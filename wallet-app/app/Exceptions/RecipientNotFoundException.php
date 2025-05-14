<?php

namespace App\Exceptions;

use Illuminate\Http\Response;

class RecipientNotFoundException extends WalletAppException
{
    protected $statusCode = Response::HTTP_NOT_FOUND; // 404
    protected $errorCode = 'recipient_not_found';
}
