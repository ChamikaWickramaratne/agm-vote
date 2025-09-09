<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conference;
use App\Models\VotingSession;
use App\Models\Candidate;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use ZipArchive;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\JcTable;

class SessionExportController extends Controller
{
    public function download(Conference $conference, VotingSession $session)
    {
        // 0) Guards
        abort_unless($session->conference_id === $conference->id, 404);
        $session->load(['position', 'conference']);

        // 1) Fetch data
        $candidates = \App\Models\Candidate::with('member')
            ->where('position_id', $session->position_id)
            ->withCount([
                'ballots as votes_count' => fn($q) => $q->where('voting_session_id', $session->id),
            ])
            ->orderByDesc('votes_count')
            ->orderBy('id')
            ->get();

        $winnerNames = [];
        if ($session->status === 'Closed' || !is_null($session->end_time)) {
            $max = (int)($candidates->max('votes_count') ?? 0);
            if ($max > 0) {
                $winnerNames = $candidates->where('votes_count', $max)
                    ->map(fn($c) => $c->member->name ?? ($c->name ?? "Candidate #{$c->id}"))
                    ->values()->all();
            }
        }

        // 2) Ensure PHPWord uses a writable temp directory
        $tmpDir = storage_path('app/phpword-temp');
        if (! is_dir($tmpDir)) @mkdir($tmpDir, 0775, true);
        \PhpOffice\PhpWord\Settings::setTempDir($tmpDir);

        // Helper to strip control chars / invalid Unicode that can trip XML
        // Replace your $clean with this:
        $clean = function ($s) {
            $s = (string)$s;

            // Normalize to NFC if intl is available (avoids weird combining sequences)
            if (class_exists(\Normalizer::class) && \Normalizer::isNormalized($s, \Normalizer::NFC) === false) {
                $s = \Normalizer::normalize($s, \Normalizer::NFC);
            }

            // Strip characters not allowed by XML 1.0:
            // Allowed ranges: 0x9 | 0xA | 0xD | 0x20-0xD7FF | 0xE000-0xFFFD
            // (We also remove UTF-16 surrogate range 0xD800-0xDFFF which is illegal in XML 1.0.)
            $s = preg_replace(
                '/(?!' .
                    '\x09' .                 // TAB
                    '|\x0A' .                // LF
                    '|\x0D' .                // CR
                    '|[\x20-\x{D7FF}]' .     // Basic Multilingual Plane up to before surrogates
                    '|[\x{E000}-\x{FFFD}]' . // Private use area to BMP end
                ')/u',
                '',
                $s
            );

            // Fallback: if regex failed (rare invalid UTF-8), force to UTF-8 cleanly:
            if ($s === null) $s = '';

            // Optionally trim super-long runs that sometimes trip Word
            if (strlen($s) > 2000) {
                $s = substr($s, 0, 2000) . '…';
            }

            return $s;
        };


        // 3) Build the document
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        $titleStyle = ['size' => 18, 'bold' => true];
        $hStyle     = ['size' => 12, 'bold' => true];
        $tableStyle = ['borderColor' => 'cccccc', 'borderSize' => 6, 'cellMargin' => 80];
        $firstRow   = ['bgColor' => 'eeeeee'];
        $phpWord->addTableStyle('VotesTable', $tableStyle, $firstRow);

        $section = $phpWord->addSection();

        // Title
        $section->addText('Voting Session Report', $titleStyle);
        $section->addTextBreak(1);

        // Header meta (clean all strings before inserting)
        $section->addText('Conference ID: '.$conference->id);
        $section->addText('Session ID: '.$session->id);
        $section->addText('Position: '.$clean(optional($session->position)->name ?? '—'));
        $section->addText('Status: '.$clean($session->status));
        $section->addText('Starts: '.($session->start_time ? $session->start_time->format('Y-m-d H:i') : '—'));
        $section->addText('Ends: '.($session->end_time ? $session->end_time->format('Y-m-d H:i') : '—'));
        $section->addTextBreak(1);

        $section->addText('Candidates and Votes');
        $table = $section->addTable([
            'borderColor' => '999999',
            'borderSize'  => 6,
            'cellMargin'  => 80,   // twips; safe across versions
        ]);

        $table->addRow();
        $table->addCell(7000)->addText('Candidate');
        $table->addCell(2000)->addText('Votes');

        $sanitize = fn ($s) => preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', (string)$s) ?? '';
        foreach ($candidates as $c) {
            $name = $c->member->name ?? ($c->name ?? "Candidate #{$c->id}");
            $table->addRow();
            $table->addCell(7000)->addText($sanitize($name));
            $table->addCell(2000)->addText((string) ($c->votes_count ?? 0));
        }


        if (!empty($winnerNames)) {
            $section->addTextBreak(1);
            $section->addText('Winner(s): '.$clean(implode(', ', $winnerNames)), ['bold' => true]);
        }

        // 4) Write to disk in exports/
        \Storage::disk('local')->makeDirectory('exports');
        $fullPath = \Storage::path("exports/session_{$conference->id}_{$session->id}.docx");

        try {
            \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($fullPath);
        } catch (\Throwable $e) {
            \Log::error('PHPWord save failed', ['err' => $e->getMessage()]);
            abort(500, 'Failed to generate DOCX: '.$e->getMessage());
        }

        // 5) Validate the resulting ZIP to catch corruption before sending
        $size = @filesize($fullPath);
        $zipOk = false;
        if ($size && $size > 800) {
            if (class_exists(\ZipArchive::class)) {
                $zip = new \ZipArchive();
                $zipOk = ($zip->open($fullPath) === true);
                if ($zipOk) $zip->close();
            } else {
                // If ZipArchive is missing, we already know hello.docx worked, so skip
                $zipOk = true;
            }
        }

        if (! $zipOk) {
            \Log::error('DOCX invalid/too small', ['path' => $fullPath, 'size' => $size]);
            abort(500, 'Generated DOCX seems invalid. Check logs for details.');
        }

        // 6) IMPORTANT: clear any buffered output
        while (ob_get_level()) { ob_end_clean(); }

        Storage::disk('local')->makeDirectory('exports');
        $fullPath = Storage::path("exports/session_{$conference->id}_{$session->id}.docx");

        try {
            if (function_exists('ini_set')) {
                @ini_set('zlib.output_compression', 'Off');
                @ini_set('output_buffering', 'Off');
            }

            IOFactory::createWriter($phpWord, 'Word2007')->save($fullPath);
        } catch (\Throwable $e) {
            \Log::error('PHPWord save failed', ['err' => $e->getMessage()]);
            abort(500, 'Failed to generate DOCX: '.$e->getMessage());
        }
        
        $size = @filesize($fullPath);
        $zipOk = false;
        if ($size && $size > 800) {
            if (class_exists(\ZipArchive::class)) {
                $zip = new \ZipArchive();
                $zipOk = ($zip->open($fullPath) === true);
                if ($zipOk) $zip->close();
            } else {
                $fh = fopen($fullPath, 'rb');
                $sig = $fh ? fread($fh, 2) : '';
                if ($fh) fclose($fh);
                $zipOk = ($sig === "PK"); // best-effort check if ZipArchive missing
            }
        }
        if (! $zipOk) {
            \Log::error('DOCX invalid/too small', ['path' => $fullPath, 'size' => $size]);
            abort(500, 'Generated DOCX seems invalid. Check logs for details.');
        }

        // 6) IMPORTANT: clear any buffered output (kills debug/echo noise)
        while (ob_get_level()) { @ob_end_clean(); }

        // 7) Use Laravel's BinaryFileResponse to avoid accidental body appenders
        return response()->download(
            $fullPath,
            "session-{$conference->id}-{$session->id}.docx",
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        )->deleteFileAfterSend(false);

    }

}
