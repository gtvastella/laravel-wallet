# Wallet App API

## Sobre o projeto

Wallet App é uma API REST desenlvoida em Laravel com princípios SOLID e containerizado em Docker. O sistema permite simular uma carteira digital; o usuário pode realizar depósitos, transferências e consultar transações.

## Funcionalidades principais

- Autenticação de usuários (cadastro, login, logout)
- Depósitos e transferências
- Histórico detalhado de transações
- Possibilidade de estornar transações

## Requisitos

- Docker e Docker Compose

## Instalação e configuração

1. Clone o repositório:
   ```
   git clone https://github.com/seu-usuario/wallet-app.git
   cd wallet-app
   ```

2. Inicie os containers:
   ```
   docker-compose up -d
   ```

3. A aplicação estará disponível em `http://localhost`. As migrations serão executadas automaticamente pela primeira vez, e um arquivo .env será criado também.

## Estrutura da API

A API segue uma estrutura RESTful com os seguintes endpoints:

### Autenticação

- `POST /api/v1/register` - Cadastro de novo usuário
- `POST /api/v1/login` - Login com email e senha
- `POST /api/v1/logout` - Logout (requer autenticação)

### Carteira

- `GET /api/v1/wallet` - Consulta dados da carteira
- `POST /api/v1/wallet/deposit` - Realiza um depósito
- `POST /api/v1/wallet/transfer` - Transfere fundos para outro usuário

### Transações

- `GET /api/v1/transactions` - Lista histórico de transações
- `GET /api/v1/transactions/{id}` - Consulta detalhes de uma transação
- `POST /api/v1/transactions/{id}/reverse` - Estorna uma transação

## Padrão de respostas

Todas as respostas da API seguem um padrão consistente:

```json
{
  "success": true|false,
  "message": "Descrição do resultado",
  "data": { 
  }
}
```

Em caso de erro:

```json
{
  "success": false,
  "message": "Descrição do erro",
  "error": "código_do_erro",
  "data": []
}
```

### Códigos de erro comuns

- `validation_failed` - Falha na validação dos dados de entrada
- `authentication_failed` - Erro de autenticação
- `authorization_failed` - Usuário não autorizado para a operação
- `not_found` - Recurso não encontrado
- `insufficient_funds` - Saldo insuficiente para transferência
- `wallet_blocked` - Carteira bloqueada
- `transaction_reversal_failed` - Falha ao estornar transação

## Exemplos de uso

### Registro de usuário

**Requisição:**
```
POST /api/v1/register
Content-Type: application/json

{
  "name": "João Silva",
  "email": "joao.silva@email.com",
  "password": "senha123"
}
```

**Resposta:**
```json
{
  "success": true,
  "message": "Usuário registrado com sucesso",
  "data": {
    "id": 1,
    "name": "João Silva",
    "email": "joao.silva@email.com"
  }
}
```

### Login de usuário

**Requisição:**
```
POST /api/v1/login
Content-Type: application/json

{
  "email": "joao.silva@email.com",
  "password": "senha123"
}
```

## Testes

Para executar os testes, utilize o seguinte comando:

```
docker-compose exec wallet_api_app php artisan test
```

### Executando testes

Os testes podem ser executados para garantir que todas as funcionalidades da API estão funcionando corretamente. Para rodar os testes, utilize o seguinte comando:

```
docker-compose exec wallet_api_app php artisan test
```

### Collection Postman

Uma collection Postman está disponível para facilitar o teste dos endpoints da API. Você pode importar a coleção diretamente no Postman utilizando o arquivo `wallet_api_collection.json` localizado na wallet-app/postman do projeto.

## Licença

Este projeto está licenciado sob a licença MIT. Veja o arquivo LICENSE para mais detalhes.

---

👨‍💻 Desenvolvido com 💚 por Gabriel