<?php

namespace Lstr\YoPdo;

use Lstr\YoPdo\Exception\ValueCountMismatchException;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass \Lstr\YoPdo\Exception\ValueCountMismatchException
 */
class ValueCountMismatchExceptionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @expectedException Lstr\YoPdo\Exception\ValueCountMismatchException
     */
    public function testExceptionIsThrowable()
    {
        throw new ValueCountMismatchException(
            'some_table',
            ['a', 'b', 'z'],
            ['1', 2, true]
        );
    }

    /**
     * @covers ::__construct
     * @covers ::getTableName
     */
    public function testTableNameIsRetrievable()
    {
        $expected_table_name = 'some_table_' . uniqid();
        $exception = new ValueCountMismatchException(
            $expected_table_name,
            ['a', 'b', 'z'],
            ['1', 2, true]
        );

        $this->assertEquals($expected_table_name, $exception->getTableName());
    }

    /**
     * @covers ::__construct
     * @covers ::getColumns
     */
    public function testColumnsRetrievable()
    {
        $expected_columns = ['a', 'b', 'z'];
        $exception = new ValueCountMismatchException(
            'some_table',
            $expected_columns,
            ['1', 2, true]
        );

        $this->assertEquals($expected_columns, $exception->getColumns());
    }

    /**
     * @covers ::__construct
     * @covers ::getRecord
     */
    public function testRecordIsRetrievable()
    {
        $expected_record = [
            ['a', true, uniqid()],
        ];
        $exception = new ValueCountMismatchException(
            'some_table',
            ['a', 'b', 'z'],
            $expected_record
        );

        $this->assertEquals($expected_record, $exception->getRecord());
    }
}
