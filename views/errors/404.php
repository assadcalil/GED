<?php
/**
 * Sistema GED 2.0
 * Página de Erro 404 (Não Encontrado)
 */

 ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Definir código de status HTTP
http_response_code(404);

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(dirname(__FILE__))));
}

// Incluir arquivos de configuração
require_once ROOT_DIR . '/app/Config/App.php';
require_once ROOT_DIR . '/app/Config/Logger.php';

// Registrar tentativa de acesso a página não encontrada
Logger::access('erro', "Página não encontrada: {$_SERVER['REQUEST_URI']}", [
    'ip' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Não identificado'
]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro 404 - <?php echo SITE_NAME; ?></title>
    
    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --background-gradient: linear-gradient(135deg, rgba(0,123,255,0.7) 0%, rgba(0,123,255,0.4) 100%);
        }
        
        body, html {
            height: 100%;
            font-family: 'Poppins', sans-serif;
            background: var(--background-gradient);
            color: white;
            overflow: hidden;
        }
        
        .error-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
            padding: 20px;
            animation: fadeIn 0.6s ease-out;
        }
        
        .error-code {
            font-size: 120px;
            font-weight: 700;
            color: white;
            text-shadow: 0 10px 20px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        
        .error-message {
            max-width: 600px;
            font-size: 24px;
            margin-bottom: 30px;
            color: rgba(255,255,255,0.9);
        }
        
        .error-details {
            background-color: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 20px;
            max-width: 800px;
            width: 100%;
            margin-bottom: 30px;
        }
        
        .error-details-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 10px;
        }
        
        .error-details-item:last-child {
            border-bottom: none;
        }
        
        .error-details-item strong {
            color: rgba(255,255,255,0.7);
        }
        
        .btn-action {
            background-color: white;
            color: var(--primary-color);
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s;
            margin: 0 10px;
        }
        
        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(0,0,0,0.2);
        }
        
        .troubleshoot-section {
            max-width: 600px;
            background-color: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .troubleshoot-section h5 {
            color: white;
            margin-bottom: 15px;
            border-bottom: 2px solid rgba(255,255,255,0.2);
            padding-bottom: 10px;
        }
        
        .troubleshoot-section ul {
            list-style-type: none;
            padding: 0;
        }
        
        .troubleshoot-section li {
            margin-bottom: 10px;
            color: rgba(255,255,255,0.8);
        }
        
        .troubleshoot-section li::before {
            content: '•';
            color: white;
            font-weight: bold;
            display: inline-block;
            width: 1em;
            margin-left: -1em;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .error-code {
                font-size: 80px;
            }
            .error-message {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        
        <div class="error-message">
            Desculpe, a página que você está procurando não foi encontrada no sistema. <br>Contabilidade Estrela <br>GED 2.0
        </div>
        
        <div class="error-details">
            <div class="error-details-item">
                <strong>URL Solicitada:</strong> 
                <span><?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'Não identificada'); ?></span>
            </div>
            <div class="error-details-item">
                <strong>Endereço IP:</strong> 
                <span><?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'Não disponível'); ?></span>
            </div>
            <div class="error-details-item">
                <strong>Data/Hora:</strong> 
                <span><?php echo date('Y-m-d H:i:s'); ?></span>
            </div>
        </div>
        
        <div>
            <a href="/ged2.0/views/dashboard/index.php" class="btn btn-action">
                <i class="fas fa-home me-2"></i>Ir para Dashboard
            </a>
            <a href="#" onclick="window.history.back();" class="btn btn-action">
                <i class="fas fa-arrow-left me-2"></i>Voltar
            </a>
        </div>
        
        <div class="troubleshoot-section">
            <h5><i class="fas fa-tools me-2"></i>Possíveis Causas</h5>
            <ul>
                <li>O endereço da página pode estar incorreto</li>
                <li>A página pode ter sido movida ou excluída</li>
                <li>Você pode ter seguido um link quebrado</li>
                <li>Erro temporário no sistema</li>
            </ul>
        </div>
    </div>
    
    <!-- Bootstrap Bundle com Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>