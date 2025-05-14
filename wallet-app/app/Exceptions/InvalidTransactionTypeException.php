<?php

namespace App\Exceptions;

class InvalidTransactionTypeException extends WalletAppException
{
    protected $statusCode = 400;
    protected $errorCode = 'invalid_transaction_type';
}
