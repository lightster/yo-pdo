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
     * @var Transaction
     */
    private $transaction;

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
     * @return Transaction
     */
    public function transaction()
    {
        if ($this->transaction) {
            return $this->transaction;
        }

        $this->transaction = new Transaction($this);

        return $this->transaction;
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
        $columns = $expressions = $processed_values = [];
        array_walk($values, function ($value, $column) use (&$columns, &$expressions, &$processed_values) {
            $columns[] = $column;

            if ($value instanceof ExpressionValue) {
                $expressions[] = $value->getString();
                return;
            }

            $expressions[] = ":{$column}";
            $processed_values[] = $value;
        });

        $column_sql      = implode(",\n", $columns);
        $expression_sql = implode(",\n", $expressions);

        $this->query(
            "
                INSERT INTO {$tablename}
                (
                    {$column_sql}
                )
                VALUES
                (
                    {$expression_sql}
                )
            ",
            $processed_values
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
        $sets = $processed_values = array();
        array_walk($set_cols, function ($placeholder, $column) use ($values, &$sets, &$processed_values) {
            if (is_numeric($column)) {
                $column = $placeholder;
            }

            if (isset($values[$placeholder]) && $values[$placeholder] instanceof ExpressionValue) {
                $value = $values[$placeholder]->getString();
                $sets[] = "{$column} = {$value}";
                return;
            }

            $sets[] = "{$column} = :{$placeholder}";
            $processed_values[$placeholder] = $values[$placeholder];
        });

        $set_sql = implode(",\n", $sets);

        $this->query(
            "
                UPDATE {$tablename}
                SET {$set_sql}
                WHERE {$where_sql}
            ",
            $processed_values
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
