<?php

namespace Lstr\YoPdo\Exception;

use Exception;

class TransactionAcceptanceOrderException extends Exception
{
    /**
     * @var string
     */
    private $expected_transaction_name;

    /**
     * @var string
     */
    private $actual_transaction_name;

    /**
     * @param string $expected_transaction_name
     * @param string $actual_transaction_name
     */
    public function __construct($expected_transaction_name, $actual_transaction_name)
    {
        parent::__construct(
            "Transaction name '{$actual_transaction_name}' cannot be accepted before "
            . "transaction name '{$expected_transaction_name}'."
        );

        $this->expected_transaction_name = $expected_transaction_name;
        $this->actual_transaction_name = $actual_transaction_name;
    }

    /**
     * @return string
     */
    public function getExpectedTransactionName()
    {
        return $this->expected_transaction_name;
    }

    /**
     * @return string
     */
    public function getActualTransactionName()
    {
        return $this->actual_transaction_name;
    }
}
