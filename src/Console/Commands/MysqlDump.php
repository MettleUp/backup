<?php

namespace Backup\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MysqlDump extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:mysql-dump
                            {filename? : Mysql backup filename}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump your Mysql database to a file';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $host = config('database.connections.mysql.host');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $backupPath = config('backup.mysql.path');

        $cloudStorage = config('backup.mysql.cloud-storage.enabled');
        $cloudDisk = config('backup.mysql.cloud-storage.disk');
        $cloudPath = config('backup.mysql.cloud-storage.path');
        $keepLocal = config('backup.mysql.cloud-storage.keep-local');
        $extension = config('backup.mysql.compress')?'sql.gz':'sql';
        $compress = config('backup.mysql.compress')?'| gzip':'';

        $filename = $database.'_'.empty(trim($this->argument('filename'))) ? $database.'_'.\Carbon\Carbon::now()->format('YmdHis') : trim($this->argument('filename'));

        $dumpCommand = "mysqldump -e -f -h $host -u $username -p$password $database $compress > $backupPath$filename.$extension";

        exec($dumpCommand);

        $this->info('Mysql backup completed!');

        if ($cloudStorage) {
            $fileContents = file_get_contents("$backupPath$filename.$extension");
            Storage::disk($cloudDisk)->put("$cloudPath$filename.$extension", $fileContents);

            if (!$keepLocal) {
                $rmCommand = "rm $backupPath$filename.$extension";
                exec($rmCommand);
            }

            $this->info('Backup uploaded to cloud storage!');
        }
    }
}
