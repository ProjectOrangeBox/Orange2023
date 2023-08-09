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

    /* support for private / protected properties and methods */

    private function getPrivatePublic($attribute)
    {
        $getter = function () use ($attribute) {
            return $this->$attribute;
        };

        $closure = \Closure::bind($getter, $this->instance, get_class($this->instance));

        return $closure();
    }

    private function setPrivatePublic($attribute, $value)
    {
        $setter = function ($value) use ($attribute) {
            $this->$attribute = $value;
        };

        $closure = \Closure::bind($setter, $this->instance, get_class($this->instance));

        $closure($value);
    }

    private function callMethod(string $method, array $args = null)
    {
        $reflectionMethod = new ReflectionMethod($this->instance, $method);

        return (is_array($args)) ? $reflectionMethod->invokeArgs($this->instance, $args) : $reflectionMethod->invoke($this->instance);
    }
}
