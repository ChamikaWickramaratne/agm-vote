<?php

namespace App\Livewire\Admin;

use App\Models\Conference;
use App\Models\Member;
use App\Models\VoterId;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ConferenceMembers extends Component
{
    use WithPagination;

    public Conference $conference;

    // UI state
    protected string $pageName = 'confMembersPage';
    public array $assigned   = [];   // [member_id => voter_id]
    public array $justIssued = [];   // [member_id => plaintext]
    public array $revealed   = [];   // [member_id => true]
    public ?int $addMemberId = null;
    public string $search = '';

    public function mount(Conference $conference): void
    {
        $this->conference = $conference;
    }

    public function updatedConfMembersPage(): void
    {
        $this->reset(['justIssued','revealed']);
    }

    public function revealCode(int $memberId): void
    {
        if (! isset($this->assigned[$memberId])) return;
        $this->revealed[$memberId] = true;
    }

    public function hideCode(int $memberId): void
    {
        unset($this->revealed[$memberId]);
    }

    public function toggleMember(int $memberId, bool $checked): void
    {
        $this->authorizeManage();

        // ensure they’re an attendee first
        $isAttendee = $this->conference->members()->where('members.id',$memberId)->exists();
        if (! $isAttendee) {
            $this->addError('members', 'Add the member to this conference first.');
            return;
        }
        $role = optional(auth()->user())->role;
        if (! in_array($role, ['SuperAdmin','Admin','VotingManager'], true)) abort(403);

        if ($this->conference->end_date) {
            $this->addError('members', 'Conference has ended.');
            return;
        }

        $existing = VoterId::where('conference_id', $this->conference->id)
            ->where('member_id', $memberId)
            ->first();

        if ($checked) {
            if ($existing) {
                // Re-show cached plaintext if present
                if ($enc = Cache::get("voter_code_enc:{$existing->id}")) {
                    try { $this->justIssued[$memberId] = Crypt::decryptString($enc); } catch (\Throwable $e) {}
                }
                return;
            }

            // Create ONE code for the entire conference
            $code = $this->generateCode(6);
            $row = VoterId::create([
                'conference_id'     => $this->conference->id,
                'voting_session_id' => null,           // <- not needed anymore
                'member_id'         => $memberId,
                'voter_code_hash'   => Hash::make($code),
                'issued_by'         => auth()->id(),
                'issued_at'         => now(),
            ]);

            Cache::put("voter_code_enc:{$row->id}", Crypt::encryptString($code), now()->addYear());
            $this->justIssued[$memberId] = $code;

        } else {
            if ($existing) {
                Cache::forget("voter_code_enc:{$existing->id}");
                $existing->delete();
            }
            unset($this->justIssued[$memberId], $this->revealed[$memberId]);
        }
    }

    protected function generateCode(int $len = 6): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $out;
    }

    public function render()
    {
        // attendees list (with optional search)
        $attendees = $this->conference->members()
            ->when($this->search !== '', fn($q) =>
                $q->where('name','like','%'.$this->search.'%')
                ->orWhere('email','like','%'.$this->search.'%'))
            ->orderBy('name')
            ->paginate(15, ['*'], $this->pageName);

        // assigned map (unchanged)
        $this->assigned = VoterId::where('conference_id', $this->conference->id)
            ->pluck('id', 'member_id')->all();

        // decrypt only for revealed (unchanged)
        $codesByMember = [];
        foreach (array_keys($this->revealed) as $memberId) {
            if (! isset($this->assigned[$memberId])) continue;
            $voterId = $this->assigned[$memberId];
            if ($enc = \Cache::get("voter_code_enc:{$voterId}")) {
                try { $codesByMember[$memberId] = \Crypt::decryptString($enc); }
                catch (\Throwable $e) { $codesByMember[$memberId] = null; }
            } else {
                $codesByMember[$memberId] = null;
            }
        }

        // dropdown source = non-attendees to add
        $nonAttendees = \App\Models\Member::whereNotIn('id',
            $this->conference->members()->pluck('members.id')
        )->orderBy('name')->limit(200)->get(['id','name','email']);

        return view('livewire.admin.conference-members', [
            'members'       => $attendees,
            'codesByMember' => $codesByMember,
            'nonAttendees'  => $nonAttendees,
        ]);
    }


    public function addAttendee(): void
    {
        $this->authorizeManage();

        $data = $this->validate([
            'addMemberId' => ['required','integer','exists:members,id'],
        ]);

        // Attach on the explicit relation
        $this->conference->members()->syncWithoutDetaching([$data['addMemberId']]);

        // Refresh the parent model so $this->conference->members() reflects the change
        $this->conference->refresh();

        // Reset UI and pagination page so the new row appears immediately
        $this->addMemberId = null;
        $this->resetPage($this->pageName);

        session()->flash('ok','Member added to conference.');
    }


    public function removeAttendee(int $memberId): void
    {
        $this->authorizeManage();

        if ($row = \App\Models\VoterId::where('conference_id',$this->conference->id)
                ->where('member_id',$memberId)->first()) {
            \Cache::forget("voter_code_enc:{$row->id}");
            $row->delete();
            unset($this->assigned[$memberId], $this->revealed[$memberId], $this->justIssued[$memberId]);
        }

        $this->conference->members()->detach($memberId);

        // make UI reflect the change
        $this->conference->refresh();                    // <—
        $this->resetPage($this->pageName);              // <—
        session()->flash('ok','Member removed from conference.');
    }


    // small guard
    protected function authorizeManage(): void
    {
        $role = optional(auth()->user())->role;
        if (! in_array($role, ['SuperAdmin','Admin','VotingManager'], true)) abort(403);

        if ($this->conference->end_date) {
            $this->addError('members','Conference has ended.');
            throw new \RuntimeException('Conference ended'); // or simply: return; and handle in caller
        }
    }
}
