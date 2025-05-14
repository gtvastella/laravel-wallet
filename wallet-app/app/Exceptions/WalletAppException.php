<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

abstract class WalletAppException extends Exception
{
    protected $statusCode = Response::HTTP_BAD_REQUEST;
    protected $errorCode = 'wallet_app_error';

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }
}
