<?php

namespace Lstr\YoPdo\Exception;

use Exception;

class TransactionAcceptanceOrderException extends Exception
{
    /**
     * @var string
     */
    private $expected_name;

    /**
     * @var string
     */
    private $actual_name;

    /**
     * @param string $expected_name
     * @param string $actual_name
     */
    public function __construct($expected_name, $actual_name)
    {
        parent::__construct(
            "Transaction name '{$actual_name}' cannot be accepted before "
            . "transaction name '{$expected_name}'."
        );

        $this->expected_name = $expected_name;
        $this->actual_name = $actual_name;
    }

    /**
     * @return string
     */
    public function getExpectedTransactionName()
    {
        return $this->expected_name;
    }

    /**
     * @return string
     */
    public function getActualTransactionName()
    {
        return $this->actual_name;
    }
}
