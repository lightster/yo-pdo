<?php

namespace Lstr\YoPdo\TestUtil;

use Lstr\YoPdo\YoPdo;
use PHPUnit_Framework_TestCase;

class QueryResultAsserter
{
    /**
     * @var PHPUnit_Framework_TestCase
     */
    private $test_case;

    /**
     * @param PHPUnit_Framework_TestCase $test_case
     */
    public function __construct(PHPUnit_Framework_TestCase $test_case)
    {
        $this->test_case = $test_case;
    }

    /**
     * @param YoPdo $yo_pdo
     * @param string $table_name
     * @param array $expected_results
     * @return array
     */
    public function assertResults(YoPdo $yo_pdo, $table_name, array $expected_results)
    {
        $column_sql = '';
        if ($expected_results && reset($expected_results)) {
            $column_sql = ', ' . implode(', ', array_keys(reset($expected_results)));
        }

        $sql = <<<SQL
SELECT id {$column_sql}
FROM {$table_name}
ORDER BY id
SQL;
        $result = $yo_pdo->query($sql);
        while ($row = $result->fetch()) {
            if (!array_key_exists('id', $row)) {
                $this->test_case->assertTrue(false, "Field 'id' not found in row.");
            } elseif (!array_key_exists($row['id'], $expected_results)) {
                $this->test_case->assertTrue(
                    false,
                    "Row with key '{$row['id']}' not found in expected results."
                );
            } else {
                $expected_result = $expected_results[$row['id']];
                $expected_result['id'] = $row['id'];
                if (array_diff_assoc($expected_result, $row) || array_diff_assoc($row, $expected_result)) {
                    $this->test_case->assertEquals($expected_result, $row);
                }
                unset($expected_results[$row['id']]);
            }
        }

        $this->test_case->assertEmpty($expected_results);
    }
}
