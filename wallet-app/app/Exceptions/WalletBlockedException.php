<?php

namespace App\Exceptions;

use Illuminate\Http\Response;

class WalletBlockedException extends WalletAppException
{
    protected $statusCode = Response::HTTP_FORBIDDEN;
    protected $errorCode = 'wallet_blocked';
}
