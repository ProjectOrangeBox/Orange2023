declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class <?=$className ?>Test extends TestCase
{
    private $instance;
    
    protected function setUp(): void
    {
        $this->instance = null; #change
        
        fwrite(STDOUT, __METHOD__ . "\n");
    }
    
    protected function tearDown(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
    }

    // Tests
<?=$methodsText ?>
}
