<?php

namespace App\Services;

class ApiDocumentationService
{
    public function generateDocumentation()
    {
        return [
            'title' => 'Wallet API Documentation',
            'version' => '1.0',
            'description' => 'API para gerenciamento de carteiras digitais e transações',
            'endpoints' => $this->getEndpointsDocumentation()
        ];
    }

    private function getEndpointsDocumentation()
    {
        return [
            [
                'path' => '/api/v1/register',
                'method' => 'POST',
                'description' => 'Registra um novo usuário',
                'parameters' => [
                    'name' => 'required|string|max:255',
                    'email' => 'required|string|email|max:255|unique:users',
                    'password' => 'required|string|min:8|confirmed',
                    'password_confirmation' => 'required|string',
                ],
                'responses' => [
                    '201' => [
                        'success' => true,
                        'message' => 'User registered successfully',
                        'data' => [
                            'user' => [
                                'id', 'name', 'email', 'created_at', 'updated_at'
                            ],
                            'token' => 'string'
                        ]
                    ],
                    '422' => [
                        'success' => false,
                        'message' => 'Validation failed',
                        'error' => 'validation_failed',
                        'errors' => ['campo' => ['mensagem de erro']]
                    ],
                ]
            ],
            [
                'path' => '/api/v1/login',
                'method' => 'POST',
                'description' => 'Login e obtenção de token',
                'parameters' => [
                    'email' => 'required|string|email',
                    'password' => 'required|string',
                ],
                'responses' => [
                    '200' => [
                        'success' => true,
                        'message' => 'Login successful',
                        'data' => [
                            'user' => [
                                'id', 'name', 'email', 'created_at', 'updated_at'
                            ],
                            'token' => 'string'
                        ]
                    ],
                    '401' => [
                        'success' => false,
                        'message' => 'Invalid login credentials',
                        'error' => 'invalid_credentials',
                        'data' => []
                    ],
                ]
            ],
            [
                'path' => '/api/v1/logout',
                'method' => 'POST',
                'description' => 'Logout e revogação do token',
                'auth' => 'Bearer token required',
                'responses' => [
                    '200' => [
                        'success' => true,
                        'message' => 'Logged out successfully',
                        'data' => []
                    ],
                    '401' => [
                        'success' => false,
                        'message' => 'Unauthenticated',
                        'error' => 'authentication_failed',
                        'data' => []
                    ],
                ]
            ],
            [
                'path' => '/api/v1/wallet',
                'method' => 'GET',
                'description' => 'Obtém detalhes da carteira do usuário',
                'auth' => 'Bearer token required',
                'responses' => [
                    '200' => [
                        'success' => true,
                        'message' => 'Wallet retrieved successfully',
                        'data' => [
                            'wallet' => [
                                'id', 'user_id', 'balance', 'status', 'user'
                            ]
                        ]
                    ],
                    '401' => [
                        'success' => false,
                        'message' => 'Unauthenticated',
                        'error' => 'authentication_failed',
                        'data' => []
                    ],
                ]
            ],
            [
                'path' => '/api/v1/wallet/deposit',
                'method' => 'POST',
                'description' => 'Deposita fundos na carteira',
                'auth' => 'Bearer token required',
                'parameters' => [
                    'amount' => 'required|numeric|min:0.01',
                ],
                'responses' => [
                    '201' => [
                        'success' => true,
                        'message' => 'Deposit successful',
                        'data' => [
                            'transaction' => [
                                'id', 'type', 'amount', 'status', 'recipient_id'
                            ],
                            'wallet' => [
                                'id', 'user_id', 'balance', 'status'
                            ]
                        ]
                    ],
                    '400' => [
                        'success' => false,
                        'message' => 'Wallet is blocked. Cannot make deposit.',
                        'error' => 'wallet_blocked',
                        'data' => []
                    ],
                    '422' => [
                        'success' => false,
                        'message' => 'Validation failed',
                        'error' => 'validation_failed',
                        'errors' => ['amount' => ['The amount field is required']]
                    ],
                ]
            ],
            [
                'path' => '/api/v1/wallet/transfer',
                'method' => 'POST',
                'description' => 'Transfere fundos para outro usuário',
                'auth' => 'Bearer token required',
                'parameters' => [
                    'recipient_id' => 'required|exists:users,id',
                    'amount' => 'required|numeric|min:0.01',
                ],
                'responses' => [
                    '201' => [
                        'success' => true,
                        'message' => 'Transfer successful',
                        'data' => [
                            'transaction' => [
                                'id', 'type', 'amount', 'sender_id', 'recipient_id', 'status'
                            ],
                            'wallet' => [
                                'id', 'user_id', 'balance', 'status'
                            ]
                        ]
                    ],
                    '400' => [
                        'success' => false,
                        'message' => 'Insufficient funds for transfer.',
                        'error' => 'insufficient_funds',
                        'data' => []
                    ],
                    '422' => [
                        'success' => false,
                        'message' => 'Validation failed',
                        'error' => 'validation_failed',
                        'errors' => ['field' => ['error message']]
                    ],
                ]
            ],
            [
                'path' => '/api/v1/transactions',
                'method' => 'GET',
                'description' => 'Lista o histórico de transações do usuário',
                'auth' => 'Bearer token required',
                'responses' => [
                    '200' => [
                        'success' => true,
                        'message' => 'Transactions retrieved successfully',
                        'data' => [
                            'transactions' => [
                                ['id', 'type', 'amount', 'sender_id', 'recipient_id', 'status']
                            ]
                        ]
                    ],
                    '401' => [
                        'success' => false,
                        'message' => 'Unauthenticated',
                        'error' => 'authentication_failed',
                        'data' => []
                    ],
                ]
            ],
            [
                'path' => '/api/v1/transactions/{id}',
                'method' => 'GET',
                'description' => 'Obtém detalhes de uma transação específica',
                'auth' => 'Bearer token required',
                'responses' => [
                    '200' => [
                        'success' => true,
                        'message' => 'Transaction retrieved successfully',
                        'data' => [
                            'transaction' => [
                                'id', 'type', 'amount', 'sender_id', 'recipient_id', 'status'
                            ]
                        ]
                    ],
                    '403' => [
                        'success' => false,
                        'message' => 'You are not authorized to view this transaction',
                        'error' => 'authorization_failed',
                        'data' => []
                    ],
                    '404' => [
                        'success' => false,
                        'message' => 'Resource not found',
                        'error' => 'not_found',
                        'data' => []
                    ],
                ]
            ],
            [
                'path' => '/api/v1/transactions/{id}/reverse',
                'method' => 'POST',
                'description' => 'Reverte uma transação',
                'auth' => 'Bearer token required',
                'responses' => [
                    '200' => [
                        'success' => true,
                        'message' => 'Transaction reversed successfully',
                        'data' => [
                            'transaction' => [
                                'id', 'type', 'amount', 'sender_id', 'recipient_id', 'status'
                            ],
                            'reversal' => [
                                'id', 'type', 'amount', 'sender_id', 'recipient_id', 'status'
                            ]
                        ]
                    ],
                    '400' => [
                        'success' => false,
                        'message' => 'Only completed transactions can be reversed.',
                        'error' => 'transaction_reversal_failed',
                        'data' => []
                    ],
                    '403' => [
                        'success' => false,
                        'message' => 'You are not authorized to reverse this transaction',
                        'error' => 'authorization_failed',
                        'data' => []
                    ],
                    '404' => [
                        'success' => false,
                        'message' => 'Resource not found',
                        'error' => 'not_found',
                        'data' => []
                    ],
                ]
            ],
        ];
    }
}
