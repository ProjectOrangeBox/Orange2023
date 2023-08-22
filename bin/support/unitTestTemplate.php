declare(strict_types=1);

final class {{classname}}Test extends unitTestHelper
{
    protected $instance;
    
    protected function setUp(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
    }
    
    protected function tearDown(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
    }

    /* Public Method Tests */

    {{public}}

    /* Protected Method Tests */

    {{protected}}
}
