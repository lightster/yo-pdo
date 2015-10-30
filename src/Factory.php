<?php

namespace Lstr\YoPdo;

use PDO;

class Factory
{
    /**
     * @param array $config
     * @return YoPdo
     */
    public function createFromConfig(array $config)
    {
        $dsn = $username = $password = $options = null;
        extract($config, EXTR_IF_EXISTS);

        $pdo = new PDO($dsn, $username, $password, $options);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return new YoPdo($pdo);
    }
}
