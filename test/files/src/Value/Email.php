<?php

namespace App\Value;

class Email
{
    /**
     * @var string
     */
    private $value;

    /**
     * @param string $email
     */
    public function __construct($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email provided');
        }
        $this->value = $email;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}