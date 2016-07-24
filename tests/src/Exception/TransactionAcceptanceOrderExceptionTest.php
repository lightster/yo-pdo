<?php

namespace Lstr\YoPdo\Exception;

use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass \Lstr\YoPdo\Exception\TransactionAcceptanceOrderException
 */
class TransactionAcceptanceOrderExceptionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @expectedException Lstr\YoPdo\Exception\TransactionAcceptanceOrderException
     */
    public function testExceptionIsThrowable()
    {
        throw new TransactionAcceptanceOrderException('expected', 'actual');
    }

    /**
     * @covers ::__construct
     * @covers ::getExpectedTransactionName
     */
    public function testExpectedTransactionNameIsRetrievable()
    {
        $expected = 'expected_' . uniqid();
        $actual = 'actual_' . uniqid();
        $exception = new TransactionAcceptanceOrderException($expected, $actual);

        $this->assertEquals($expected, $exception->getExpectedTransactionName());
    }

    /**
     * @covers ::__construct
     * @covers ::getActualTransactionName
     */
    public function testActualTransactionNameIsRetrievable()
    {
        $expected = 'expected_' . uniqid();
        $actual = 'actual_' . uniqid();
        $exception = new TransactionAcceptanceOrderException($expected, $actual);

        $this->assertEquals($actual, $exception->getActualTransactionName());
    }
}
