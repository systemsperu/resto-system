<?php

namespace App\Console\Commands;

use App\Services\Sync\SyncPushService;
use Illuminate\Console\Command;

class SyncPushCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(SyncPushService $sync): int
    {
        $this->info($sync->push().' eventos confirmados por la nube.');

        return self::SUCCESS;
    }
}
