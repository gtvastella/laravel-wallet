<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ApiDocumentationService;

class ApiDocumentationController extends Controller
{
    protected $apiDocService;

    public function __construct(ApiDocumentationService $apiDocService)
    {
        $this->apiDocService = $apiDocService;
    }

    public function index()
    {
        $documentation = $this->apiDocService->generateDocumentation();

        return response()->json([
            'success' => true,
            'message' => 'API documentation retrieved successfully',
            'data' => $documentation
        ]);
    }
}
