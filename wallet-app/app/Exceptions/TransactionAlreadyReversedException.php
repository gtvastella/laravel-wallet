<?php

namespace App\Exceptions;

use Illuminate\Http\Response;

class TransactionAlreadyReversedException extends WalletAppException
{
    protected $statusCode = Response::HTTP_BAD_REQUEST;
    protected $errorCode = 'transaction_already_reversed';
}
