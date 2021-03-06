<?php

namespace Lstr\YoPdo;

use Lstr\YoPdo\TestUtil\QueryResultAsserter;
use Lstr\YoPdo\TestUtil\SampleTableCreator;
use PDO;
use PDOException;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass \Lstr\YoPdo\YoPdo
 */
class YoPdoTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var QueryResultAsserter
     */
    private $result_asserter;

    /**
     * @var SampleTableCreator
     */
    private $sample_table_creator;

    public function setUp()
    {
        $this->result_asserter = new QueryResultAsserter($this);
        $this->sample_table_creator = new SampleTableCreator();
    }

    /**
     * @covers ::__construct
     * @covers ::getPdo
     */
    public function testPdoConnectionCanBeRetrieved()
    {
        $config = $this->getConfig();
        $pdo = new PDO($config['dsn'], $config['username'], $config['password']);
        $yo_pdo = new YoPdo($pdo);

        $this->assertSame($pdo, $yo_pdo->getPdo());
    }

    /**
     * @covers ::transaction
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     */
    public function testTransactionCanBeRetrieved(YoPdo $yo_pdo)
    {
        $this->assertInstanceOf('Lstr\YoPdo\Transaction', $yo_pdo->transaction());
        $this->assertSame($yo_pdo->transaction(), $yo_pdo->transaction(), 'Reuse transaction object');
        $this->assertSame($yo_pdo, $yo_pdo->transaction()->getYoPdo());
    }

    /**
     * @covers ::query
     * @covers ::<private>
     * @dataProvider dbProvider
     * @expectedException PDOException
     * @param YoPdo $yo_pdo
     */
    public function testAnErrorInAQueryThrowsAnException(YoPdo $yo_pdo)
    {
        $yo_pdo->query('SELECT oops');
    }

    /**
     * @covers ::query
     * @covers ::<private>
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     */
    public function testASimpleQueryCanBeRan(YoPdo $yo_pdo)
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
     * @covers ::getSelectRowGenerator
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     */
    public function testARowGeneratorCanBeUsed(YoPdo $yo_pdo)
    {
        $sql = <<<SQL
SELECT :param_a AS col UNION
SELECT :param_b AS col UNION
SELECT :last_param AS col
ORDER BY col
SQL;

        $result = $yo_pdo->getSelectRowGenerator($sql, array(
            'param_a'    => 1,
            'param_b'    => 2,
            'last_param' => 3,
        ));
        $count = 1;
        foreach($result as $row) {
            $this->assertEquals($count, $row['col']);
            ++$count;
        }
    }

    /**
     * @covers ::queryMultiple
     * @covers ::<private>
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     */
    public function testMultipleQueriesCanBeRan(YoPdo $yo_pdo)
    {
        $table_name = $this->sample_table_creator->createTable($yo_pdo);
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

        $this->result_asserter->assertResults($yo_pdo, $table_name, array(
            1 => array('a' => $params['row_1_col_a'], 'b' => $params['row_1_col_b']),
            2 => array('a' => $params['row_2_col_a'],'b' => $params['row_2_col_b']),
            3 => array('a' => $params['last_row_col_a'], 'b' => $params['last_row_col_b']),
        ));
    }

    /**
     * @covers ::insert
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     */
    public function testInsert(YoPdo $yo_pdo)
    {
        list($rows, $expected) = $this->sample_table_creator->getSampleRowsForUpsert();

        $table_name = $this->sample_table_creator->createTable($yo_pdo);
        foreach ($rows as $row) {
            $yo_pdo->insert($table_name, $row);
        }

        $this->result_asserter->assertResults($yo_pdo, $table_name, $expected);
    }

    /**
     * @covers ::insert
     * @covers ::getLastInsertId
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     */
    public function testLastInsertIdCanBeRetrieved(YoPdo $yo_pdo)
    {
        $table_name = $this->sample_table_creator->createTable($yo_pdo);
        for ($i = 1; $i <= 3; $i++) {
            $yo_pdo->insert($table_name, array('a' => $i + 5, 'b' => $i + 10));
            $this->assertEquals($i, $yo_pdo->getLastInsertId("{$table_name}_id_seq"));
        }
    }

    /**
     * @covers ::update
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     */
    public function testUpdateRecordsUsingCustomPlaceholderNames(YoPdo $yo_pdo)
    {
        $this->assertUpdated(
            $yo_pdo,
            function ($table_name, $condition) use ($yo_pdo) {
                $yo_pdo->update(
                    $table_name,
                    array('a' => 'some_number', 'b' => 'some_number', 'c' => 'some_expression'),
                    $condition,
                    array('some_number' => 112, 'some_expression' => new ExpressionValue('5 + 2'))
                );

                return array('a' => 112, 'b' => 112, 'c' => 7);
            }
        );
    }

    /**
     * @covers ::update
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     */
    public function testUpdateRecordsUsingColumnNamesAsPlaceholderNames(YoPdo $yo_pdo)
    {
        $this->assertUpdated(
            $yo_pdo,
            function ($table_name, $condition) use ($yo_pdo) {
                $values = array('a' => 102, 'b' => 120, 'c' => new ExpressionValue('5 + 2'));

                $yo_pdo->update(
                    $table_name,
                    array('a', 'b', 'c'),
                    $condition,
                    $values
                );

                $expected = $values;
                $expected['c'] = 7;
                return $expected;
            }
        );
    }

    /**
     * @covers ::delete
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     */
    public function testDeleteRecord(YoPdo $yo_pdo)
    {
        $rows = $this->sample_table_creator->getSampleRows();
        $table_name = $this->sample_table_creator->createPopulatedTable($yo_pdo, $rows);

        $expected = $rows;
        unset($expected[2]);

        $yo_pdo->delete(
            $table_name,
            "id = :id",
            array('id' => 2)
        );

        $this->result_asserter->assertResults($yo_pdo, $table_name, $expected);
    }

    /**
     * @covers ::getBulkInserter
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     */
    public function testBulkInserterRetrievedFromYoPdoCanBeUsed(YoPdo $yo_pdo)
    {
        $table_name = $this->sample_table_creator->createTable($yo_pdo);

        $bulk_inserter = $yo_pdo->getBulkInserter($table_name, ['a', 'b'], 3);
        $bulk_inserter->insertRecords([
            [4, 5],
            [102, 32],
            [43, 12],
        ]);

        $this->result_asserter->assertResults($yo_pdo, $table_name, array(
            1 => array('a' => 4, 'b' => 5),
            2 => array('a' => 102,'b' => 32),
            3 => array('a' => 43, 'b' => 12),
        ));
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
     * @param callable $run_update
     */
    private function assertUpdated(YoPdo $yo_pdo, $run_update)
    {
        list($rows, $expected) = $this->sample_table_creator->getSampleRowsForUpsert();
        $table_name = $this->sample_table_creator->createPopulatedTable($yo_pdo, $rows);

        $expected[2] = $run_update($table_name, 'id = 2');

        $this->result_asserter->assertResults($yo_pdo, $table_name, $expected);
    }
}
