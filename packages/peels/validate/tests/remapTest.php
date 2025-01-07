<?php

declare(strict_types=1);

use orange\framework\Input;
use peels\validate\Remap;

final class remapTest extends \unitTestHelper
{
    private $remap;

    protected function setUp(): void
    {
        $this->remap = Remap::getInstance(Input::getInstance([]));
    }

    public function testArray(): void
    {
        $input = [
            'name' => 'Johnny Appleseed',
            'age' => ' 34 ',
        ];

        $equals = [
            'fullname' => 'Johnny Appleseed',
            'age' => '34',
            'full' => 'Johnny Appleseed 34',
        ];

        $this->assertEquals($equals, $this->remap->array($input, 'name>fullname|name>#|@trim(age)>age|@concat(fullname," ",age)>full'));

        $equals = [
            'age' => '34',
        ];

        $this->assertEquals($equals, $this->remap->array($input, 'name>#|@trim(age)>age'));

        $input = [
            'name' => 'Johnny Appleseed',
            'age' => ' 34 ',
        ];

        $equals = [
            'fullname' => 'Johnny Appleseed',
            'age' => '34',
            'full' => 'Johnny Appleseed,34',
            'more' => '(Johnny Appleseed,34)',
        ];

        $this->assertEquals($equals, $this->remap->array($input, 'name>fullname|name>#|@trim(age)>age|@concat(fullname,",",age)>full|@concat("(",fullname,",",age,")")>more'));
    }
}
