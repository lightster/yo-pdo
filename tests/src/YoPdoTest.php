<?php

namespace Lstr\YoPdo;

use PDO;
use PDOException;
use PHPUnit_Framework_TestCase;

class YoPdoTest extends PHPUnit_Framework_TestCase
{
    public function testPdoConnectionCanBeRetrieved()
    {
        $config = $this->getConfig();
        $pdo = new PDO($config['dsn'], $config['username'], $config['password']);
        $yo_pdo = new YoPdo($pdo);

        $this->assertSame($pdo, $yo_pdo->getPdo());
    }

    /**
     * @dataProvider dbProvider
     * @expectedException PDOException
     */
    public function testAnErrorInAQueryThrowsAnException($yo_pdo)
    {
        $yo_pdo->query('SELECT oops');
    }

    /**
     * @dataProvider dbProvider
     */
    public function testASimpleQueryCanBeRan($yo_pdo)
    {
        $sql = <<<SQL
SELECT :param_a AS col UNION
SELECT :param_b AS col UNION
SELECT :last_param AS col
ORDER BY col
SQL;

        $result = $yo_pdo->query($sql, array(
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
    public function testMultipleQueriesCanBeRan($yo_pdo)
    {
        $table_name = $this->createTable($yo_pdo);
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
        $yo_pdo->queryMultiple($sql, $params);

        $this->assertResults($yo_pdo, $table_name, array(
            1 => array('a' => $params['row_1_col_a'], 'b' => $params['row_1_col_b']),
            2 => array('a' => $params['row_2_col_a'],'b' => $params['row_2_col_b']),
            3 => array('a' => $params['last_row_col_a'], 'b' => $params['last_row_col_b']),
        ));
    }

    /**
     * @dataProvider dbProvider
     */
    public function testInsert($yo_pdo)
    {
        $rows = $this->getSampleRows();

        $table_name = $this->createTable($yo_pdo);
        foreach ($rows as $row) {
            $yo_pdo->insert($table_name, $row);
        }

        $this->assertResults($yo_pdo, $table_name, $rows);
    }

    /**
     * @dataProvider dbProvider
     */
    public function testLastInsertIdCanBeRetrieved($yo_pdo)
    {
        $table_name = $this->createTable($yo_pdo);
        for ($i = 1; $i <= 3; $i++) {
            $yo_pdo->insert($table_name, array('a' => $i + 5, 'b' => $i + 10));
            $this->assertEquals($i, $yo_pdo->getLastInsertId("{$table_name}_id_seq"));
        }
    }

    /**
     * @dataProvider dbProvider
     */
    public function testUpdateRecordsUsingCustomPlaceholderNames($yo_pdo)
    {
        $this->assertUpdated(
            $yo_pdo,
            function ($table_name, $condition) use ($yo_pdo) {
                $yo_pdo->update(
                    $table_name,
                    array('a' => 'some_number', 'b' => 'some_number'),
                    $condition,
                    array('some_number' => 112)
                );

                return array('a' => 112, 'b' => 112);
            }
        );
    }

    /**
     * @dataProvider dbProvider
     */
    public function testUpdateRecordsUsingColumnNamesAsPlaceholderNames($yo_pdo)
    {
        $this->assertUpdated(
            $yo_pdo,
            function ($table_name, $condition) use ($yo_pdo) {
                $expected = array('a' => 102, 'b' => 120);

                $yo_pdo->update(
                    $table_name,
                    array('a', 'b'),
                    $condition,
                    $expected
                );

                return $expected;
            }
        );
    }

    /**
     * @dataProvider dbProvider
     */
    public function testDeleteRecord($yo_pdo)
    {
        $rows = $this->getSampleRows();
        $table_name = $this->createPopulatedTable($yo_pdo, $rows);

        $expected = $rows;
        unset($expected[2]);

        $yo_pdo->delete(
            $table_name,
            "id = :id",
            array('id' => 2)
        );

        $this->assertResults($yo_pdo, $table_name, $expected);
    }

    /**
     * @return array
     */
    public function dbProvider()
    {
        $factory = new Factory();
        $yo_pdo = $factory->createFromConfig($this->getConfig());

        return array(
            array($yo_pdo),
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
     * @param YoPdo $yo_pdo
     * @return string
     */
    private function createTable(YoPdo $yo_pdo)
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
     * @param $table_name
     * @param array $rows
     */
    private function populateTable(YoPdo $yo_pdo, $table_name, array $rows)
    {
        foreach ($rows as $row) {
            $yo_pdo->insert($table_name, $row);
        }
    }

    /**
     * @param YoPdo $yo_pdo
     * @param array $rows
     * @return string
     */
    private function createPopulatedTable(YoPdo $yo_pdo, array $rows)
    {
        $table_name = $this->createTable($yo_pdo);
        $this->populateTable($yo_pdo, $table_name, $rows);

        return $table_name;
    }

    /**
     * @param YoPdo $yo_pdo
     * @param callable $run_update
     */
    private function assertUpdated(YoPdo $yo_pdo, $run_update)
    {
        $rows = $this->getSampleRows();
        $table_name = $this->createPopulatedTable($yo_pdo, $rows);

        $expected = $rows;
        $expected[2] = $run_update($table_name, 'id = 2');

        $this->assertResults($yo_pdo, $table_name, $expected);
    }

    /**
     * @param YoPdo $yo_pdo
     * @param string $table_name
     * @param array $expected_results
     * @return array
     */
    private function assertResults(YoPdo $yo_pdo, $table_name, array $expected_results)
    {
        $sql = <<<SQL
SELECT id, a, b
FROM {$table_name}
ORDER BY id
SQL;
        $result = $yo_pdo->query($sql);
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

    /**
     * @return array
     */
    private function getSampleRows()
    {
        return array(
            1 => array('a' => 3, 'b' => 6),
            2 => array('a' => 2, 'b' => 4),
            3 => array('a' => 1, 'b' => 2),
        );
    }
}
