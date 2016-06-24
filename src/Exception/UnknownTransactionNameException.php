<?php

namespace Lstr\YoPdo\Exception;

use Exception;

class UnknownTransactionNameException extends Exception
{
    /**
     * @var string
     */
    private $transaction_name;

    /**
     * @param string $transaction_name
     */
    public function __construct($transaction_name)
    {
        parent::__construct(
            "Transaction name '{$transaction_name}' was not started and therefore cannot be accepted."
        );

        $this->transaction_name = $transaction_name;
    }

    /**
     * @return string
     */
    public function getTransactionName()
    {
        return $this->transaction_name;
    }
}
