<?php

namespace App\Exceptions;

use Illuminate\Http\Response;

class TransactionReversalException extends WalletAppException
{
    protected $statusCode = Response::HTTP_BAD_REQUEST;
    protected $errorCode = 'transaction_reversal_error';
}
