<?php

namespace App\Console\Commands;

use App\Models\QuizPublished;
use App\Models\StudentProgress;
use Illuminate\Console\Command;

class PruneOrphanedQuizProgress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'progress:prune-orphaned {--dry-run : List the rows that would be deleted without deleting them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete pre/post student_progress rows for topics that no longer have a published quiz';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $orphaned = StudentProgress::query()
            ->whereIn('phase', StudentProgress::QUIZ_PHASES)
            ->whereNotIn('topic_key', StudentProgress::SELF_DIRECTED_TOPIC_KEYS)
            ->whereNotIn('topic_key', QuizPublished::query()->select('topic_key'));

        $count = (clone $orphaned)->count();

        if ($count === 0) {
            $this->info('No orphaned quiz progress rows found.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->table(
                ['session_id', 'topic_key', 'phase', 'score', 'total'],
                (clone $orphaned)->get(['session_id', 'topic_key', 'phase', 'score', 'total'])->toArray(),
            );
            $this->warn("{$count} row(s) would be deleted. Re-run without --dry-run to delete them.");

            return self::SUCCESS;
        }

        $deleted = $orphaned->delete();
        $this->info("Deleted {$deleted} orphaned quiz progress row(s).");

        return self::SUCCESS;
    }
}
