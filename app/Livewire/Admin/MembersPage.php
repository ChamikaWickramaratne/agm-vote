<?php

namespace App\Livewire\Admin;

use App\Models\Member;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class MembersPage extends Component
{
    use WithPagination, WithFileUploads;

    // ---------- Create form ----------
    #[Validate(['nullable','string','in:Mr.,Mrs.,Miss,Ms'])]
    public ?string $title = null;

    #[Validate('required|string|min:2|max:255')]
    public string $first_name = '';

    #[Validate('required|string|min:2|max:255')]
    public string $last_name = '';

    #[Validate(['nullable','string','max:255'])]
    public ?string $branch_name = null;

    #[Validate(['nullable','string','max:255'])]
    public ?string $member_type = null;

    #[Validate(['nullable','email','max:255','unique:members,email'])]
    public ?string $email = null;

    #[Validate(['nullable','string','max:2000'])]
    public ?string $bio = null;

    #[Validate(['nullable','image','max:2048'])] // ~2MB
    public $photoUpload = null; // Livewire file (create)

    // Legacy props (kept for compatibility but no longer used in the form)
    #[Validate('required|string|min:2|max:255')]
    public string $name = '';

    // ---------- Search ----------
    public string $search = '';

    // ---------- Edit state ----------
    public ?int $editingId = null;

    #[Validate(['nullable','string','in:Mr.,Mrs.,Miss,Ms'])]
    public ?string $editTitle = null;

    #[Validate('required|string|min:2|max:255')]
    public string $editFirstName = '';

    #[Validate('required|string|min:2|max:255')]
    public string $editLastName = '';

    #[Validate(['nullable','string','max:255'])]
    public ?string $editBranchName = null;

    #[Validate(['nullable','string','max:255'])]
    public ?string $editMemberType = null;

    #[Validate(['nullable','email','max:255'])]
    public ?string $editEmail = null;

    #[Validate(['nullable','string','max:2000'])]
    public ?string $editBio = null;

    #[Validate(['nullable','image','max:2048'])]
    public $editPhotoUpload = null; // Livewire file (edit)

    public bool $showEditModal = false;

    // ---------- Bulk Import state ----------
    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $importFile = null;
    public bool $dryRun = true;
    public int $progress = 0; // 0..100
    public array $report = [
        'total'   => 0,
        'valid'   => 0,
        'created' => 0,
        'skipped' => 0,
        'errors'  => [], // [ ['row'=>3,'message'=>'...'], ... ]
    ];

    // Reset pagination when searching
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        // Validate new fields
        $this->validate([
            'title'       => ['nullable','string','in:Mr.,Mrs.,Miss,Ms'],
            'first_name'  => ['required','string','min:2','max:255'],
            'last_name'   => ['required','string','min:2','max:255'],
            'branch_name' => ['nullable','string','max:255'],
            'member_type' => ['nullable','string','max:255'],
            'email'       => ['nullable','email','max:255','unique:members,email'],
            'bio'         => ['nullable','string','max:2000'],
            'photoUpload' => ['nullable','image','max:2048'],
        ]);

        $photoPath = null;
        if ($this->photoUpload) {
            // store in storage/app/public/members
            $photoPath = $this->photoUpload->store('members', 'public');
        }

        // Maintain legacy 'name' column
        $fullName = trim($this->first_name.' '.$this->last_name);

        Member::create([
            'title'       => $this->title,
            'first_name'  => $this->first_name,
            'last_name'   => $this->last_name,
            'branch_name' => $this->branch_name,
            'member_type' => $this->member_type,
            'email'       => $this->email ?: null,
            'bio'         => $this->bio,
            'photo'       => $photoPath,
            'name'        => $fullName,   // keep old column populated
        ]);

        // Reset form
        $this->reset([
            'title','first_name','last_name','branch_name','member_type',
            'email','bio','photoUpload'
        ]);

        session()->flash('ok', 'Member created.');
    }

    public function startEdit(int $id): void
    {
        $m = Member::findOrFail($id);
        $this->editingId     = $m->id;
        $this->editTitle     = $m->title;
        $this->editFirstName = $m->first_name ?? '';
        $this->editLastName  = $m->last_name ?? '';
        $this->editBranchName= $m->branch_name;
        $this->editMemberType= $m->member_type;
        $this->editEmail     = $m->email;
        $this->editBio       = $m->bio;
        $this->editPhotoUpload = null;

        $this->showEditModal = true;
    }

    public function cancelEdit(): void
    {
        $this->reset([
            'editingId','editTitle','editFirstName','editLastName','editBranchName',
            'editMemberType','editEmail','editBio','editPhotoUpload'
        ]);

        $this->showEditModal = false;
    }

    public function update(): void
    {
        if (!$this->editingId) return;

        $this->validate([
            'editTitle'      => ['nullable','string','in:Mr.,Mrs.,Miss,Ms'],
            'editFirstName'  => ['required','string','min:2','max:255'],
            'editLastName'   => ['required','string','min:2','max:255'],
            'editBranchName' => ['nullable','string','max:255'],
            'editMemberType' => ['nullable','string','max:255'],
            'editEmail'      => [
                'nullable','email','max:255',
                Rule::unique('members','email')->ignore($this->editingId),
            ],
            'editBio'        => ['nullable','string','max:2000'],
            'editPhotoUpload'=> ['nullable','image','max:2048'],
        ]);

        $m = Member::findOrFail($this->editingId);

        // Handle new photo upload (optional)
        if ($this->editPhotoUpload) {
            $newPath = $this->editPhotoUpload->store('members', 'public');
            // Optionally delete old file
            if ($m->photo && Storage::disk('public')->exists($m->photo)) {
                Storage::disk('public')->delete($m->photo);
            }
            $m->photo = $newPath;
        }

        $m->fill([
            'title'       => $this->editTitle,
            'first_name'  => $this->editFirstName,
            'last_name'   => $this->editLastName,
            'branch_name' => $this->editBranchName,
            'member_type' => $this->editMemberType,
            'email'       => $this->editEmail ?: null,
            'bio'         => $this->editBio,
            'name'        => trim($this->editFirstName.' '.$this->editLastName), // legacy
        ])->save();

        $this->showEditModal = false;
        $this->cancelEdit();

        session()->flash('ok', 'Member updated.');
    }

    public function delete(int $id): void
    {
        $m = Member::findOrFail($id);
        // optionally remove stored photo
        if ($m->photo && Storage::disk('public')->exists($m->photo)) {
            Storage::disk('public')->delete($m->photo);
        }
        $m->delete();

        session()->flash('ok', 'Member deleted.');
    }

    // ---------- Bulk Import actions ----------

    public function downloadTemplate()
    {
        $csv = implode("\n", [
            'title,first_name,last_name,email,branch_name,member_type,bio',
            'Mr.,Jane,Doe,jane@example.com,Colombo,Regular,Short bio here',
            'Ms,Sam,Lee,,Sydney,Associate,',
        ])."\n";

        // Stream the file directly, no need to check disk
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="members_template.csv"');
    }


    public function import()
    {
        $this->validate([
            'importFile' => ['required','file','mimes:csv,txt','max:51200'],
            'dryRun'     => ['boolean'],
        ]);

        // Reset report
        $this->report = ['total'=>0,'valid'=>0,'created'=>0,'skipped'=>0,'errors'=>[]];
        $this->progress = 0;

        // 👉 Read directly from Livewire temp file (no intermediate storage)
        $real = $this->importFile->getRealPath();
        if (!$real || !is_readable($real)) {
            $this->report['errors'][] = ['row'=>1, 'message'=>'Unable to read uploaded file (temp file not readable)'];
            $this->progress = 100;
            return;
        }

        [$rows, $headers] = $this->readCsvFromPath($real);

        $required = ['title','first_name','last_name'];
        if (!$this->hasRequiredHeaders($headers, $required)) {
            $this->report['errors'][] = [
                'row'=>1,
                'message'=>'Invalid headers. Expected: '.implode(',', $required),
            ];
            $this->progress = 100;
            return;
        }

        $this->report['total'] = count($rows);

        // Preload existing emails for fast duplicate detection (email is optional)
        $incomingEmails = array_values(array_filter(array_map(fn($r) => trim((string)($r['email'] ?? '')), $rows)));
        $existingEmails = $incomingEmails
            ? Member::query()->whereIn('email', $incomingEmails)->pluck('email')->all()
            : [];

        $toInsert = [];
        $rowNum = 1; // header row is 1; data lines start at 2
        foreach ($rows as $r) {
            $rowNum++;

            $data = $this->mapRowToMember($r);

            // Validate row
            $errs = $this->validateRow($data, $existingEmails);
            if ($errs) {
                $this->report['errors'][] = ['row'=>$rowNum, 'message'=>implode('; ', $errs)];
                $this->report['skipped']++;
                continue;
            }

            $this->report['valid']++;

            if (!$this->dryRun) {
                $toInsert[] = $this->prepareForInsert($data);
                if ($data['email']) $existingEmails[] = $data['email']; // prevent dupes within same file
            }
        }

        if (!$this->dryRun && $toInsert) {
            $total = count($toInsert);
            $done  = 0;

            foreach (array_chunk($toInsert, 500) as $chunk) {
                DB::transaction(fn() => Member::insert($chunk));
                $done += count($chunk);
                $this->report['created'] += count($chunk);
                $this->progress = (int) floor(($done / $total) * 100);
            }
        }

        $this->progress = 100;

        session()->flash(
            'ok',
            $this->dryRun
                ? "Dry-run complete: {$this->report['valid']} valid, {$this->report['skipped']} with errors."
                : "Import complete: created {$this->report['created']}, skipped {$this->report['skipped']}."
        );
    }

    private function readCsvFromPath(string $path): array
    {
        $fh = fopen($path, 'r');
        if (!$fh) return [[], []];

        $headers = fgetcsv($fh, 0, ',');
        if (!$headers) { fclose($fh); return [[], []]; }

        $headers = array_map(fn($h) => strtolower(trim((string)$h)), $headers);

        $rows = [];
        while (($row = fgetcsv($fh, 0, ',')) !== false) {
            if (count($row) === 1 && trim(implode('', $row)) === '') continue; // skip empty lines
            $assoc = [];
            foreach ($headers as $i => $h) {
                $assoc[$h] = isset($row[$i]) ? trim((string) $row[$i]) : null;
            }
            $rows[] = $assoc;
        }
        fclose($fh);
        return [$rows, $headers];
    }

    public function downloadErrors()
    {
        if (empty($this->report['errors'])) {
            session()->flash('ok', 'No errors to download.');
            return null;
        }

        $lines = ["row,message"];
        foreach ($this->report['errors'] as $e) {
            $msg = str_replace('"', '""', $e['message']);
            $lines[] = "{$e['row']},\"{$msg}\"";
        }
        $csv = implode("\n", $lines) . "\n";

        return response()->streamDownload(
            fn() => print($csv),
            'members_import_errors_'.now()->format('Ymd_His').'.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    // ---------- Helpers ----------

    /** @return array{0: array<int, array<string,string>>, 1: array<int,string>} */
    private function readCsv(string $absPath): array
    {
        $fh = fopen($absPath, 'r');
        if (!$fh) return [[], []];

        $headers = fgetcsv($fh, 0, ',');
        if (!$headers) return [[], []];

        $headers = array_map(fn($h) => strtolower(trim((string)$h)), $headers);

        $rows = [];
        while (($row = fgetcsv($fh, 0, ',')) !== false) {
            if (count($row) === 1 && trim(implode('', $row)) === '') continue; // skip empty line
            $assoc = [];
            foreach ($headers as $i => $h) {
                $assoc[$h] = isset($row[$i]) ? trim((string) $row[$i]) : null;
            }
            $rows[] = $assoc;
        }
        fclose($fh);
        return [$rows, $headers];
    }

    private function hasRequiredHeaders(array $headers, array $required): bool
    {
        $set = array_flip($headers);
        foreach ($required as $r) {
            if (!isset($set[$r])) return false;
        }
        return true;
    }

    /** Normalize + map CSV row to Member attributes */
    private function mapRowToMember(array $row): array
    {
        return [
            'title'       => $this->normalizeTitle($row['title']       ?? null),
            'first_name'  => $row['first_name']                        ?? null,
            'last_name'   => $row['last_name']                         ?? null,
            'email'       => $row['email']                             ?? null,
            'branch_name' => $row['branch_name']                       ?? null,
            'member_type' => $row['member_type']                       ?? null, // <- now safe if missing
            'bio'         => $row['bio']                               ?? null,
        ];
    }


    /** Return array of error strings */
    private function validateRow(array $d, array $existingEmails): array
    {
        $errs = [];

        if ($d['title'] && !in_array($d['title'], ['Mr.','Mrs.','Miss','Ms'], true)) {
            $errs[] = 'title must be one of: Mr., Mrs., Miss, Ms';
        }
        if (!$d['first_name'] || strlen($d['first_name']) < 2) $errs[] = 'first_name is required (min 2)';
        if (!$d['last_name']  || strlen($d['last_name']) < 2)  $errs[] = 'last_name is required (min 2)';

        if ($d['email']) {
            if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
                $errs[] = 'email is invalid';
            } elseif (in_array($d['email'], $existingEmails, true)) {
                $errs[] = 'email already exists';
            }
        }

        if ($d['branch_name'] && strlen($d['branch_name']) > 255) $errs[] = 'branch_name too long';
        if ($d['member_type'] && strlen($d['member_type']) > 255) $errs[] = 'member_type too long';
        if ($d['bio'] && strlen($d['bio']) > 2000)               $errs[] = 'bio too long';

        return $errs;
    }

    private function prepareForInsert(array $d): array
    {
        return [
            'title'       => $d['title'],
            'first_name'  => $d['first_name'],
            'last_name'   => $d['last_name'],
            'email'       => $d['email'] ?: null,
            'branch_name' => $d['branch_name'],
            'member_type' => $d['member_type'],
            'bio'         => $d['bio'],
            'photo'       => null, // not handled via CSV
            'name'        => trim(($d['first_name'] ?? '').' '.($d['last_name'] ?? '')), // legacy
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
    }

    private function normalizeTitle(?string $title): ?string
    {
        if (!$title) return null;
        $t = trim($title);
        // Standardize common variants (e.g., "mr", "MR", "mr.")
        $t = rtrim(ucfirst(strtolower($t)), '.');
        return match ($t) {
            'Mr'   => 'Mr.',
            'Mrs'  => 'Mrs.',
            'Miss' => 'Miss',
            'Ms'   => 'Ms',
            default => null, // invalid becomes null; validator will flag if present
        };
    }

    protected function getPerPage(): int
    {
        return 10;
    }

    public function render()
    {
        $q = Member::query()
            ->when($this->search !== '', function ($qq) {
                $s = '%'.$this->search.'%';
                $qq->where(function ($w) use ($s) {
                    $w->where('first_name','like',$s)
                      ->orWhere('last_name','like',$s)
                      ->orWhere('email','like',$s)
                      ->orWhere('branch_name','like',$s)
                      ->orWhere('member_type','like',$s);
                });
            })
            ->orderByDesc('id');

        $members = $q->paginate($this->getPerPage());

        return view('livewire.admin.members-page', compact('members'));
    }
}
