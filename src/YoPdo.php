<?php

namespace Lstr\YoPdo;

use PDO;
use PDOStatement;

class YoPdo
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return PDO
     */
    public function getPdo()
    {
        if (null !== $this->pdo) {
            return $this->pdo;
        }

        $dsn      = $this->config['dsn'];
        $username = isset($this->config['username']) ? $this->config['username'] : null;
        $password = isset($this->config['password']) ? $this->config['password'] : null;
        $options  = isset($this->config['driver_options']) ? $this->config['driver_options'] : null;

        $this->pdo = new PDO($dsn, $username, $password, $options);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

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
