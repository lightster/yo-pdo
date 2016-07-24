<?php

namespace Lstr\YoPdo\Exception;

use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass \Lstr\YoPdo\Exception\UnknownTransactionNameException
 */
class UnknownTransactionNameExceptionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Lstr\YoPdo\Exception\UnknownTransactionNameException
     */
    public function testExceptionIsThrowable()
    {
        throw new UnknownTransactionNameException('unknown');
    }

    /**
     * @covers ::__construct
     * @covers ::getTransactionName
     */
    public function testTransactionNameIsRetrievable()
    {
        $transaction_name = 'unknown_' . uniqid();
        $exception = new UnknownTransactionNameException($transaction_name);

        $this->assertEquals($transaction_name, $exception->getTransactionName());
    }
}
