<?php

return [

    'directory' => env('BACKUP_DIRECTORY', storage_path('backups')),

    'max_backups' => (int) env('BACKUP_MAX_BACKUPS', 7),

    'compress' => (bool) env('BACKUP_COMPRESS', true),

    'timeout' => (int) env('BACKUP_TIMEOUT', 300),

    'mysqldump_path' => env('BACKUP_MYSQLDUMP_PATH', 'mysqldump'),

    'mysql_path' => env('BACKUP_MYSQL_PATH', 'mysql'),

];
