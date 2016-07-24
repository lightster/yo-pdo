<?php

namespace Lstr\YoPdo\Exception;

use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass \Lstr\YoPdo\Exception\BufferNotEmptiedException
 */
class BufferNotEmptiedExceptionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @expectedException Lstr\YoPdo\Exception\BufferNotEmptiedException
     */
    public function testExceptionIsThrowable()
    {
        throw new BufferNotEmptiedException("some_table", [['a', '1', true]]);
    }

    /**
     * @covers ::__construct
     * @covers ::getTableName
     */
    public function testTableNameIsRetrievable()
    {
        $expected_table_name = 'some_table_' . uniqid();
        $exception = new BufferNotEmptiedException(
            $expected_table_name,
            [['a', '1', true]]
        );

        $this->assertEquals($expected_table_name, $exception->getTableName());
    }

    /**
     * @covers ::__construct
     * @covers ::getRecords
     */
    public function testRecordsAreRetrievable()
    {
        $expected_records = [
            ['a', '1', true, uniqid()],
            ['b', '2', false, uniqid()]
        ];
        $exception = new BufferNotEmptiedException(
            'some_table',
            $expected_records
        );

        $this->assertEquals($expected_records, $exception->getRecords());
    }
}
