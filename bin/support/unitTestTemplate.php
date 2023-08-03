declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class {{classname}}Test extends TestCase
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
