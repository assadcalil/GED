<?php
/**
 * Access Denied Page
 * 
 * This page is displayed when a user attempts to access content 
 * without proper authorization.
 */

// Ensure proper HTTP status code is sent
http_response_code(403);

// You can add session checks or other logic here if needed
// Example: if(isset($_SESSION['redirect_url'])) { $redirect = $_SESSION['redirect_url']; }

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Negado</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            max-width: 600px;
            width: 90%;
            text-align: center;
        }
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            color: #e74c3c;
            margin: 0;
            line-height: 1;
        }
        .error-message {
            font-size: 1.5rem;
            margin: 1rem 0 2rem;
        }
        .description {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .button {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 0.8rem 1.5rem;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #2980b9;
        }
        .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔒</div>
        <h1 class="error-code">403</h1>
        <h2 class="error-message">Acesso Negado</h2>
        <p class="description">
            Você não tem permissão para acessar esta página. 
            Se você acredita que isso é um erro, entre em contato com o administrador 
            ou tente fazer login novamente.
        </p>
        <a href="index.php" class="button">Voltar para página inicial</a>
    </div>
</body>
</html>