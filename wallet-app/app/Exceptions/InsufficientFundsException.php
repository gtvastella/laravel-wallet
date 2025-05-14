<?php

namespace App\Exceptions;

use Illuminate\Http\Response;

class InsufficientFundsException extends WalletAppException
{
    protected $statusCode = Response::HTTP_BAD_REQUEST; // 400
    protected $errorCode = 'insufficient_funds';
}
