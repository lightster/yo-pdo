<?php

namespace Lstr\YoPdo\Exception;

use PHPUnit_Framework_TestCase;

class DuplicateTransactionNameExceptionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Lstr\YoPdo\Exception\DuplicateTransactionNameException
     */
    public function testExceptionIsThrowable()
    {
        throw new DuplicateTransactionNameException('duplicate');
    }

    public function testTransactionNameIsRetrievable()
    {
        $transaction_name = 'duplicate_' . uniqid();
        $exception = new DuplicateTransactionNameException($transaction_name);

        $this->assertEquals($transaction_name, $exception->getTransactionName());
    }
}
