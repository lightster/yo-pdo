<?php

namespace Lstr\YoPdo;

use Lstr\YoPdo\Exception\UnknownTransactionNameException;
use Lstr\YoPdo\TestUtil\QueryResultAsserter;
use Lstr\YoPdo\TestUtil\SampleTableCreator;
use PDOException;
use PHPUnit_Framework_TestCase;

class TransactionTest extends PHPUnit_Framework_TestCase
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
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     */
    public function testTransactionIsCommittedIfNameIsAccepted(YoPdo $yo_pdo)
    {
        $rows = $this->sample_table_creator->getSampleRows();
        $table_name = $this->sample_table_creator->createTable($yo_pdo);

        $yo_pdo->transaction()->begin('outer');
        $yo_pdo->insert($table_name, $rows[1]);
        $yo_pdo->insert($table_name, $rows[2]);
        $yo_pdo->insert($table_name, $rows[3]);
        $yo_pdo->transaction()->accept('outer');

        try {
            // make sure an error occurred so we know the results were committed
            $yo_pdo->query('SELECT oops');
        } catch (PDOException $exception) {
            $this->result_asserter->assertResults($yo_pdo, $table_name, $rows);
        }
    }

    /**
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     */
    public function testTransactionCanBeReused(YoPdo $yo_pdo)
    {
        $rows = $this->sample_table_creator->getSampleRows();
        $table_name = $this->sample_table_creator->createTable($yo_pdo);

        $yo_pdo->transaction()->begin('first');
        $yo_pdo->insert($table_name, $rows[1]);
        $yo_pdo->transaction()->accept('first');
        $yo_pdo->transaction()->begin('second');
        $yo_pdo->insert($table_name, $rows[2]);
        $yo_pdo->insert($table_name, $rows[3]);
        $yo_pdo->transaction()->accept('second');

        try {
            // make sure an error occurred so we know the results were committed
            $yo_pdo->query('SELECT oops');
        } catch (PDOException $exception) {
            $this->result_asserter->assertResults($yo_pdo, $table_name, $rows);
        }
    }

    /**
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     */
    public function testTransactionIsNotCommittedIfNameIsNotAccepted(YoPdo $yo_pdo)
    {
        $rows = $this->sample_table_creator->getSampleRows();
        $table_name = $this->sample_table_creator->createTable($yo_pdo);

        $yo_pdo->transaction()->begin('outer');
        $yo_pdo->insert($table_name, $rows[1]);
        $yo_pdo->insert($table_name, $rows[2]);
        $yo_pdo->insert($table_name, $rows[3]);

        $yo_pdo->query('ROLLBACK');
        $this->result_asserter->assertResults($yo_pdo, $table_name, []);
    }

    /**
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     */
    public function testTransactionIsCommittedIfAllNamesAreAccepted(YoPdo $yo_pdo)
    {
        $rows = $this->sample_table_creator->getSampleRows();
        $table_name = $this->sample_table_creator->createTable($yo_pdo);

        $yo_pdo->transaction()->begin('outer');
        $yo_pdo->insert($table_name, $rows[1]);
        $yo_pdo->transaction()->begin('inner');
        $yo_pdo->insert($table_name, $rows[2]);
        $yo_pdo->insert($table_name, $rows[3]);
        $yo_pdo->transaction()->accept('inner');
        $yo_pdo->transaction()->accept('outer');

        try {
            // make sure an error occurred so we know the results were committed
            $yo_pdo->query('SELECT oops');
        } catch (PDOException $exception) {
            $this->result_asserter->assertResults($yo_pdo, $table_name, $rows);
        }
    }

    /**
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     */
    public function testTransactionIsNotCommittedIfNotAllNamesAreAccepted(YoPdo $yo_pdo)
    {
        $rows = $this->sample_table_creator->getSampleRows();
        $table_name = $this->sample_table_creator->createTable($yo_pdo);

        $yo_pdo->transaction()->begin('outer');
        $yo_pdo->insert($table_name, $rows[1]);
        $yo_pdo->transaction()->begin('inner');
        $yo_pdo->insert($table_name, $rows[2]);
        $yo_pdo->insert($table_name, $rows[3]);
        $yo_pdo->transaction()->accept('inner');

        $yo_pdo->query('ROLLBACK');
        $this->result_asserter->assertResults($yo_pdo, $table_name, []);
    }

    /**
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     * @expectedException \Lstr\YoPdo\Exception\TransactionAcceptanceOrderException
     */
    public function testNamesMustBeAcceptedInOppositeOrderTheyWereStarted(YoPdo $yo_pdo)
    {
        $yo_pdo->transaction()->begin('outer');
        $yo_pdo->transaction()->begin('inner');
        $yo_pdo->transaction()->accept('outer');
    }

    /**
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     * @expectedException \Lstr\YoPdo\Exception\DuplicateTransactionNameException
     */
    public function testDuplicateActiveNamesAreNotAllowed(YoPdo $yo_pdo)
    {
        $yo_pdo->transaction()->begin('outer');
        $yo_pdo->transaction()->begin('outer');
    }

    /**
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     * @expectedException \Lstr\YoPdo\Exception\UnknownTransactionNameException
     */
    public function testAcceptingUnknownNameIsNotAllowed(YoPdo $yo_pdo)
    {
        $yo_pdo->transaction()->accept('outer');
    }

    /**
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     */
    public function testRollbackAllRollsBackTheTransaction(YoPdo $yo_pdo)
    {
        $rows = $this->sample_table_creator->getSampleRows();
        $table_name = $this->sample_table_creator->createTable($yo_pdo);

        $yo_pdo->transaction()->begin('outer');
        $yo_pdo->insert($table_name, $rows[1]);

        $yo_pdo->transaction()->rollbackAll();

        $this->result_asserter->assertResults($yo_pdo, $table_name, []);
    }

    /**
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     */
    public function testRollbackAllEndsAllNames(YoPdo $yo_pdo)
    {
        $rows = $this->sample_table_creator->getSampleRows();
        $table_name = $this->sample_table_creator->createTable($yo_pdo);

        $yo_pdo->transaction()->begin('outer');
        $yo_pdo->transaction()->begin('inner');
        $yo_pdo->insert($table_name, $rows[1]);

        $yo_pdo->transaction()->rollbackAll();

        $this->assertUnknownTransactionNameException($yo_pdo, 'inner');
        $this->assertUnknownTransactionNameException($yo_pdo, 'outer');
    }

    /**
     * @dataProvider dbProvider
     * @param YoPdo $yo_pdo
     */
    public function testRollbackAllDoesNothingIfThereAreNoNamesToRollback(YoPdo $yo_pdo)
    {
        $yo_pdo->transaction()->rollbackAll();

        $this->assertTrue(true);
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
     * @param string $name
     * @throws Exception\TransactionAcceptanceOrderException
     */
    private function assertUnknownTransactionNameException(YoPdo $yo_pdo, $name)
    {
        try {
            $yo_pdo->transaction()->accept($name);
            $this->fail("'{$name}' transaction should not be defined");
        } catch (UnknownTransactionNameException $exception) {
            $this->assertTrue(true);
        }
    }
}
