<?php

namespace Lstr\YoPdo\Exception;

use PHPUnit_Framework_TestCase;

class TransactionAcceptanceOrderExceptionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Lstr\YoPdo\Exception\TransactionAcceptanceOrderException
     */
    public function testExceptionIsThrowable()
    {
        throw new TransactionAcceptanceOrderException('expected', 'actual');
    }

    public function testExpectedTransactionNameIsRetrievable()
    {
        $expected = 'expected_' . uniqid();
        $actual = 'actual_' . uniqid();
        $exception = new TransactionAcceptanceOrderException($expected, $actual);

        $this->assertEquals($expected, $exception->getExpectedTransactionName());
    }

    public function testActualTransactionNameIsRetrievable()
    {
        $expected = 'expected_' . uniqid();
        $actual = 'actual_' . uniqid();
        $exception = new TransactionAcceptanceOrderException($expected, $actual);

        $this->assertEquals($actual, $exception->getActualTransactionName());
    }
}
