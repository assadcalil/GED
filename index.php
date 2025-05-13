<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Arquivo de entrada principal
 * 
 * Este arquivo é o ponto de entrada principal do sistema.
 * Ele carrega as configurações, verifica autenticação e
 * redireciona para as páginas corretas.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Definir diretório raiz
define('ROOT_DIR', __DIR__);

// Incluir arquivos de configuração (CORRIGIDO: adicionando barra)
require_once ROOT_DIR . '/app/Config/App.php';
require_once ROOT_DIR . '/app/Config/ErrorHandler.php';
require_once ROOT_DIR . '/app/Config/Database.php';
require_once ROOT_DIR . '/app/Config/Auth.php';
require_once ROOT_DIR . '/app/Config/Logger.php';

// Iniciar sessão se ainda não estiver ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar cookie de "lembrar-me" se usuário não estiver logado
if (!Auth::isLoggedIn() && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    // Buscar token no banco de dados
    $sql = "SELECT u.* FROM users u 
            JOIN remember_tokens rt ON u.id = rt.user_id 
            WHERE rt.token = ? AND rt.expires_at > NOW() AND u.active = 1 
            LIMIT 1";
    
    $user = Database::selectOne($sql, [$token]);
    
    if ($user) {
        // Token válido, fazer login automático
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $user['type'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['last_activity'] = time();
        
        // Registrar login automático
        Logger::access('automático', "Login por cookie para usuário ID: {$user['id']}");
        
        // Atualizar último acesso
        Database::update('users', [
            'last_login' => date('Y-m-d H:i:s'),
            'last_ip' => $_SERVER['REMOTE_ADDR']
        ], 'id = ?', [$user['id']]);
    } else {
        // Token inválido ou expirado, remover cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

// Verificar se há solicitação de logout
if (isset($_GET['logout'])) {
    Auth::logout();
    Config::redirect('./login.php');
}

// Determinar rota com base na autenticação
if (Auth::isLoggedIn()) {
    // Usuário está logado
    
    // Verificar se URL é a raiz ou index
    $requestUri = $_SERVER['REQUEST_URI'];
    $baseDir = '/GED2.0'; // CORRIGIDO: Maiúsculas corretas
    
    // Remover o subdiretório da URI para comparação correta
    if (strpos($requestUri, $baseDir) === 0) {
        $cleanUri = substr($requestUri, strlen($baseDir));
    } else {
        $cleanUri = $requestUri;
    }
    
    if ($cleanUri == '/' || $cleanUri == '/index.php') {
        // Redirecionar para o dashboard
        Config::redirect('./views/dashboard/index.php');
    }
    
    // Definir cabeçalhos para evitar cache
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Obter página requisitada (sem parâmetros de consulta)
    $requestPath = parse_url($requestUri, PHP_URL_PATH);
    
    // Remover barra inicial para encontrar o arquivo
    $filePath = ltrim($requestPath, '/');
    
    // Remover o prefixo do diretório base se presente
    if (strpos($filePath, 'GED2.0/') === 0) {
        $filePath = substr($filePath, strlen('GED2.0/'));
    }
    
    // Verificar se é o dashboard sem extensão
    if ($filePath == 'dashboard') {
        $filePath = 'views/dashboard/index.php';
    }

    // Verificar se arquivo existe
    if (file_exists($filePath)) {
        // Se for um arquivo PHP, incluí-lo
        if (pathinfo($filePath, PATHINFO_EXTENSION) == 'php') {
            // Incluir o arquivo
            include $filePath;
        } else {
            // Para outros tipos de arquivo, entregar diretamente
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            
            header("Content-Type: {$mimeType}");
            readfile($filePath);
        }
    } else {
        // Verificar se é um diretório com index.php
        if (is_dir($filePath) && file_exists($filePath . '/index.php')) {
            include $filePath . '/index.php';
        } else {
            // Arquivo não encontrado
            header('HTTP/1.0 404 Not Found');
            include 'views/errors/404.php';
        }
    }
    
} else {
    // Usuário não está logado, redirecionar para página de login
    Config::redirect('./login.php');
}