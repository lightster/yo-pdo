<?php

namespace Lstr\YoPdo\TestUtil;

use Lstr\YoPdo\YoPdo;

class SampleTableCreator
{
    /**
     * @param YoPdo $yo_pdo
     * @return string
     */
    public function createTable(YoPdo $yo_pdo)
    {
        $table_name = 'test_' . uniqid();
        $sql = <<<SQL
CREATE SEQUENCE {$table_name}_id_seq;
CREATE TABLE {$table_name} (
    id INT NOT NULL PRIMARY KEY DEFAULT NEXTVAL('{$table_name}_id_seq'::regclass),
    a INT NOT NULL,
    b INT NOT NULL
);
SQL;
        $yo_pdo->queryMultiple($sql);

        return $table_name;
    }

    /**
     * @param YoPdo $yo_pdo
     * @param array $rows
     * @return string
     */
    public function createPopulatedTable(YoPdo $yo_pdo, array $rows)
    {
        $table_name = $this->createTable($yo_pdo);
        $this->populateTable($yo_pdo, $table_name, $rows);

        return $table_name;
    }

    /**
     * @return array
     */
    public function getSampleRows()
    {
        return array(
            1 => array('a' => 3, 'b' => 6),
            2 => array('a' => 2, 'b' => 4),
            3 => array('a' => 1, 'b' => 2),
        );
    }

    /**
     * @param YoPdo $yo_pdo
     * @param $table_name
     * @param array $rows
     */
    private function populateTable(YoPdo $yo_pdo, $table_name, array $rows)
    {
        foreach ($rows as $row) {
            $yo_pdo->insert($table_name, $row);
        }
    }
}
