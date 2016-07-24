<?php

namespace Lstr\YoPdo\Exception;

use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass \Lstr\YoPdo\Exception\DuplicateTransactionNameException
 */
class DuplicateTransactionNameExceptionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @expectedException Lstr\YoPdo\Exception\DuplicateTransactionNameException
     */
    public function testExceptionIsThrowable()
    {
        throw new DuplicateTransactionNameException('duplicate');
    }

    /**
     * @covers ::__construct
     * @covers ::getTransactionName
     */
    public function testTransactionNameIsRetrievable()
    {
        $transaction_name = 'duplicate_' . uniqid();
        $exception = new DuplicateTransactionNameException($transaction_name);

        $this->assertEquals($transaction_name, $exception->getTransactionName());
    }
}
