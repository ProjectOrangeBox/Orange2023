#!/usr/bin/env php
<?php

declare(strict_types=1);

use dmyers\orange\Sql;

define('__ROOT__', realpath(__DIR__ . '/../'));
chdir(__ROOT__);

require_once __ROOT__ . '/vendor/autoload.php';

mergeEnv(__ROOT__ . '/bin/.env');

/* send config into application */
$container = cli(include __ROOT__ . '/app/config/config.php');

$pdo = new PDO('mysql:host=' . $_ENV['phpunit']['host'] . ';dbname=' . $_ENV['phpunit']['database'], $_ENV['phpunit']['username'], $_ENV['phpunit']['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$sql = new Sql($pdo,[
    'primaryColumn'=>'id',
    'tablename'=>'tablename',
    'defaultFetchType'=>PDO::FETCH_ASSOC,
    'fetchClass'=>'',
]);

$query = $sql->select('name,age')->from('tablename')->where('id','=',1)->build();

print_r($query);
echo PHP_EOL;
print_r($sql->boundValues());

$query = $sql->insert()->into('tablename')->set(['name'=>'Johnny Appleseed','age'=>23])->build();

print_r($query);
echo PHP_EOL;
print_r($sql->boundValues());


$query = $sql->update()->table('tablename')->set(['name'=>'Johnny Appleseed','age'=>23])->where('id','=',1)->build();

print_r($query);
echo PHP_EOL;
print_r($sql->boundValues());

$query = $sql->delete()->from('tablename')->where('id','=',3)->build();

print_r($query);
echo PHP_EOL;
print_r($sql->boundValues());


$query = $sql->select('*')->from('tablename')->wherePrimary(3)->build();

print_r($query);
echo PHP_EOL;
print_r($sql->boundValues());

$query = $sql->select(['id','fname','tablename.lname as lastname','age'])->from()->wherePrimary(3)->build();

print_r($query);
echo PHP_EOL;
print_r($sql->boundValues());

$query = $sql
    ->select(['id','fname','tablename.lname as lastname','age'])
    ->from()
    ->groupStart()
    ->where('id','=',4)
    ->and()
    ->where('last','first')
    ->groupEnd()
    ->or()
    ->where('last','>','first')
    ->build();

print_r($query);
echo PHP_EOL;
print_r($sql->boundValues());

echo '---------'.PHP_EOL;

$record = $sql->select('*')->from('main')->wherePrimary(1)->run()->row();

print_r($sql->hasError());
print_r($sql->errors());
print_r($sql->getLast());
print_r($record);

echo '---------'.PHP_EOL;

$records = $sql->select('*')->from('main')->innerJoin('join','main.id','join.parent_id')->wherePrimary(1)->run()->rows();

print_r($sql->hasError());
print_r($sql->errors());
print_r($sql->getLast());
print_r($records);

echo '---------'.PHP_EOL;

$records = $sql->select('first_name')->from('main')->innerJoin('join','main.id','join.parent_id')->wherePrimary(1)->run()->column();

print_r($sql->hasError());
print_r($sql->errors());
print_r($sql->getLast());
print_r($records);

