<?php

$iniArray = parse_ini_file(__DIR__ . '/../../.env', true, INI_SCANNER_TYPED);

return
    [
        'paths' => [
            'migrations' => '%%PHINX_CONFIG_DIR%%/migrations',
            'seeds' => '%%PHINX_CONFIG_DIR%%/seeds'
        ],
        'environments' => [
            'default_migration_table' => 'phinxlog',
            'default_environment' => 'development',
            'production' => [
                'adapter' => 'mysql',
                'host' => 'localhost',
                'name' => 'production_db',
                'user' => 'root',
                'pass' => '',
                'port' => '3306',
                'charset' => 'utf8',
            ],
            'development' => [
                'adapter' => 'mysql',
                'host' => $iniArray['db']['host'],
                'name' => $iniArray['db']['database'],
                'user' => $iniArray['db']['username'],
                'pass' => $iniArray['db']['password'],
                'port' => '3306',
                'charset' => 'utf8',
            ],
            'testing' => [
                'adapter' => 'mysql',
                'host' => 'localhost',
                'name' => 'testing_db',
                'user' => 'root',
                'pass' => '',
                'port' => '3306',
                'charset' => 'utf8',
            ]
        ],
        'version_order' => 'creation'
    ];
