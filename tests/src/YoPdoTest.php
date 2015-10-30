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
        $tablename = 'test_multiple_queries_' . uniqid();
        $populate_sql = <<<SQL
CREATE TABLE {$tablename} (
    id INT NOT NULL
);
INSERT INTO {$tablename} VALUES (:param_a);
INSERT INTO {$tablename} VALUES (:param_b);
INSERT INTO {$tablename} VALUES (:last_param);
SQL;
        $result = $db->queryMultiple($populate_sql, array(
            'param_a'    => 1,
            'param_b'    => 2,
            'last_param' => 3,
        ));

        $select_sql = <<<SQL
SELECT id AS col
FROM {$tablename}
ORDER BY col
SQL;
        $result = $db->query($select_sql);
        $count = 1;
        while ($row = $result->fetch()) {
            $this->assertEquals($count, $row['col']);
            ++$count;
        }
    }

    /**
     * @dataProvider dbProvider
     */
    public function testInsert($db)
    {
        $tablename = 'insert_queries_' . uniqid();
        $create_table_sql = <<<SQL
CREATE SEQUENCE {$tablename}_id_seq;
CREATE TABLE {$tablename} (
    id INT NOT NULL DEFAULT NEXTVAL('{$tablename}_id_seq'::regclass),
    other INT NOT NULL
);
SQL;
        $result = $db->queryMultiple($create_table_sql);

        $db->insert($tablename, array('other' => 3));
        $db->insert($tablename, array('other' => 2));
        $db->insert($tablename, array('other' => 1));

        $select_sql = <<<SQL
SELECT id, other
FROM {$tablename}
ORDER BY id
SQL;
        $result = $db->query($select_sql);
        $count = 1;
        while ($row = $result->fetch()) {
            $this->assertEquals($count, $row['id']);
            $this->assertEquals(4 - $count, $row['other']);
            ++$count;
        }
    }

    /**
     * @dataProvider dbProvider
     */
    public function testLastInsertIdCanBeRetrieved($db)
    {
        $tablename = 'last_insert_id_' . uniqid();
        $create_table_sql = <<<SQL
CREATE SEQUENCE {$tablename}_id_seq;
CREATE TABLE {$tablename} (
    id INT NOT NULL DEFAULT NEXTVAL('{$tablename}_id_seq'::regclass),
    other INT NOT NULL
);
SQL;
        $result = $db->queryMultiple($create_table_sql);

        for ($i = 1; $i <= 3; $i++) {
            $db->insert($tablename, array('other' => $i + 5));
            $this->assertEquals($i, $db->getLastInsertId("{$tablename}_id_seq"));
        }
    }

    /**
     * @dataProvider dbProvider
     */
    public function testUpdateRecordsUsingCustomPlaceholderNames($db)
    {
        $tablename = 'update_records_' . uniqid();
        $create_table_sql = <<<SQL
CREATE SEQUENCE {$tablename}_id_seq;
CREATE TABLE {$tablename} (
    id INT NOT NULL,
    a INT NOT NULL,
    b INT NOT NULL
);
SQL;
        $result = $db->queryMultiple($create_table_sql);

        $expected = array(
            1 => array('a' => 4, 'b' => 7),
            2 => array('a' => 5, 'b' => 8),
            3 => array('a' => 6, 'b' => 9),
        );

        for ($i = 1; $i <= 3; $i++) {
            $record = $expected[$i];
            $record['id'] = $i;
            $db->insert($tablename, $record);
        }

        $new_expected = $expected;
        $new_expected[2] = array('a' => 112, 'b' => 112);

        $db->update(
            $tablename,
            array('a' => 'some_number', 'b' => 'some_number'),
            'id = 2',
            array('some_number' => 112)
        );

        $select_sql = <<<SQL
SELECT id, a, b
FROM {$tablename}
ORDER BY id
SQL;
        $result = $db->query($select_sql);
        while ($row = $result->fetch()) {
            $this->assertEquals($new_expected[$row['id']]['a'], $row['a']);
            $this->assertEquals($new_expected[$row['id']]['b'], $row['b']);
        }
    }

    /**
     * @dataProvider dbProvider
     */
    public function testUpdateRecordsUsingColumnNamesAsPlaceholderNames($db)
    {
        $tablename = 'update_records_' . uniqid();
        $create_table_sql = <<<SQL
CREATE SEQUENCE {$tablename}_id_seq;
CREATE TABLE {$tablename} (
    id INT NOT NULL,
    a INT NOT NULL,
    b INT NOT NULL
);
SQL;
        $result = $db->queryMultiple($create_table_sql);

        $expected = array(
            1 => array('a' => 4, 'b' => 7),
            2 => array('a' => 5, 'b' => 8),
            3 => array('a' => 6, 'b' => 9),
        );

        for ($i = 1; $i <= 3; $i++) {
            $record = $expected[$i];
            $record['id'] = $i;
            $db->insert($tablename, $record);
        }

        $new_expected = $expected;
        $new_expected[2] = array('a' => 102, 'b' => 120);

        $db->update(
            $tablename,
            array('a', 'b'),
            'id = 2',
            $new_expected[2]
        );

        $select_sql = <<<SQL
SELECT id, a, b
FROM {$tablename}
ORDER BY id
SQL;
        $result = $db->query($select_sql);
        while ($row = $result->fetch()) {
            $this->assertEquals($new_expected[$row['id']]['a'], $row['a']);
            $this->assertEquals($new_expected[$row['id']]['b'], $row['b']);
        }
    }

    public function dbProvider()
    {
        $db = new YoPdo($this->getConfig());

        return array(
            array($db),
        );
    }

    private function getConfig()
    {
        $config = require __DIR__ . '/../config/config.php';

        return $config['database'];
    }
}
