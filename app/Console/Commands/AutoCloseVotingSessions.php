<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AutoCloseVotingSessions extends Command
{
    protected $signature = 'sessions:auto-close';
    protected $description = 'Auto-close Open voting sessions when start_time + close_after_minutes has elapsed.';

    public function handle(): int
    {
        $now = now();

        // Find candidate sessions to close
        $ids = DB::table('voting_sessions')
            ->where('status', 'Open')
            ->where('close_condition', 'Timer')
            ->whereNotNull('start_time')
            ->whereNotNull('close_after_minutes')
            ->whereRaw("DATE_ADD(start_time, INTERVAL close_after_minutes MINUTE) <= ?", [$now])
            ->pluck('id');

        if ($ids->isEmpty()) {
            $this->info('No sessions to auto-close.');
            return self::SUCCESS;
        }

        // Close them safely under a transaction/lock
        DB::transaction(function () use ($ids) {
            $sessions = \App\Models\VotingSession::whereIn('id', $ids)->lockForUpdate()->get();
            foreach ($sessions as $s) {
                if ($s->status === 'Open') {
                    $s->update([
                        'status'   => 'Closed',
                        'end_time' => now(),
                    ]);
                }
            }
        });

        $this->info('Auto-closed sessions: '.implode(', ', $ids->all()));
        return self::SUCCESS;
    }
}
