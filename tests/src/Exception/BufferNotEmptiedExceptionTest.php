<?php

namespace Lstr\YoPdo;

use Lstr\YoPdo\Exception\BufferNotEmptiedException;
use PHPUnit_Framework_TestCase;

class BufferNotEmptiedExceptionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Lstr\YoPdo\Exception\BufferNotEmptiedException
     */
    public function testExceptionIsThrowable()
    {
        throw new BufferNotEmptiedException("some_table", [['a', '1', true]]);
    }

    public function testTableNameIsRetrievable()
    {
        $expected_table_name = 'some_table_' . uniqid();
        $exception = new BufferNotEmptiedException(
            $expected_table_name,
            [['a', '1', true]]
        );

        $this->assertEquals($expected_table_name, $exception->getTableName());
    }

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
