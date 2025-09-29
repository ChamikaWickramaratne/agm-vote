<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conference;
use App\Models\VotingSession;
use App\Models\Candidate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;

class SessionExportController extends Controller
{
    public function download(Conference $conference, VotingSession $session)
    {
        abort_unless($session->conference_id === $conference->id, 404);
        $session->load(['position', 'conference']);

        // ---------- Helpers ----------
        $clean = function ($s) {
            $s = (string) $s;

            if (class_exists(\Normalizer::class) && \Normalizer::isNormalized($s, \Normalizer::NFC) === false) {
                $s = \Normalizer::normalize($s, \Normalizer::NFC);
            }

            // keep only XML-legal chars
            $s = preg_replace(
                '/(?!' .
                    '\x09' .
                    '|\x0A' .
                    '|\x0D' .
                    '|[\x20-\x{D7FF}]' .
                    '|[\x{E000}-\x{FFFD}]' .
                ')/u',
                '',
                $s
            );

            if ($s === null) $s = '';
            if (strlen($s) > 2000) {
                $s = substr($s, 0, 2000) . '…';
            }

            return $s;
        };
        $sanitize = fn ($s) => preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', (string) $s) ?? '';

        // ---------- Temp dir for PhpWord ----------
        $tmpDir = storage_path('app/phpword-temp');
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }
        Settings::setTempDir($tmpDir);

        // ---------- Candidates (prefer session relation if present) ----------
        if (method_exists($session, 'sessionCandidates')) {
            $candidates = $session->sessionCandidates()
                ->with('member')
                ->orderBy('candidates.id')
                ->get();
        } else {
            // fallback: all candidates on the position
            $candidates = Candidate::with('member')
                ->where('position_id', $session->position_id)
                ->orderBy('id')
                ->get();
        }

        // ---------- Tallies (IDENTICAL to SessionShow) ----------
        $isMulti = (bool) ($session->voting_rules['multiSelect'] ?? false);

        if ($isMulti) {
            // Multi-select: count distinct voters per candidate
            $tallies = DB::table('ballots')
                ->where('voting_session_id', $session->id)
                ->select('candidate_id', DB::raw('COUNT(DISTINCT voter_code_hash) AS votes'))
                ->groupBy('candidate_id')
                ->pluck('votes', 'candidate_id');

            $totalVotes = (int) $tallies->sum();
        } else {
            // Single-select: only the latest ballot per voter counts
            $latestPerVoter = DB::table('ballots as b1')
                ->select(DB::raw('MAX(b1.id) AS max_id'))
                ->where('b1.voting_session_id', $session->id)
                ->groupBy('b1.voter_code_hash');

            $tallies = DB::table('ballots as b')
                ->joinSub($latestPerVoter, 'lpv', fn($j) => $j->on('b.id', '=', 'lpv.max_id'))
                ->where('b.voting_session_id', $session->id)
                ->select('b.candidate_id', DB::raw('COUNT(*) AS votes'))
                ->groupBy('b.candidate_id')
                ->pluck('votes', 'candidate_id');

            $totalVotes = (int) $tallies->sum();
        }

        $majorityPercent = $session->majority_percent ?? null;

        $majorityMode = $session->majority_mode ?? 'simple';
        $majorityLabel = match ($majorityMode) {
            'simple'     => 'Simple majority (≥ 50%)',
            'two_thirds' => 'Two-thirds majority (≥ 66.67%)',
            'plurality'  => 'Plurality (no threshold)',
            'custom'     => is_numeric($majorityPercent)
                ? 'Custom (≥ ' . rtrim(rtrim(number_format((float)$majorityPercent, 2, '.', ''), '0'), '.') . '%)'
                : 'Custom (—)',
            default      => 'Simple majority (≥ 50%)',
        };

        // ---------- Winner(s) by plurality using above tallies ----------
        $winnerNames = [];
        if ($session->status === 'Closed' || !is_null($session->end_time)) {
            $max = (int) ($tallies->max() ?? 0);
            if ($max > 0) {
                $winnerNames = $candidates
                    ->filter(fn($c) => (int) ($tallies[$c->id] ?? 0) === $max)
                    ->map(fn($c) => $c->member->name ?? ($c->name ?? "Candidate #{$c->id}"))
                    ->values()
                    ->all();
            }
        }

        // ---------- Build DOCX ----------
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        $titleStyle = ['size' => 18, 'bold' => true];
        $hStyle     = ['size' => 12, 'bold' => true];

        $section = $phpWord->addSection();

        $section->addText('Voting Session Report', $titleStyle);
        $section->addTextBreak(1);

        $section->addText('Conference ID: ' . $conference->id);
        $section->addText('Session ID: ' . $session->id);
        $section->addText('Position: ' . $clean(optional($session->position)->name ?? '—'));
        $section->addText('Status: ' . $clean($session->status));
        $section->addText('Starts: ' . ($session->start_time ? $session->start_time->format('Y-m-d H:i') : '—'));
        $section->addText('Ends: ' . ($session->end_time ? $session->end_time->format('Y-m-d H:i') : '—'));
        $section->addText('Majority Threshold: ' . $majorityLabel);
        $section->addTextBreak(1);

        // Table header
        $section->addText('Candidates and Votes');
        $table = $section->addTable([
            'borderColor' => '999999',
            'borderSize'  => 6,
            'cellMargin'  => 80,
        ]);

        $table->addRow();
        $table->addCell(7000)->addText('Candidate', ['bold' => true]);
        $table->addCell(2000)->addText('Votes', ['bold' => true]);

        // Rows
        foreach ($candidates as $c) {
            $name  = $c->member->name ?? ($c->name ?? "Candidate #{$c->id}");
            $votes = (int) ($tallies[$c->id] ?? 0);

            $table->addRow();
            $table->addCell(7000)->addText($sanitize($name));
            $table->addCell(2000)->addText((string) $votes);
        }

        // Total
        $table->addRow();
        $table->addCell(7000)->addText('Total', ['bold' => true]);
        $table->addCell(2000)->addText((string) $totalVotes, ['bold' => true]);

        // Winner(s)
        if (!empty($winnerNames)) {
            $section->addTextBreak(1);
            $section->addText('Winner(s): ' . $clean(implode(', ', $winnerNames)), ['bold' => true]);
        }

        // Chart (uses same tallies)
        if ($session->status === 'Closed' || !is_null($session->end_time)) {
            $labels = $candidates->map(
                fn ($c) => $c->member->name ?? ($c->name ?? "Candidate #{$c->id}")
            )->all();

            $values = $candidates->map(
                fn ($c) => (int) ($tallies[$c->id] ?? 0)
            )->all();

            $section->addTextBreak(1);
            $section->addText('Charts', $hStyle);

            $section->addText('Votes per Candidate', ['bold' => true]);
            $section->addChart('column', $labels, $values, [
                'width'             => Converter::inchToEmu(6.5),
                'height'            => Converter::inchToEmu(3.2),
                'title'             => 'Votes per Candidate',
                'showLegend'        => false,
                'gridY'             => true,
                'categoryAxisTitle' => 'Candidates',
                'valueAxisTitle'    => 'Votes',
            ]);
        }

        // ---------- Save DOCX ----------
        Storage::disk('local')->makeDirectory('exports');
        $fullPath = Storage::path("exports/session_{$conference->id}_{$session->id}.docx");

        try {
            IOFactory::createWriter($phpWord, 'Word2007')->save($fullPath);
        } catch (\Throwable $e) {
            Log::error('PHPWord save failed', ['err' => $e->getMessage()]);
            abort(500, 'Failed to generate DOCX: ' . $e->getMessage());
        }

        // ---------- Validate ZIP (best-effort) ----------
        $size  = @filesize($fullPath);
        $zipOk = false;
        if ($size && $size > 800) {
            if (class_exists(\ZipArchive::class)) {
                $zip = new \ZipArchive();
                $zipOk = ($zip->open($fullPath) === true);
                if ($zipOk) $zip->close();
            } else {
                // Best-effort signature check if ZipArchive missing
                $fh  = @fopen($fullPath, 'rb');
                $sig = $fh ? fread($fh, 2) : '';
                if ($fh) fclose($fh);
                $zipOk = ($sig === "PK");
            }
        }

        if (!$zipOk) {
            Log::error('DOCX invalid/too small', ['path' => $fullPath, 'size' => $size]);
            abort(500, 'Generated DOCX seems invalid. Check logs for details.');
        }

        // ---------- Flush any buffers ----------
        while (ob_get_level()) { @ob_end_clean(); }

        // ---------- Stream file ----------
        return response()->download(
            $fullPath,
            "session-{$conference->id}-{$session->id}.docx",
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        )->deleteFileAfterSend(false);
    }
}
