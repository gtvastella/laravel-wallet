<?php

namespace App\Exceptions;

use Illuminate\Http\Response;

class InvalidCredentialsException extends WalletAppException
{
    protected $statusCode = Response::HTTP_UNAUTHORIZED;
    protected $errorCode = 'invalid_credentials';
}
