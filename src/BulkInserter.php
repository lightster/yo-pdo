<?php

namespace Lstr\YoPdo;

use Lstr\YoPdo\Exception\BufferNotEmptiedException;
use Lstr\YoPdo\Exception\ValueCountMismatchException;

class BulkInserter
{
    /**
     * @var YoPdo
     */
    private $yo_pdo;

    /**
     * @var string
     */
    private $table_name;

    /**
     * @var array
     */
    private $columns;

    /**
     * @var int
     */
    private $column_count;

    /**
     * @var int
     */
    private $max_buffer_size;

    /**
     * @var array
     */
    private $records = [];

    /**
     * @param YoPdo $yo_pdo
     * @param string $table_name
     * @param array $columns
     * @param int $max_buffer_size
     */
    public function __construct(YoPdo $yo_pdo, $table_name, array $columns, $max_buffer_size = 250)
    {
        $this->yo_pdo = $yo_pdo;
        $this->table_name = $table_name;
        $this->columns = $columns;
        $this->column_count = count($columns);
        $this->max_buffer_size = $max_buffer_size;
    }

    public function __destruct()
    {
        if (count($this->records)) {
            throw new BufferNotEmptiedException($this->table_name, $this->records);
        }
    }

    public function destroyBuffer()
    {
        $this->records = [];
    }

    /**
     * @param array $record
     */
    public function bufferRecord(array $record)
    {
        $this->bufferRecords([$record]);
    }

    /**
     * @param array $records
     */
    public function bufferRecords(array $records)
    {
        foreach ($records as $record) {
            if (count($record) !== $this->column_count) {
                throw new ValueCountMismatchException(
                    $this->table_name,
                    $this->columns,
                    $record
                );
            }

            $this->records[] = $record;

            if (count($this->records) >= $this->max_buffer_size) {
                $this->insertRecords();
            }
        }
    }

    /**
     * @param array $additional_records
     */
    public function insertRecords(array $additional_records = [])
    {
        $this->bufferRecords($additional_records);

        if (!count($this->records)) {
            return;
        }

        $table_name = $this->quoteIdentifiers([$this->table_name]);
        $columns = $this->quoteIdentifiers($this->columns);
        $placeholders = $this->getRecordLines(count($this->records));
        $flattened_records = $this->flattenRecords($this->records);

        $sql = <<<SQL
INSERT INTO {$table_name}
($columns)
VALUES
{$placeholders}
SQL;
        $this->yo_pdo->query($sql, $flattened_records);

        $this->records = [];
    }

    /**
     * @param array $identifiers
     * @return string
     */
    private function quoteIdentifiers(array $identifiers)
    {
        $quoted_identifiers = [];
        foreach ($identifiers as $identifier) {
            $quoted_identifiers[] = '"' . str_replace('"', '""', $identifier) . '"';
        }

        return implode(', ', $quoted_identifiers);
    }

    /**
     * @param array $records
     * @return array
     */
    private function flattenRecords(array $records)
    {
        $flattened_records = [];
        foreach ($records as $record) {
            $flattened_records = array_merge($flattened_records, $record);
        }

        return $flattened_records;
    }

    /**
     * @param int $record_count
     * @return string
     */
    private function getRecordLines($record_count)
    {
        $record_placeholder = $this->getRecordPlaceholder();
        return implode(', ', array_fill(0, $record_count, $record_placeholder));
    }

    /**
     * @return string
     */
    private function getRecordPlaceholder()
    {
        return '(' . implode(',', array_fill(0, $this->column_count, '?')) . ')';
    }
}
