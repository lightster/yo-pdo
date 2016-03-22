<?php

namespace Lstr\YoPdo\Exception;

use RuntimeException;

class BufferNotEmptiedException extends RuntimeException
{
    /**
     * @var string
     */
    private $table_name;

    /**
     * @var array
     */
    private $records;

    /**
     * @param string $table_name
     * @param array $records
     */
    public function __construct($table_name, array $records)
    {
        parent::__construct(
            "The bulk inserter '{$table_name} was destroyed without emptying the buffer."
        );

        $this->table_name = $table_name;
        $this->records = $records;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->table_name;
    }

    /**
     * @return array
     */
    public function getRecords()
    {
        return $this->records;
    }
}
