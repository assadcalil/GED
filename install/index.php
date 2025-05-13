<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Instalação do Banco de Dados
 * 
 * Este script cria as tabelas necessárias para o funcionamento
 * inicial do sistema e configura o usuário administrador.
 */

// Evitar acesso direto após instalação
$lockFile = __DIR__ . '/install.lock';
if (file_exists($lockFile)) {
    die('O sistema já foi instalado. Por segurança, este arquivo foi bloqueado.');
}

// Incluir arquivo de permissões
require_once __DIR__ . '/permissions.php';

// Variáveis para armazenar mensagens
$messages = [];
$errors = [];
$installed = false;

// Função para criar o arquivo de configuração do banco de dados
function createDbConfigFile($host, $dbname, $username, $password) {
    $configContent = <<<EOT
<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Configuração do Banco de Dados
 * 
 * Este arquivo foi gerado automaticamente durante a instalação.
 * Não edite manualmente a menos que saiba o que está fazendo.
 */

// Definições de conexão com o banco de dados
define('DB_HOST', '{$host}');
define('DB_NAME', '{$dbname}');
define('DB_USER', '{$username}');
define('DB_PASS', '{$password}');
define('DB_CHARSET', 'utf8mb4');

EOT;

    // Criar o diretório config se não existir
    if (!is_dir(__DIR__ . '/../config')) {
        mkdir(__DIR__ . '/../config', 0755, true);
    }
    
    // Salvar o arquivo de configuração
    return file_put_contents(__DIR__ . '/.././../app/Config/Database.php', $configContent) !== false;
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter parâmetros de conexão
    $host = $_POST['host'] ?? 'localhost';
    $dbname = $_POST['dbname'] ?? 'contabilidade_estrela';
    $username = $_POST['username'] ?? 'root';
    $password = $_POST['password'] ?? '';
    $createDb = isset($_POST['create_db']);
    
    // Obter dados do administrador
    $adminName = $_POST['admin_name'] ?? 'Administrador';
    $adminEmail = $_POST['admin_email'] ?? '';
    $adminUsername = $_POST['admin_username'] ?? 'admin';
    $adminPassword = $_POST['admin_password'] ?? '';
    $adminPasswordConfirm = $_POST['admin_password_confirm'] ?? '';
    
    // Validar dados
    $isValid = true;
    
    if (empty($host)) {
        $errors[] = "O host do banco de dados é obrigatório.";
        $isValid = false;
    }
    
    if (empty($dbname)) {
        $errors[] = "O nome do banco de dados é obrigatório.";
        $isValid = false;
    }
    
    if (empty($username)) {
        $errors[] = "O usuário do banco de dados é obrigatório.";
        $isValid = false;
    }
    
    if (empty($adminName)) {
        $errors[] = "O nome do administrador é obrigatório.";
        $isValid = false;
    }
    
    if (empty($adminEmail)) {
        $errors[] = "O e-mail do administrador é obrigatório.";
        $isValid = false;
    } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "O e-mail do administrador é inválido.";
        $isValid = false;
    }
    
    if (empty($adminUsername)) {
        $errors[] = "O nome de usuário do administrador é obrigatório.";
        $isValid = false;
    } elseif (strlen($adminUsername) < 4) {
        $errors[] = "O nome de usuário deve ter pelo menos 4 caracteres.";
        $isValid = false;
    }
    
    if (empty($adminPassword)) {
        $errors[] = "A senha do administrador é obrigatória.";
        $isValid = false;
    } elseif (strlen($adminPassword) < 8) {
        $errors[] = "A senha do administrador deve ter pelo menos 8 caracteres.";
        $isValid = false;
    } elseif ($adminPassword !== $adminPasswordConfirm) {
        $errors[] = "As senhas não conferem.";
        $isValid = false;
    }
    
    // Se tudo estiver válido, prosseguir com a instalação
    if ($isValid) {
        try {
            // Tentar conectar ao servidor MySQL
            $dsn = "mysql:host={$host}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $pdo = new PDO($dsn, $username, $password, $options);
            $messages[] = "Conexão ao servidor MySQL estabelecida com sucesso.";
            
            // Criar banco de dados se solicitado
            if ($createDb) {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $messages[] = "Banco de dados '{$dbname}' criado com sucesso.";
            }
            
            // Selecionar banco de dados
            $pdo->exec("USE `{$dbname}`");
            $messages[] = "Banco de dados '{$dbname}' selecionado.";
            
            // Criar tabela de usuários
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `users` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) NOT NULL,
                    `email` varchar(191) NOT NULL,
                    `username` varchar(50) NOT NULL,
                    `password` varchar(255) NOT NULL,
                    `type` tinyint(1) NOT NULL DEFAULT 4 COMMENT '1=Admin, 2=Editor, 3=Tax, 4=Employee, 5=Financial, 6=Client',
                    `phone` varchar(20) DEFAULT NULL,
                    `cpf` varchar(14) DEFAULT NULL,
                    `profile_image` varchar(255) DEFAULT NULL,
                    `active` tinyint(1) NOT NULL DEFAULT 1,
                    `last_login` datetime DEFAULT NULL,
                    `last_ip` varchar(45) DEFAULT NULL,
                    `created_at` datetime NOT NULL,
                    `created_by` int(11) DEFAULT NULL,
                    `updated_at` datetime DEFAULT NULL,
                    `updated_by` int(11) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `email` (`email`(191)),
                    UNIQUE KEY `username` (`username`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $messages[] = "Tabela 'users' criada com sucesso.";
            
            // Criar tabela de permissões
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `permissions` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `permission_name` varchar(100) NOT NULL,
                    `description` varchar(255) DEFAULT NULL,
                    `created_at` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `permission_name` (`permission_name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $messages[] = "Tabela 'permissions' criada com sucesso.";
            
            // Criar tabela de permissões de usuário
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `user_permissions` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) NOT NULL,
                    `permission_id` int(11) NOT NULL,
                    `granted_by` int(11) NOT NULL,
                    `granted_at` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `user_permission` (`user_id`, `permission_id`),
                    KEY `user_id` (`user_id`),
                    KEY `permission_id` (`permission_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $messages[] = "Tabela 'user_permissions' criada com sucesso.";
            
            // Criar tabela de tentativas de login
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `login_attempts` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `username` varchar(255) NOT NULL,
                    `ip_address` varchar(45) NOT NULL,
                    `user_agent` text DEFAULT NULL,
                    `reason` varchar(255) DEFAULT NULL,
                    `attempt_date` datetime NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `ip_address` (`ip_address`),
                    KEY `attempt_date` (`attempt_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $messages[] = "Tabela 'login_attempts' criada com sucesso.";
            
            // Criar tabela de logs de acesso
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `access_logs` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) NOT NULL,
                    `ip_address` varchar(45) NOT NULL,
                    `user_agent` text DEFAULT NULL,
                    `login_time` datetime NOT NULL,
                    `logout_time` datetime DEFAULT NULL,
                    `success` tinyint(1) NOT NULL DEFAULT 1,
                    PRIMARY KEY (`id`),
                    KEY `user_id` (`user_id`),
                    KEY `login_time` (`login_time`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $messages[] = "Tabela 'access_logs' criada com sucesso.";
            
            // Criar tabela de tokens "lembrar-me"
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `remember_tokens` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) NOT NULL,
                    `token` varchar(64) NOT NULL,
                    `ip_address` varchar(45) NOT NULL,
                    `expires_at` datetime NOT NULL,
                    `created_at` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `token` (`token`),
                    KEY `user_id` (`user_id`),
                    KEY `expires_at` (`expires_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $messages[] = "Tabela 'remember_tokens' criada com sucesso.";
            
            // Criar tabela de logs do sistema
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `system_logs` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `log_type` varchar(50) NOT NULL,
                    `log_level` varchar(20) NOT NULL,
                    `message` text NOT NULL,
                    `context` text DEFAULT NULL,
                    `ip_address` varchar(45) DEFAULT NULL,
                    `user_id` int(11) DEFAULT NULL,
                    `user_agent` text DEFAULT NULL,
                    `request_uri` varchar(255) DEFAULT NULL,
                    `created_at` datetime NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `user_id` (`user_id`),
                    KEY `log_type` (`log_type`),
                    KEY `log_level` (`log_level`),
                    KEY `created_at` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $messages[] = "Tabela 'system_logs' criada com sucesso.";
            
            // Inserir permissões
            $permissionsCount = insertPermissions($pdo, $permissions);
            $messages[] = "Foram inseridas {$permissionsCount} permissões no sistema.";
            
            // Criar usuário administrador
            $passwordHash = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $now = date('Y-m-d H:i:s');
            
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, username, password, type, active, created_at)
                VALUES (?, ?, ?, ?, 1, 1, ?)
            ");
            
            $stmt->execute([$adminName, $adminEmail, $adminUsername, $passwordHash, $now]);
            $adminId = $pdo->lastInsertId();
            
            $messages[] = "Usuário administrador criado com sucesso (ID: {$adminId}).";
            
            // Atribuir todas as permissões ao administrador
            $permissionsAssigned = assignAdminPermissions($pdo, $adminId);
            $messages[] = "Foram atribuídas {$permissionsAssigned} permissões ao administrador.";
            
            // Criar arquivo de configuração do banco de dados
            if (createDbConfigFile($host, $dbname, $username, $password)) {
                $messages[] = "Arquivo de configuração do banco de dados criado com sucesso.";
            } else {
                $errors[] = "Não foi possível criar o arquivo de configuração do banco de dados.";
            }
            
            // Criar arquivo de bloqueio para evitar reinstalação
            if (file_put_contents($lockFile, date('Y-m-d H:i:s'))) {
                $messages[] = "Arquivo de bloqueio de instalação criado com sucesso.";
            } else {
                $errors[] = "Não foi possível criar o arquivo de bloqueio de instalação.";
            }
            
            // Marcar instalação como concluída
            $installed = true;
            
        } catch (PDOException $e) {
            $errors[] = "Erro no banco de dados: " . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = "Erro: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Sistema Contabilidade Estrela 2.0</title>
    
    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #333;
            min-height: 100vh;
            padding: 40px 0;
        }
        
        .install-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo {
            max-width: 200px;
            height: auto;
        }
        
        .system-title {
            font-size: 24px;
            font-weight: 600;
            margin-top: 15px;
            color: #007bff;
        }
        
        .system-version {
            font-size: 14px;
            color: #6c757d;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #f0f0f0;
            padding: 15px 20px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .form-label {
            font-weight: 500;
        }
        
        .form-text {
            font-size: 12px;
        }
        
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }
        
        .message-list {
            margin-bottom: 20px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .message-item {
            padding: 8px 15px;
            margin-bottom: 5px;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .message-success {
            background-color: rgba(40, 167, 69, 0.1);
            border-left: 3px solid #28a745;
            color: #28a745;
        }
        
        .message-error {
            background-color: rgba(220, 53, 69, 0.1);
            border-left: 3px solid #dc3545;
            color: #dc3545;
        }
        
        .copyright {
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            margin-top: 20px;
        }
        
        .completed-icon {
            font-size: 64px;
            color: #28a745;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container install-container">
        <div class="logo-container">
            <img src="../assets/img/logo.png" alt="Logo Contabilidade Estrela" class="logo">
            <h1 class="system-title">Sistema Contabilidade Estrela</h1>
            <div class="system-version">Instalação da Versão 2.0</div>
        </div>
        
        <?php if ($installed): ?>
            <div class="card">
                <div class="card-body text-center">
                    <div class="completed-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="card-title mb-4">Instalação Concluída!</h2>
                    
                    <div class="message-list">
                        <?php foreach ($messages as $message): ?>
                            <div class="message-item message-success">
                                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php foreach ($errors as $error): ?>
                            <div class="message-item message-error">
                                <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <p>O Sistema Contabilidade Estrela 2.0 foi instalado com sucesso!</p>
                    <p>Você já pode acessar o sistema utilizando as credenciais do administrador:</p>
                    <p><strong>Usuário:</strong> <?php echo htmlspecialchars($_POST['admin_username'] ?? 'admin'); ?></p>
                    
                    <a href="../login.php" class="btn btn-primary btn-lg mt-3">
                        <i class="fas fa-sign-in-alt me-2"></i> Acessar o Sistema
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i> Ocorreram erros durante a instalação:</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Configuração do Banco de Dados</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="" class="needs-validation" novalidate>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="host" class="form-label">Host do Banco de Dados</label>
                                <input type="text" class="form-control" id="host" name="host" value="localhost" required>
                                <div class="form-text">Geralmente "localhost" ou um endereço IP.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="dbname" class="form-label">Nome do Banco de Dados</label>
                                <input type="text" class="form-control" id="dbname" name="dbname" value="estrela_contabilidade" required>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Usuário do Banco de Dados</label>
                                <input type="text" class="form-control" id="username" name="username" value="root" required>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Senha do Banco de Dados</label>
                                <input type="password" class="form-control" id="password" name="password">
                                <div class="form-text">Deixe em branco se não houver senha.</div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="create_db" name="create_db" checked>
                                <label class="form-check-label" for="create_db">
                                    Criar banco de dados se não existir
                                </label>
                            </div>
                        </div>
                        
                        <div class="card-header mt-5 mb-4">
                            <h5 class="card-title">Configuração do Administrador</h5>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="admin_name" class="form-label">Nome do Administrador</label>
                                <input type="text" class="form-control" id="admin_name" name="admin_name" value="Administrador" required>
                            </div>
                            <div class="col-md-6">
                                <label for="admin_email" class="form-label">E-mail do Administrador</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label for="admin_username" class="form-label">Nome de Usuário</label>
                                <input type="text" class="form-control" id="admin_username" name="admin_username" value="admin" required>
                                <div class="form-text">Mínimo de 4 caracteres, sem espaços.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="admin_password" class="form-label">Senha</label>
                                <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                                <div class="form-text">Mínimo de 8 caracteres.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="admin_password_confirm" class="form-label">Confirmar Senha</label>
                                <input type="password" class="form-control" id="admin_password_confirm" name="admin_password_confirm" required>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> O usuário administrador terá acesso completo a todas as funcionalidades do sistema.
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-cog me-2"></i> Instalar Sistema
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="copyright">
            GED Contabilidade Estrela &copy; <?php echo date('Y'); ?>
        </div>
    </div>
    
    <!-- Bootstrap Bundle com Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Validação de formulário
        (function() {
            'use strict';
            
            var forms = document.querySelectorAll('.needs-validation');
            
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>