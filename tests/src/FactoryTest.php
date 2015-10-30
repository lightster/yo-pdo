<?php

namespace Lstr\YoPdo;

use PDOException;
use PHPUnit_Framework_TestCase;

class FactoryTest extends PHPUnit_Framework_TestCase
{
    public function testYoPdoCanBeCreatedFromConfigArray()
    {
        $factory = new Factory();
        $this->assertInstanceOf(
            'Lstr\YoPdo\YoPdo',
            $factory->createFromConfig($this->getConfig())
        );
    }

    public function testPdoCanBeUsedAfterYoPdoIsCreatedFromConfigArray()
    {
        $factory = new Factory();
        $yo_pdo = $factory->createFromConfig($this->getConfig());

        $this->assertEquals(
            1,
            $yo_pdo->getPdo()->query('SELECT 1')->fetchColumn(0)
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
}
