<?php

namespace App\Console\Commands;

use App\Services\Sync\CloudBackupService;
use Illuminate\Console\Command;

class CloudBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:cloud';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(CloudBackupService $backups): int
    {
        $backup = $backups->create();
        $this->info('Respaldo cifrado y verificado: '.$backup->backup_uuid);

        return self::SUCCESS;
    }
}
