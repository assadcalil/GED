<?php
/**
 * Sistema Contabilidade Estrela 3.0
 * Página de Login com Tema Escuro
 */

// Incluir arquivos necessários
require_once __DIR__ . '/app/Config/App.php';
require_once __DIR__ . '/app/Config/Database.php';
require_once __DIR__ . '/app/Config/ErrorHandler.php';
require_once __DIR__ . '/app/Config/Auth.php';
require_once __DIR__ . '/app/Config/Logger.php';

// Iniciar sessão se ainda não estiver ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário já está logado
if (Auth::isLoggedIn()) {
    // Redirecionar para o dashboard
    Config::redirect('views/dashboard/index.php');
}

// Variáveis para mensagens
$error = '';
$success = '';

// Processar login
if (Config::isPost()) {
    // Obter dados do formulário
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // Tentar autenticar
    list($result, $message) = Auth::login($username, $password);
    
    if ($result) {
        // Login bem-sucedido
        $success = $message;
        
        // Configurar cookie "lembrar-me" se solicitado
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + (86400 * 30); // 30 dias
            
            // Salvar token no banco de dados
            $data = [
                'user_id' => Auth::getCurrentUserId(),
                'token' => $token,
                'expires_at' => date('Y-m-d H:i:s', $expires),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            Database::insert('remember_tokens', $data);
            
            // Configurar cookie
            setcookie('remember_token', $token, $expires, '/', '', false, true);
        }
        
        // Redirecionar após login bem-sucedido
        $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'views/dashboard/index.php';
        unset($_SESSION['redirect_after_login']);
        
        // Registrar atividade
        Logger::access('bem-sucedido', "Login para {$username}");
        
        // Redirecionar
        Config::redirect($redirect);
    } else {
        // Login falhou
        $error = $message;
        
        // Registrar falha
        Logger::access('falhou', "Login falhou para {$username}", ['message' => $message]);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    
    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Estilo personalizado -->
    <link rel="stylesheet" href="/GED2.0/assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div class="logo-container">
                <img src="/GED2.0/assets/img/logo.png" alt="Logo Contabilidade Estrela" class="logo">
            </div>
            
            <h3 class="form-title">Acesso ao Sistema</h3>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="nome_usuario" required autocomplete="username">
                    <label for="username">Nome de Usuário</label>
                </div>
                
                <div class="form-floating mb-3 position-relative">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Senha" required autocomplete="current-password">
                    <label for="password">Senha</label>
                    <span class="password-toggle">
                        <i class="far fa-eye"></i>
                    </span>
                </div>
                
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Lembrar-me neste dispositivo</label>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i> Entrar
                </button>
                
                <a href="forgot-password.php" class="forgot-password">Esqueceu sua senha?</a>
            </form>
        </div>
    </div>
    
    <div class="system-name">
        <h2><?php echo SITE_NAME; ?></h2>
        <div class="version">Versão 3.0</div>
        <div class="version"> 2017 - <?php echo date('Y'); ?></div>
    </div>
    
    <!-- Bootstrap Bundle com Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script personalizado -->
    <script>
        // Script para mostrar/ocultar senha
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.querySelector('.password-toggle');
            
            passwordToggle.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="far fa-eye"></i>' : '<i class="far fa-eye-slash"></i>';
            });
            
            // Detectar se é um dispositivo móvel para melhor experiência
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            if (isMobile) {
                document.body.classList.add('mobile-device');
            }
        });
    </script>
</body>
</html>