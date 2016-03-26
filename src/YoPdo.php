<?php

namespace Lstr\YoPdo;

use Generator;
use PDO;
use PDOStatement;

class YoPdo
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    public function query($sql, array $params = array())
    {
        return $this->queryWithOptions($sql, $params);
    }

    /**
     * @param string $sql
     * @param array $params
     * @return Generator
     */
    public function getSelectRowGenerator($sql, array $params = array())
    {
        $result = $this->query($sql, $params);
        while ($row = $result->fetch()) {
            yield $row;
        }
    }

    /**
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    public function queryMultiple($sql, array $params = array())
    {
        // turn on query emulation so multiple queries can be ran
        $options = array(
            PDO::ATTR_EMULATE_PREPARES => true,
        );

        return $this->queryWithOptions($sql, $params, $options);
    }

    /**
     * @param string $sequence_table
     * @return string
     */
    public function getLastInsertId($sequence_table = null)
    {
        return $this->getPdo()->lastInsertId($sequence_table);
    }

    /**
     * @param string $tablename
     * @param array $values
     */
    public function insert($tablename, array $values)
    {
        $columns      = array();
        $placeholders = array();
        foreach ($values as $column => $value) {
            $columns[]      = $column;
            $placeholders[] = ":{$column}";
        }

        $column_sql      = implode(",\n", $columns);
        $placeholder_sql = implode(",\n", $placeholders);

        $this->query(
            "
                INSERT INTO {$tablename}
                (
                    {$column_sql}
                )
                VALUES
                (
                    {$placeholder_sql}
                )
            ",
            $values
        );
    }

    /**
     * @param string $tablename
     * @param array $set_cols
     * @param string $where_sql
     * @param array $values
     */
    public function update($tablename, array $set_cols, $where_sql, array $values)
    {
        $sets = array();
        foreach ($set_cols as $column => $placeholder) {
            if (is_numeric($column)) {
                $column = $placeholder;
            }
            $sets[] = "{$column} = :{$placeholder}";
        }

        $set_sql = implode(",\n", $sets);

        $this->query(
            "
                UPDATE {$tablename}
                SET {$set_sql}
                WHERE {$where_sql}
            ",
            $values
        );
    }

    /**
     * @param string $tablename
     * @param string $where_sql
     * @param array $values
     */
    public function delete($tablename, $where_sql, array $values)
    {
        $this->query(
            "
                DELETE FROM {$tablename}
                WHERE {$where_sql}
            ",
            $values
        );
    }

    /**
     * @param string $table_name
     * @param array $columns
     * @param int $max_buffer_size
     * @return BulkInserter
     */
    public function getBulkInserter($table_name, array $columns, $max_buffer_size = 250)
    {
        return new BulkInserter($this, $table_name, $columns, $max_buffer_size);
    }

    /**
     * @param string $sql
     * @param array $params
     * @param array $options
     * @return PDOStatement
     */
    private function queryWithOptions($sql, array $params = array(), array $options = array())
    {
        $pdo   = $this->getPdo();
        $query = $pdo->prepare($sql, $options);
        $query->execute($params);

        return $query;
    }
}
