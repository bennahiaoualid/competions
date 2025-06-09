<?php

namespace App\Contracts;

interface TransactionManagerInterface
{
    public function run(callable $callback): mixed;
}