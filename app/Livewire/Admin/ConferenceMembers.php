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
    protected string $pageName = 'confMembersPage';
    public array $assigned   = [];
    public array $justIssued = [];
    public array $revealed   = [];
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

        $existing = VoterId::where('conference_id', $this->conference->id)
            ->where('member_id', $memberId)
            ->first();

        if ($checked) {
            if ($existing) {
                if ($enc = Cache::get("voter_code_enc:{$existing->id}")) {
                    try { $this->justIssued[$memberId] = Crypt::decryptString($enc); } catch (\Throwable $e) {}
                }
                return;
            }

            $code = $this->generateCode(6);
            $row  = VoterId::create([
                'conference_id'     => $this->conference->id,
                'voting_session_id' => null,
                'member_id'         => $memberId,
                'voter_code_hash'   => Hash::make($code),
                'issued_by'         => auth()->id(),
                'issued_at'         => now(),
            ]);

            Cache::put("voter_code_enc:{$row->id}", Crypt::encryptString(strtoupper($code)), now()->addYear());
            $this->justIssued[$memberId] = strtoupper($code);

        } else {
            if ($existing) {
                Cache::forget("voter_code_enc:{$existing->id}");
                $existing->delete();
            }
            unset($this->justIssued[$memberId], $this->revealed[$memberId]);
        }
    }

    protected function authorizeManage(): void
    {
        $role = optional(auth()->user())->role;
        if (! in_array($role, ['SuperAdmin','Admin','VotingManager'], true)) abort(403);
        if ($this->conference->end_date) {
            $this->addError('members','Conference has ended.');
            throw new \RuntimeException('Conference ended');
        }
    }

    protected function generateCode(int $len = 6): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $out = '';
        for ($i = 0; $i < $len; $i++) $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        return $out;
    }

    public function render()
    {
        $members = Member::query()
            ->when($this->search !== '', function ($q) {
                $s = '%'.$this->search.'%';
                $q->where(function($w) use ($s) {
                    $w->where('name','like',$s)
                      ->orWhere('email','like',$s)
                      ->orWhere('first_name','like',$s)
                      ->orWhere('last_name','like',$s)
                      ->orWhere('branch_name','like',$s)
                      ->orWhere('member_type','like',$s);
                });
            })
            ->orderBy('name')
            ->paginate(15, ['*'], $this->pageName);

        $this->assigned = VoterId::where('conference_id', $this->conference->id)
            ->pluck('id', 'member_id')
            ->all();

        $codesByMember = [];
        foreach (array_keys($this->revealed) as $memberId) {
            if (! isset($this->assigned[$memberId])) continue;
            $voterId = $this->assigned[$memberId];
            $enc = Cache::get("voter_code_enc:{$voterId}");
            if ($enc) {
                try { $codesByMember[$memberId] = Crypt::decryptString($enc); }
                catch (\Throwable $e) { $codesByMember[$memberId] = null; }
            } else {
                $codesByMember[$memberId] = null;
            }
        }

        return view('livewire.admin.conference-members', [
            'members'       => $members,
            'codesByMember' => $codesByMember,
        ]);
    }
}
