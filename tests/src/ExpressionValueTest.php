<?php

namespace Lstr\YoPdo;

use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass \Lstr\YoPdo\ExpressionValue
 */
class ExpressionValueTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getString
     */
    public function testValuePassedToConstructorIsSameReturned()
    {
        $value = "some_string_" . uniqid();
        $expression_value = new ExpressionValue($value);

        $this->assertSame($value, $expression_value->getString());
    }
}
