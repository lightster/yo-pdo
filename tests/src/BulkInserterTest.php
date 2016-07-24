<?php

namespace Lstr\YoPdo;

use Lstr\YoPdo\TestUtil\QueryResultAsserter;
use Lstr\YoPdo\TestUtil\SampleTableCreator;
use PDO;
use PDOException;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass \Lstr\YoPdo\BulkInserter
 */
class BulkInserterTest extends PHPUnit_Framework_TestCase
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
     * @covers ::__destruct
     * @covers ::insertRecords
     * @covers ::<private>
     * @dataProvider dbProvider
     */
    public function testBulkInserterCanInsertRecords($yo_pdo)
    {
        $table_name = $this->sample_table_creator->createTable($yo_pdo);

        $bulk_inserter = new BulkInserter($yo_pdo, $table_name, ['a', 'b'], 250);
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
     * @covers ::__construct
     * @covers ::insertRecords
     * @covers ::<private>
     * @dataProvider dbProvider
     */
    public function testBulkInserterCanInsertTheExactNumberOfRecordsThatFitsInTheBuffer($yo_pdo)
    {
        $table_name = $this->sample_table_creator->createTable($yo_pdo);

        $bulk_inserter = new BulkInserter($yo_pdo, $table_name, ['a', 'b'], 3);
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
     * @covers ::__construct
     * @covers ::bufferRecord
     * @covers ::__destruct
     * @dataProvider dbProvider
     * @expectedException Lstr\YoPdo\Exception\BufferNotEmptiedException
     */
    public function testBulkInserterThrowsAnExceptionWhenBufferNotEmptied($yo_pdo)
    {
        $bulk_inserter = new BulkInserter($yo_pdo, 'anything', ['a', 'b'], 250);
        $bulk_inserter->bufferRecord([1, 2]);

        unset($bulk_inserter);
    }

    /**
     * @covers ::bufferRecord
     * @covers ::destroyBuffer
     * @dataProvider dbProvider
     */
    public function testBulkInserterBufferCanBeDestroyed($yo_pdo)
    {
        $bulk_inserter = new BulkInserter($yo_pdo, 'anything', ['a', 'b'], 250);
        $bulk_inserter->bufferRecord([1, 2]);

        $bulk_inserter->destroyBuffer();

        unset($bulk_inserter);
    }

    /**
     * @covers ::__construct
     * @covers ::bufferRecords
     * @dataProvider dbProvider
     * @expectedException Lstr\YoPdo\Exception\BufferNotEmptiedException
     */
    public function testRecordsCanBeBufferedWithoutBeingInserted($yo_pdo)
    {
        $table_name = $this->sample_table_creator->createTable($yo_pdo);

        $bulk_inserter = new BulkInserter($yo_pdo, $table_name, ['a', 'b'], 250);
        $bulk_inserter->bufferRecords([
            [4, 5],
            [102, 32],
            [43, 12],
        ]);

        $this->result_asserter->assertResults($yo_pdo, $table_name, array());
    }

    /**
     * @covers ::__construct
     * @covers ::bufferRecords
     * @covers ::insertRecords
     * @covers ::<private>
     * @dataProvider dbProvider
     */
    public function testBufferIsAutomaticallyInsertedWhenBufferingAsManyRowsAsBufferCanHold($yo_pdo)
    {
        $table_name = $this->sample_table_creator->createTable($yo_pdo);

        $bulk_inserter = new BulkInserter($yo_pdo, $table_name, ['a', 'b'], 3);
        $bulk_inserter->bufferRecords([
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
     * @covers ::__construct
     * @covers ::bufferRecords
     * @covers ::insertRecords
     * @covers ::<private>
     * @dataProvider dbProvider
     * @expectedException Lstr\YoPdo\Exception\BufferNotEmptiedException
     */
    public function testBufferIsAutomaticallyInsertedWhenBufferingMoreRowsThanBufferCanHold($yo_pdo)
    {
        $table_name = $this->sample_table_creator->createTable($yo_pdo);

        $bulk_inserter = new BulkInserter($yo_pdo, $table_name, ['a', 'b'], 2);
        $bulk_inserter->bufferRecords([
            [4, 5],
            [102, 32],
            [43, 12],
        ]);

        $this->result_asserter->assertResults($yo_pdo, $table_name, array(
            1 => array('a' => 4, 'b' => 5),
            2 => array('a' => 102,'b' => 32),
        ));
    }

    /**
     * @covers ::__construct
     * @covers ::bufferRecords
     * @covers ::bufferRecord
     * @covers ::insertRecords
     * @covers ::<private>
     * @dataProvider dbProvider
     */
    public function testBulkInserterInsertsWhenBufferingRecordThatCausesLimitToBeHit($yo_pdo)
    {
        $table_name = $this->sample_table_creator->createTable($yo_pdo);

        $bulk_inserter = new BulkInserter($yo_pdo, $table_name, ['a', 'b'], 3);

        $bulk_inserter->bufferRecords([
            [4, 5],
            [102, 32],
        ]);

        $this->result_asserter->assertResults($yo_pdo, $table_name, array());

        $bulk_inserter->bufferRecord([43, 12]);

        $this->result_asserter->assertResults($yo_pdo, $table_name, array(
            1 => array('a' => 4, 'b' => 5),
            2 => array('a' => 102,'b' => 32),
            3 => array('a' => 43, 'b' => 12),
        ));
    }

    /**
     * @covers ::insertRecords
     * @covers ::<private>
     * @dataProvider dbProvider
     */
    public function testBulkInserterCanInsertMultipleTimes($yo_pdo)
    {
        $table_name = $this->sample_table_creator->createTable($yo_pdo);

        $bulk_inserter = new BulkInserter($yo_pdo, $table_name, ['a', 'b'], 2);

        $bulk_inserter->bufferRecords([
            [4, 5],
            [102, 32],
        ]);

        $this->result_asserter->assertResults($yo_pdo, $table_name, array(
            1 => array('a' => 4, 'b' => 5),
            2 => array('a' => 102,'b' => 32),
        ));

        $bulk_inserter->bufferRecords([
            [43, 12],
            [27, 24],
        ]);

        $this->result_asserter->assertResults($yo_pdo, $table_name, array(
            1 => array('a' => 4, 'b' => 5),
            2 => array('a' => 102,'b' => 32),
            3 => array('a' => 43, 'b' => 12),
            4 => array('a' => 27, 'b' => 24),
        ));
    }

    /**
     * @covers ::bufferRecords
     * @dataProvider dbProvider
     * @expectedException Lstr\YoPdo\Exception\ValueCountMismatchException
     */
    public function testAnExceptionIsThrownWhenBulkInserterHasMoreColumnsThanRecord($yo_pdo)
    {
        $table_name = $this->sample_table_creator->createTable($yo_pdo);

        $bulk_inserter = new BulkInserter($yo_pdo, $table_name, ['a', 'b'], 2);

        $bulk_inserter->bufferRecords([
            [4],
        ]);
    }

    /**
     * @dataProvider dbProvider
     * @expectedException Lstr\YoPdo\Exception\ValueCountMismatchException
     */
    public function testAnExceptionIsThrownWhenBulkInserterHasFewerColumnsThanRecord($yo_pdo)
    {
        $table_name = $this->sample_table_creator->createTable($yo_pdo);

        $bulk_inserter = new BulkInserter($yo_pdo, $table_name, ['a'], 2);

        $bulk_inserter->bufferRecords([
            [4, 5],
        ]);
    }

    /**
     * @covers ::bufferRecord
     * @dataProvider dbProvider
     */
    public function testManyPreparedPlaceholdersCanBeUsed($yo_pdo)
    {
        $table_name = $this->sample_table_creator->createTable($yo_pdo);

        $bulk_inserter = new BulkInserter($yo_pdo, $table_name, ['a', 'b'], 2500);

        $expected_records = [];
        for ($i = 0; $i < 5000; $i++) {
            $record = ['a' => $i, 'b' => 500000 - $i];
            $bulk_inserter->bufferRecord([$record['a'], $record['b']]);
            $expected_records[$i + 1] = $record;
        }

        $this->result_asserter->assertResults($yo_pdo, $table_name, $expected_records);
    }

    /**
     * @return array
     */
    public function dbProvider()
    {
        $config = require __DIR__ . '/../config/config.php';
        $factory = new Factory();
        $yo_pdo = $factory->createFromConfig($config['database']);

        return array(
            array($yo_pdo),
        );
    }
}
