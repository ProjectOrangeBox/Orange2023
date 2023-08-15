declare(strict_types=1);

final class {{classname}}Test extends unitTestHelper
{
    private $instance;
    
    protected function setUp(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
    }
    
    protected function tearDown(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
    }

    /* Tests */
{{tests}}

}
