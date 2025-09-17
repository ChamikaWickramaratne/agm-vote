<?php

// app/Console/Commands/CloseTimedVotingSessions.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VotingSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CloseTimedVotingSessions extends Command
{
    protected $signature = 'sessions:close-timed';
    protected $description = 'Close voting sessions whose timer has elapsed';

    public function handle(): int
    {
        // Find sessions: Timer, started, not yet ended, and expired
        $now = Carbon::now();

        $sessions = VotingSession::query()
            ->where('close_condition', 'Timer')
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->whereNotNull('close_after_minutes')
            ->whereRaw('start_time + INTERVAL close_after_minutes MINUTE <= ?', [$now])
            ->lockForUpdate()
            ->get();

        foreach ($sessions as $s) {
            DB::transaction(function () use ($s, $now) {
                // double-check inside txn
                $deadline = Carbon::parse($s->start_time)->addMinutes($s->close_after_minutes);
                if ($deadline->lte($now) && is_null($s->end_time)) {
                    $s->status   = 'Closed';
                    $s->end_time = $now;
                    $s->save();
                }
            });
        }

        $this->info("Closed {$sessions->count()} timed sessions.");
        return self::SUCCESS;
    }
}
