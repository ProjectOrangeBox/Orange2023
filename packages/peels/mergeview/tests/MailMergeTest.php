<?php

declare(strict_types=1);

use orange\framework\Data;
use peels\mergeview\Merge;

final class MailMergeTest extends unitTestHelper
{
    protected $instance;

    protected $sampleData = [];

    protected function setUp(): void
    {
        $config = [
            'view paths' => [],
            'view aliases' => [],
            'temp directory' => sys_get_temp_dir(),
            'debug' => false,
            'extension' => '.merge',
        ];

        $this->instance = new Merge($config, new Data());

        $this->instance->addPath(__DIR__ . '/support/mergeViews');

        $this->sampleData = array(
            'user'      => [
                'group' => 'user',
            ],
            'title'     => 'Lex is Awesome!',
            'name'      => 'World',
            'real_name' => array(
                'first' => 'Lex',
                'last'  => 'Luther',
            ),
            'title'     => 'Current Projects',
            'projects'  => array(
                array(
                    'name' => 'Acme Site',
                    'assignees' => array(
                        array('name' => 'Dan'),
                        array('name' => 'Phil'),
                    ),
                ),
                array(
                    'name' => 'Lex',
                    'contributors' => array(
                        array('name' => 'Dan'),
                        array('name' => 'Ziggy'),
                        array('name' => 'Jerel')
                    ),
                ),
            ),
        );
    }

    // Tests
    public function testRender(): void
    {
        $this->assertEquals('<h1>Hello World!</h1>', $this->instance->render('test', ['who' => 'World']));
    }

    public function testRenderString(): void
    {
        $this->assertEquals('<h1>Hello World!</h1>', $this->instance->renderString('<h1>Hello {{ who }}!</h1>', ['who' => 'World']));
    }

    public function testRenderTwo(): void
    {
        $match = file_get_contents(__DIR__ . '/support/mergeMatch.merge');

        $this->assertEquals($match, $this->instance->render('basic', $this->sampleData));
    }
}
