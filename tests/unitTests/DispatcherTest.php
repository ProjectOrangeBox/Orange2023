<?php

declare(strict_types=1);

namespace unitTests;

use PHPUnit\Framework\TestCase;

final class DispatcherTest extends TestCase
{
    public static function setUpBeforeClass(): void
	{
        define('__ROOT__', realpath(__DIR__ . '/../../'));
        define('__WWW__', realpath(__DIR__.'/../../htdocs'));

		$_ENV = array_replace_recursive($_ENV, parse_ini_file(__ROOT__.'/.unit.env', true, INI_SCANNER_TYPED));

        fwrite(STDOUT, __METHOD__ . "\n");
    }

    protected function setUp(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
    }

    public function testCall(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        $this->assertTrue(true);
    }

    protected function assertPreConditions(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
    }

    protected function assertPostConditions(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
    }

    protected function tearDown(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
    }

    public static function tearDownAfterClass(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
    }

    protected function onNotSuccessfulTest(\Throwable $t): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        throw $t;
    }
}
