#!/usr/bin/env php
<?php

declare(strict_types=1);

use dmyers\orange\Sql;

define('__ROOT__', realpath(__DIR__ . '/../'));
chdir(__ROOT__);

require_once __ROOT__ . '/vendor/autoload.php';

mergeEnv(__ROOT__ . '/.env');

/* send config into application */
$container = cli(include __ROOT__ . '/app/config/config.php');

$pdo = $container->pdo;
$sql = new Sql([
    'tablename' => 'pick_lines pl',
    'primaryColumn' => 'id',
], $pdo);

$x = $sql
    ->select('pick_lines.id, pick_lines.pick_status_id, pick_lines.item_identifier, pick_lines.user_id, pick_lines.shipment_group_id, sg.group_type, batch_release.id as batch_id, batch_release.shipment_type_id')
    ->from()
    ->innerJoin('shipment_group sg','sg.id','pick_lines.shipment_group_id')
    ->and('pick_lines.bar',123)
    ->and('pick_lines.foo',456)
    ->whereEqual('batch_release.id',23)
    ->and()
    ->whereIsNull('batch_release.merged_into_batch')
    ->build();

echo $x.PHP_EOL;

var_export($sql->boundValues());