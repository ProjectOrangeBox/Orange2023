<?php

declare(strict_types=1);

use peels\asset\Asset;
use orange\framework\Data;
use peels\asset\Priority;

final class AssetTest extends \unitTestHelper
{
    protected $instance;
    private $dataInstance;

    protected function setUp(): void
    {
        $config = [
            'script attributes' => ['src' => '', 'type' => 'text/javascript', 'charset' => 'utf-8'],
            'link attributes' => ['href' => '', 'type' => 'text/css', 'rel' => 'stylesheet'],
            'inject' => [],
            'page min' => false,
            'data variable name' => [
                'js variables' => 'js_variable',
                'body class' => 'body_class',
                'link' => 'link',
                'script' => 'script',
            ],
            'trim' => ['body_class', 'link', 'script', 'js_variable'],
        ];

        $this->dataInstance = new Data();

        $this->instance = new Asset($config, $this->dataInstance);
    }

    // Tests
    public function testhas(): void
    {
        $this->instance->asset_name('<h1>Hello World!</h1>');

        $this->assertTrue($this->instance->has('asset_name'));
    }

    public function testget(): void
    {
        $this->instance->asset_name('<h1>Hello World!</h1>');
        $this->instance->asset_name('<h2>Goodbye!</h2>');

        $this->assertEquals('<h1>Hello World!</h1><h2>Goodbye!</h2>', $this->instance->get('asset_name'));
    }

    public function testlinkHTML(): void
    {
        $this->assertEquals('<link href="/assets/foobar.css" type="text/css" rel="stylesheet"/>', $this->instance->linkHTML('/assets/foobar.css'));
    }

    public function testscriptHTML(): void
    {
        $this->assertEquals('<script src="/assets/foobar.js" type="text/javascript" charset="utf-8"></script>', $this->instance->scriptHTML('/assets/foobar.js'));
    }

    public function testelementHTML(): void
    {
        $this->assertEquals('<h1 class="heading" id="45" data-open="true">Hello</h1>', $this->instance->elementHTML('h1', ['class' => 'heading', 'id' => '45'], 'Hello', ['open' => 'true']));
    }

    public function testscriptFile(): void
    {
        $this->instance->scriptFile('/assets/app.js');

        $this->assertEquals('<script src="/assets/app.js" type="text/javascript" charset="utf-8"></script>', $this->instance->get('script'));

        $this->instance->scriptFile('/assets/more.js');

        $this->assertEquals('<script src="/assets/app.js" type="text/javascript" charset="utf-8"></script><script src="/assets/more.js" type="text/javascript" charset="utf-8"></script>', $this->instance->get('script'));
    }

    public function testscriptFiles(): void
    {
        $this->instance->scriptFiles(['/assets/app.js', '/assets/more.js']);

        $this->assertEquals('<script src="/assets/app.js" type="text/javascript" charset="utf-8"></script><script src="/assets/more.js" type="text/javascript" charset="utf-8"></script>', $this->instance->get('script'));
    }

    public function testlinkFile(): void
    {
        $this->instance->linkFile('/assets/app.css');

        $this->assertEquals('<link href="/assets/app.css" type="text/css" rel="stylesheet"/>', $this->instance->get('link'));

        $this->instance->linkFile('/assets/more.css');

        $this->assertEquals('<link href="/assets/app.css" type="text/css" rel="stylesheet"/><link href="/assets/more.css" type="text/css" rel="stylesheet"/>', $this->instance->get('link'));
    }

    public function testlinkFileOrder(): void
    {
        $this->instance->linkFile('/assets/a.css', Priority::LATEST);
        $this->instance->linkFile('/assets/b.css', Priority::EARLY);
        $this->instance->linkFile('/assets/c.css', Priority::LATE);
        $this->instance->linkFile('/assets/d.css', Priority::EARLY);

        $this->assertEquals('<link href="/assets/b.css" type="text/css" rel="stylesheet"/><link href="/assets/d.css" type="text/css" rel="stylesheet"/><link href="/assets/c.css" type="text/css" rel="stylesheet"/><link href="/assets/a.css" type="text/css" rel="stylesheet"/>', $this->instance->get('link'));
    }

    public function testlinkFiles(): void
    {
        $this->instance->linkFiles(['/assets/app.js', '/assets/more.js']);

        $this->assertEquals('<link href="/assets/app.js" type="text/css" rel="stylesheet"/><link href="/assets/more.js" type="text/css" rel="stylesheet"/>', $this->instance->get('link'));
    }

    public function testjavascriptVariable(): void
    {
        $this->instance->javascriptVariable('name', 'Johnny Appleseed');

        $this->assertEquals('var name="Johnny Appleseed";', $this->instance->get('js_variable'));
    }

    public function testjavascriptVariableArray(): void
    {
        $this->instance->javascriptVariable('keys', ['id' => '123', 'reg' => 'xyz']);

        $this->assertEquals('var keys={"id":"123","reg":"xyz"};', $this->instance->get('js_variable'));
    }

    public function testjavascriptVariables(): void
    {
        $this->instance->javascriptVariables(['name' => 'Johnny Appleseed', 'id' => '123']);

        $this->assertEquals('var name="Johnny Appleseed";var id="123";', $this->instance->get('js_variable'));
    }

    public function testjavascriptVariablesOrder(): void
    {
        $this->instance->javascriptVariable('name', 'Jane Appleseed', Priority::LATE);
        $this->instance->javascriptVariable('name', 'Johnny Appleseed', Priority::LATEST);
        $this->instance->javascriptVariable('name', 'Joe Appleseed', Priority::EARLIEST);

        $this->assertEquals('var name="Joe Appleseed";var name="Jane Appleseed";var name="Johnny Appleseed";', $this->instance->get('js_variable'));
    }

    public function testbodyClass(): void
    {
        $this->instance->bodyClass('main loop content');

        $this->assertEquals('main loop content', $this->instance->get('body_class'));

        $this->instance->bodyClass('hook');

        $this->assertEquals('main loop content hook', $this->instance->get('body_class'));

        // we aren't testing the data object here but let's make sure it was added to the data object
        $this->assertEquals('main loop content hook', $this->dataInstance['body_class']);
    }
}
