<?php

namespace Lstr\YoPdo\Exception;

use RuntimeException;

class ValueCountMismatchException extends RuntimeException
{
    /**
     * @var string
     */
    private $table_name;

    /**
     * @var array
     */
    private $columns;

    /**
     * @var array
     */
    private $record;

    public function __construct($table_name, array $columns, array $record)
    {
        parent::__construct(
            "The bulk inserter '{$table_name} was destroyed without emptying the buffer."
        );

        $this->table_name = $table_name;
        $this->columns = $columns;
        $this->record = $record;
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
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return array
     */
    public function getRecord()
    {
        return $this->record;
    }
}
