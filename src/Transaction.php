<?php

namespace Lstr\YoPdo;

use Lstr\YoPdo\Exception\DuplicateTransactionNameException;
use Lstr\YoPdo\Exception\TransactionAcceptanceOrderException;
use Lstr\YoPdo\Exception\UnknownTransactionNameException;

class Transaction
{
    /**
     * @var YoPdo
     */
    private $yo_pdo;

    /**
     * @var array
     */
    private $names = [];

    /**
     * @var string
     */
    private $current_name = null;

    /**
     * @param YoPdo $yo_pdo
     */
    public function __construct(YoPdo $yo_pdo)
    {
        $this->yo_pdo = $yo_pdo;
    }

    /**
     * @return YoPdo
     */
    public function getYoPdo()
    {
        return $this->yo_pdo;
    }

    /**
     * @param string $name
     * @throws DuplicateTransactionNameException
     */
    public function begin($name)
    {
        if (isset($this->names[$name])) {
            throw new DuplicateTransactionNameException($name);
        }

        if (null === $this->current_name) {
            $this->yo_pdo->query('BEGIN');
        }

        $this->names[$name] = true;
        $this->current_name = $name;
    }

    /**
     * @param string $name
     * @throws TransactionAcceptanceOrderException
     * @throws UnknownTransactionNameException
     */
    public function accept($name)
    {
        if (!isset($this->names[$name])) {
            throw new UnknownTransactionNameException($name);
        }

        if ($this->current_name !== $name) {
            throw new TransactionAcceptanceOrderException($this->current_name, $name);
        }

        unset($this->names[$name]);
        end($this->names);
        $this->current_name = key($this->names);

        if (null === $this->current_name) {
            $this->yo_pdo->query('COMMIT');
        }
    }

    public function rollbackAll()
    {
        if (null === $this->current_name) {
            return;
        }

        $this->names = [];
        $this->current_name = null;

        $this->yo_pdo->query('ROLLBACK');
    }
}
