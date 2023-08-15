<?php

declare(strict_types=1);

use dmyers\orange\Input;
use dmyers\orange\Console;
use dmyers\orange\interfaces\ConsoleInterface;
use dmyers\orange\exceptions\ExitException;

final class ConsoleTest extends unitTestHelper
{
    protected $instance;

    protected function setUp(): void
    {
        $this->instance = new Console([
            'simulate' => true,
            'Linefeed Character' => 'EOL',
            'List Format' => '<dark>[<primary>%key%<dark>] %value%',
            'named' => [
                'primary' => '0;34',
                'secondary' => '1;34',

                'success' => '0;32',
                'danger' => '1:37,41',
                'warning' => '1;33',
                'info' => '1;36',

                'light' => '1;37',
                'dark' => '0;37',
            ],
            'icons' => [
                'success' => '✔',
                'danger' => '✘',
                'warning' => '❖',
                'info' => '➜',
            ],

            'icons' => [
                'success' => '✔',
                'danger' => '✘',
                'warning' => '❖',
                'info' => '➜',
            ],
        ], new Input([
            'server' => [
                'argc' => 4,
                'argv' => ['thisscript', 'abc', '-color', 'red'],
            ],
            'convert keys to' => 'lowercase',
            'valid input keys' => ['post', 'get', 'request', 'server', 'file', 'raw', 'cookie'],
        ]));
    }

    protected function tearDown(): void
    {
    }

    /* Tests */

    /* public */
    public function testEcho1(): void
    {
        $this->instance->echo('This is a test');

        $this->assertEquals('This is a testEOL', $this->getPrivatePublic('stdout'));
    }

    public function testEcho2(): void
    {
        $this->instance->echo('This is a test', false);

        $this->assertEquals('This is a test', $this->getPrivatePublic('stdout'));
    }

    public function testError(): void
    {
        $this->instance->error('This is a test');

        $this->assertEquals('%1B%5B1%3A37m%1B%5B41m%E2%9C%98+This+is+a+test%1B%5B0mEOL', urlencode($this->getPrivatePublic('stderr')));
    }

    public function testSuccess(): void
    {
        $this->instance->success('This is a test');

        $this->assertEquals('%1B%5B0%3B32m%E2%9C%94+This+is+a+test%1B%5B0mEOL', urlencode($this->getPrivatePublic('stdout')));
    }

    public function testInfo(): void
    {
        $this->instance->info('This is a test');

        $this->assertEquals('%1B%5B1%3B36m%E2%9E%9C+This+is+a+test%1B%5B0mEOL', urlencode($this->getPrivatePublic('stdout')));
    }

    public function testWarning(): void
    {
        $this->instance->warning('This is a test');

        $this->assertEquals('%1B%5B1%3B33m%E2%9D%96+This+is+a+test%1B%5B0mEOL', urlencode($this->getPrivatePublic('stdout')));
    }

    public function testStop(): void
    {
        $this->expectException(ExitException::class);
        $this->expectExceptionMessage('exit(1)');

        $this->instance->stop('This is a test');
    }

    public function testBell(): void
    {
        $this->instance->bell(4);

        $this->assertEquals('%07%07%07%07', urlencode($this->getPrivatePublic('stdout')));
    }

    public function testLine1(): void
    {
        $this->instance->line(8, '.');

        $this->assertEquals('........EOL', $this->getPrivatePublic('stdout'));
    }

    public function testLine2(): void
    {
        $this->instance->line(8);

        $this->assertEquals('--------EOL', $this->getPrivatePublic('stdout'));
    }

    public function testLine3(): void
    {
        $this->instance->line();

        $this->assertEquals('tput cols', $this->getPrivatePublic('system'));
        $this->assertEquals('EOL', $this->getPrivatePublic('stdout'));
    }

    public function testLine4(): void
    {
        $this->instance->line(8, '-+');

        $this->assertEquals('-+-+-+-+EOL', $this->getPrivatePublic('stdout'));
    }

    public function testClear(): void
    {
        $this->instance->clear();

        $this->assertEquals('clear', $this->getPrivatePublic('system'));
    }

    public function testLinefeed1(): void
    {
        $this->instance->linefeed();

        $this->assertEquals('EOL', $this->getPrivatePublic('stdout'));
    }

    public function testLinefeed2(): void
    {
        $this->instance->linefeed(8);

        $this->assertEquals('EOLEOLEOLEOLEOLEOLEOLEOL', $this->getPrivatePublic('stdout'));
    }

    public function testTable(): void
    {
        $table = [
            ['name', 'age'],
            ['Johnny', 21],
            ['Jenny', 28],
        ];

        $this->instance->table($table);

        $this->assertEquals('-------------------EOL| name    | age  |EOLEOL-------------------EOL| Johnny  | 21   |EOLEOL| Jenny   | 28   |EOLEOL-------------------EOL', $this->getPrivatePublic('stdout'));
    }

    public function testList(): void
    {
        $this->assertInstanceOf(ConsoleInterface::class, $this->instance->list([1 => 'red', 2 => 'blue', 3 => 'green']));

        $this->assertEquals('%1B%5B0%3B37m%5B%1B%5B0%3B34m1%1B%5B0%3B37m%5D+red%1B%5B0mEOL%1B%5B0%3B37m%5B%1B%5B0%3B34m2%1B%5B0%3B37m%5D+blue%1B%5B0mEOL%1B%5B0%3B37m%5B%1B%5B0%3B34m3%1B%5B0%3B37m%5D+green%1B%5B0mEOL', urlencode($this->getPrivatePublic('stdout')));
    }

    public function testGetLine(): void
    {
        $this->setPrivatePublic('stdin', 'Johnny Appleseed');

        $this->assertEquals('Johnny Appleseed', $this->instance->getLine('Get Line'));

        $this->assertEquals('Get LineEOL', $this->getPrivatePublic('stdout'));
    }

    public function testGetLineOneOf(): void
    {
        $this->setPrivatePublic('stdin', 'red');

        $this->assertEquals('red', $this->instance->getLineOneOf('Get Line', ['red', 'blue', 'green']));

        $this->assertEquals('Get LineEOL', $this->getPrivatePublic('stdout'));
    }

    public function testGet(): void
    {
        $this->setPrivatePublic('stdin', 'a');

        $this->assertEquals('a', $this->instance->get('Get Char'));

        $this->assertEquals('Get CharEOL', $this->getPrivatePublic('stdout'));
    }

    public function testGetOneOf(): void
    {
        $this->setPrivatePublic('stdin', '2');

        $this->assertEquals('2', $this->instance->get('Get Char', [1 => 'red', 2 => 'blue', 3 => 'green']));

        $this->assertEquals('Get CharEOL', $this->getPrivatePublic('stdout'));
    }

    public function testMinimumArguments(): void
    {
        $this->assertInstanceOf(ConsoleInterface::class, $this->instance->minimumArguments(1));

        $this->expectException(ExitException::class);
        $this->expectExceptionMessage('exit(1)');

        $this->instance->minimumArguments(99);
    }

    public function testGetArgument(): void
    {
        $this->assertEquals('abc', $this->instance->getArgument(1));
        $this->assertEquals('-color', $this->instance->getArgument(2));
        $this->assertEquals('red', $this->instance->getArgument(3));

        $this->expectException(ExitException::class);
        $this->expectExceptionMessage('exit(1)');

        $this->instance->getArgument(4);
    }

    public function testGetLastArgument(): void
    {
        $this->assertEquals('red', $this->instance->getLastArgument());
    }

    public function testGetArgumentByOption(): void
    {
        $this->assertEquals('red', $this->instance->getArgumentByOption('-color'));
    }
}
