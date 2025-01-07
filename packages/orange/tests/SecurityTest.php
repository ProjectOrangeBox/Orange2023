<?php

declare(strict_types=1);

use orange\framework\Security;

final class SecurityTest extends unitTestHelper
{
    protected $instance;

    private $publicKeyFile = WORKINGDIR . '/writeable/public.key';
    private $privateKeyFile = WORKINGDIR . '/writeable/private.key';

    protected function setUp(): void
    {
        $this->tearDown();

        $this->instance = Security::getInstance([
            'public key' => $this->publicKeyFile,
            'private key' => $this->privateKeyFile,
        ]);

        $this->instance->createKeys();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->publicKeyFile)) {
            unlink($this->publicKeyFile);
        }
        if (file_exists($this->privateKeyFile)) {
            unlink($this->privateKeyFile);
        }
    }

    public function createKeys(): void
    {
        // created in setup
        $this->assertFileExists($this->publicKeyFile);
        $this->assertFileExists($this->privateKeyFile);
    }

    public function testVerifySig(): void
    {
        $sig = $this->instance->publicSig();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}$/', $sig);
        $this->assertTrue($this->instance->verifySig($sig));
    }

    public function testEncrypt(): void
    {
        $text = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.';

        $this->assertEquals($text, $this->instance->decrypt($this->instance->encrypt($text)));
    }

    public function testRemoveInvisibleCharacters(): void
    {
        $input = '';

        for ($c = 0; $c < 256; $c++) {
            $input .= chr($c);
        }

        $this->assertEquals(' !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~', $this->instance->removeInvisibleCharacters($input));
    }

    public function testCleanFilename(): void
    {
        $input = '';

        for ($c = 0; $c < 256; $c++) {
            $input .= chr($c);
        }

        $this->assertEquals(' ()+,-.0123456789<=>@ABCDEGHIJKLMNOPQRSTUVWXYZ[]_abcdefghijklmnopqrstuvwxyz{|}~', $this->instance->cleanFilename($input));
        $this->assertEquals('This is a test 2004-10-31 103100', $this->instance->cleanFilename('This is a test 2004-10-31 10:31:00'));
        $this->assertEquals('This is a test <2004-10-31 103100>', $this->instance->cleanFilename('This is a test <2004-10-31 10:31:00>'));
    }
}
