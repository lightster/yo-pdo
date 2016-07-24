<?php

namespace Lstr\YoPdo;

class ExpressionValue
{
    /**
     * @var string
     */
    private $value;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getString()
    {
        return $this->value;
    }
}
