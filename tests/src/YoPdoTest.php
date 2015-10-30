<?php

namespace Lstr\YoPdo;

use PDOException;
use PHPUnit_Framework_TestCase;

class YoPdoTest extends PHPUnit_Framework_TestCase
{
    public function testPdoConnectionCanBeRetrieved()
    {
        $db = new YoPdo($this->getConfig());

        $pdo = $db->getPdo();
        $this->assertInstanceOf('PDO', $pdo);
        $this->assertSame($pdo, $db->getPdo());
    }

    /**
     * @dataProvider dbProvider
     * @expectedException PDOException
     */
    public function testAnErrorInAQueryThrowsAnException($db)
    {
        $db->query('SELECT oops');
    }

    /**
     * @dataProvider dbProvider
     */
    public function testASimpleQueryCanBeRan($db)
    {
        $sql = <<<SQL
SELECT :param_a AS col UNION
SELECT :param_b AS col UNION
SELECT :last_param AS col
ORDER BY col
SQL;

        $result = $db->query($sql, array(
            'param_a'    => 1,
            'param_b'    => 2,
            'last_param' => 3,
        ));
        $count = 1;
        while ($row = $result->fetch()) {
            $this->assertEquals($count, $row['col']);
            ++$count;
        }
    }

    /**
     * @dataProvider dbProvider
     */
    public function testMultipleQueriesCanBeRan($db)
    {
        $table_name = $this->createTable($db);
        $sql = <<<SQL
INSERT INTO {$table_name} (a, b) VALUES (:row_1_col_a, :row_1_col_b);
INSERT INTO {$table_name} (a, b) VALUES (:row_2_col_a, :row_2_col_b);
INSERT INTO {$table_name} (a, b) VALUES (:last_row_col_a, :last_row_col_b);
SQL;

        $params = array(
            'row_1_col_a'    => 20,
            'row_1_col_b'    => 40,
            'row_2_col_a'    => 60,
            'row_2_col_b'    => 30,
            'last_row_col_a' => 50,
            'last_row_col_b' => 10,
        );
        $db->queryMultiple($sql, $params);

        $this->assertResults($db, $table_name, array(
            1 => array('a' => $params['row_1_col_a'], 'b' => $params['row_1_col_b']),
            2 => array('a' => $params['row_2_col_a'],'b' => $params['row_2_col_b']),
            3 => array('a' => $params['last_row_col_a'], 'b' => $params['last_row_col_b']),
        ));
    }

    /**
     * @dataProvider dbProvider
     */
    public function testInsert($db)
    {
        $rows = array(
            1 => array('a' => 3, 'b' => 6),
            2 => array('a' => 2, 'b' => 4),
            3 => array('a' => 1, 'b' => 2),
        );

        $table_name = $this->createTable($db);
        foreach ($rows as $row) {
            $db->insert($table_name, $row);
        }

        $this->assertResults($db, $table_name, $rows);
    }

    /**
     * @dataProvider dbProvider
     */
    public function testLastInsertIdCanBeRetrieved($db)
    {
        $table_name = $this->createTable($db);
        for ($i = 1; $i <= 3; $i++) {
            $db->insert($table_name, array('a' => $i + 5, 'b' => $i + 10));
            $this->assertEquals($i, $db->getLastInsertId("{$table_name}_id_seq"));
        }
    }

    /**
     * @dataProvider dbProvider
     */
    public function testUpdateRecordsUsingCustomPlaceholderNames($db)
    {
        $rows = array(
            1 => array('a' => 4, 'b' => 7),
            2 => array('a' => 5, 'b' => 8),
            3 => array('a' => 6, 'b' => 9),
        );
        $table_name = $this->createPopulatedTable($db, $rows);

        $expected = $rows;
        $expected[2] = array('a' => 112, 'b' => 112);

        $db->update(
            $table_name,
            array('a' => 'some_number', 'b' => 'some_number'),
            'id = 2',
            array('some_number' => 112)
        );

        $this->assertResults($db, $table_name, $expected);
    }

    /**
     * @dataProvider dbProvider
     */
    public function testUpdateRecordsUsingColumnNamesAsPlaceholderNames($db)
    {
        $rows = array(
            1 => array('a' => 4, 'b' => 7),
            2 => array('a' => 5, 'b' => 8),
            3 => array('a' => 6, 'b' => 9),
        );
        $table_name = $this->createPopulatedTable($db, $rows);

        $expected = $rows;
        $expected[2] = array('a' => 102, 'b' => 120);

        $db->update(
            $table_name,
            array('a', 'b'),
            'id = 2',
            $expected[2]
        );

        $this->assertResults($db, $table_name, $expected);
    }

    /**
     * @return array
     */
    public function dbProvider()
    {
        $db = new YoPdo($this->getConfig());

        return array(
            array($db),
        );
    }

    /**
     * @return array
     */
    private function getConfig()
    {
        $config = require __DIR__ . '/../config/config.php';

        return $config['database'];
    }

    /**
     * @param YoPdo $db
     * @return string
     */
    private function createTable(YoPdo $db)
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
        $db->queryMultiple($sql);

        return $table_name;
    }

    /**
     * @param YoPdo $db
     * @param $table_name
     * @param array $rows
     */
    private function populateTable(YoPdo $db, $table_name, array $rows)
    {
        foreach ($rows as $row) {
            $db->insert($table_name, $row);
        }
    }

    /**
     * @param YoPdo $db
     * @param array $rows
     * @return string
     */
    private function createPopulatedTable(YoPdo $db, array $rows)
    {
        $table_name = $this->createTable($db);
        $this->populateTable($db, $table_name, $rows);

        return $table_name;
    }

    /**
     * @param YoPdo $db
     * @param string $table_name
     * @param array $expected_results
     * @return array
     */
    private function assertResults(YoPdo $db, $table_name, array $expected_results)
    {
        $sql = <<<SQL
SELECT id, a, b
FROM {$table_name}
ORDER BY id
SQL;
        $result = $db->query($sql);
        while ($row = $result->fetch()) {
            if (!array_key_exists('id', $row)) {
                $this->assertTrue(false, "Field 'id' not found in row.");
            } else if (!array_key_exists($row['id'], $expected_results)) {
                $this->assertTrue(false, "Row with key '{$row['id']}' not found in expected results.");
            } else {
                $expected_result = $expected_results[$row['id']];
                $expected_result['id'] = $row['id'];
                $this->assertEquals($expected_result, $row);
            }
        }
    }
}
