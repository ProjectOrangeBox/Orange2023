<?php

declare(strict_types=1);

use peels\disc\disc;
use PHPUnit\Framework\TestCase;

final class fileTest extends TestCase
{
    private $ini = [
        'section1' => [
            'name' => 'frank',
            'age' => 24,
        ],
        'section2' => [
            'name' => 'pete',
            'age' => 28,
        ]
    ];

    public function setUp(): void
    {
        if (!defined('__ROOT__')) {
            define('__ROOT__', realpath(__DIR__ . '/support'));
        }

        disc::root(__ROOT__);
    }

    public function tearDown(): void
    {
        disc::deleteContent(disc::directory('/working'));
    }

    public function testExportIni(): void
    {
        $file = disc::file('/local/disc/tests/test_working/test.ini');

        $this->assertEquals(57, $file->export->ini($this->ini));
    }

    public function testImportIni(): void
    {
        $ini = disc::file('/local/disc/tests/test_working/test.ini')->import->ini();

        $this->assertEquals($this->ini, $ini);
    }
}
