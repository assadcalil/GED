<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Sistema de Autenticação
 * 
 * Este arquivo contém funções para autenticação, autorização
 * e gerenciamento de sessões de usuários.
 */

// Iniciar sessão se ainda não estiver ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class Auth {
    // Tipos de usuário do sistema
    const ADMIN = 1;
    const EDITOR = 2;
    const TAX = 3; // Imposto de renda
    const EMPLOYEE = 4; // Funcionário
    const FINANCIAL = 5; // Financeiro
    const CLIENT = 6; // Cliente

    // Nomes legíveis dos tipos de usuário
    public static $userTypes = [
        self::ADMIN => 'Administrador',
        self::EDITOR => 'Editor',
        self::TAX => 'Imposto de Renda',
        self::EMPLOYEE => 'Funcionário',
        self::FINANCIAL => 'Financeiro',
        self::CLIENT => 'Cliente'
    ];

    /**
     * Tenta autenticar um usuário
     */
    public static function login($username, $password) {
        try {
            // Validar entrada
            if (empty($username) || empty($password)) {
                self::logFailedAttempt($username, 'Campos vazios');
                return [false, 'Usuário e senha são obrigatórios'];
            }

            // Obter usuário pelo nome de usuário
            $sql = "SELECT * FROM users WHERE username = ? AND active = 1 LIMIT 1";
            $user = Database::selectOne($sql, [$username]);

            // Verificar se o usuário existe
            if (!$user) {
                self::logFailedAttempt($username, 'Usuário não encontrado');
                return [false, 'Credenciais inválidas'];
            }

            // Verificar se a senha está correta
            if (!password_verify($password, $user['password'])) {
                self::logFailedAttempt($username, 'Senha incorreta');
                return [false, 'Credenciais inválidas'];
            }

            // Autenticação bem-sucedida, atualizar último acesso
            Database::update('users', [
                'last_login' => date('Y-m-d H:i:s'),
                'last_ip' => $_SERVER['REMOTE_ADDR']
            ], 'id = ?', [$user['id']]);

            // Registrar log de acesso
            self::logAccess($user['id'], true);

            // Iniciar sessão do usuário
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['type'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['last_activity'] = time();

            return [true, 'Login realizado com sucesso'];
        } catch (Exception $e) {
            // Registrar erro de login
            ErrorHandler::handleException($e);
            return [false, 'Erro durante o login'];
        }
    }

    /**
     * Encerra a sessão do usuário
     */
    public static function logout() {
        // Verificar se há um usuário logado
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            
            // Limpar todas as variáveis de sessão
            $_SESSION = [];
            
            // Destruir o cookie da sessão
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            
            // Destruir a sessão
            session_destroy();
            
            // Registrar logout nos logs
            self::logLogout($userId);
            
            return true;
        }
        
        return false;
    }

    /**
     * Verifica se o usuário está autenticado
     */
    public static function isLoggedIn() {
        // Verificar se existe ID de usuário na sessão
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            return false;
        }
        
        // Verificar timeout da sessão (30 minutos de inatividade)
        $timeout = 30 * 60; // 30 minutos em segundos
        
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            // Sessão expirou, fazer logout
            self::logout();
            return false;
        }
        
        // Atualizar tempo da última atividade
        $_SESSION['last_activity'] = time();
        
        return true;
    }

    /**
     * Verifica se o usuário atual tem o tipo especificado
     */
    public static function isUserType($type) {
        if (!self::isLoggedIn()) {
            header('Location: /ged2.0/login.php');
            exit;
        }
        
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] == $type;
    }

    /**
     * Verifica se o usuário atual é administrador
     */
    public static function isAdmin() {
        return self::isUserType(self::ADMIN);
    }

    /**
     * Verifica se o usuário tem permissão para acessar uma funcionalidade
     */
    public static function hasPermission($permission) {
        if (!self::isLoggedIn()) {
            header('Location: /ged2.0/login.php');
            exit;
        }
        
        // Se for administrador, tem todas as permissões
        if (self::isAdmin()) {
            return true;
        }
        
        // Buscar permissões do usuário no banco de dados
        $sql = "SELECT p.permission_name FROM user_permissions up
                JOIN permissions p ON up.permission_id = p.id
                WHERE up.user_id = ?";
        
        $permissions = Database::select($sql, [$_SESSION['user_id']]);
        
        // Verificar se a permissão específica existe
        foreach ($permissions as $p) {
            if ($p['permission_name'] == $permission) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Redireciona se o usuário não estiver autenticado
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            // Salvar URL atual para redirecionamento após login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            
            // Redirecionar para a página de login
            header('Location: /ged2.0/login.php');
            exit;
        }
    }

    /**
     * Redireciona se o usuário não tiver a permissão especificada
     */
    public static function requirePermission($permission) {
        // Primeiro, garantir que o usuário está logado
        self::requireLogin();
        
        // Depois, verificar permissão
        if (!self::hasPermission($permission)) {
            // Redirecionar para página de acesso negado
            header('Location: /access-denied.php');
            exit;
        }
    }

    /**
     * Redireciona se o usuário não for do tipo especificado
     */
    public static function requireUserType($type) {
        // Primeiro, garantir que o usuário está logado
        self::requireLogin();
        
        // Depois, verificar tipo de usuário
        if (!self::isUserType($type)) {
            // Redirecionar para página de acesso negado
            header('Location: /access-denied.php');
            exit;
        }
    }

    /**
     * Obtém o tipo de usuário atual como string
     */
    public static function getUserTypeName() {
        if (!self::isLoggedIn()) {
            header('Location: /ged2.0/login.php');
            exit;
        }
        
        // Check if user_type exists in session
        if (!isset($_SESSION['user_type'])) {
            return 'Desconhecido';
        }
        
        $type = $_SESSION['user_type'];
        return isset(self::$userTypes[$type]) ? self::$userTypes[$type] : 'Desconhecido';
    }

    /**
     * Registra tentativa de login nos logs
     */
    private static function logFailedAttempt($username, $reason) {
        // Obter informações do cliente
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $date = date('Y-m-d H:i:s');
        
        // Registrar no banco de dados
        $data = [
            'username' => $username,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'reason' => $reason,
            'attempt_date' => $date
        ];
        
        Database::insert('login_attempts', $data);
        
        // Registrar no arquivo de log
        $logMessage = "[$date] [$ip] Falha de login para '$username': $reason" . PHP_EOL;
        error_log($logMessage, 3, LOG_DIR . '/login_failures.log');
    }
    
    /**
     * Registra acesso bem-sucedido nos logs
     */
    private static function logAccess($userId, $success) {
        // Obter informações do cliente
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $date = date('Y-m-d H:i:s');
        
        // Registrar no banco de dados
        $data = [
            'user_id' => $userId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'login_time' => $date,
            'success' => $success ? 1 : 0
        ];
        
        Database::insert('access_logs', $data);
        
        // Registrar no arquivo de log
        $logMessage = "[$date] [$ip] Acesso " . ($success ? 'bem-sucedido' : 'falhou') . " para usuário ID: $userId" . PHP_EOL;
        error_log($logMessage, 3, LOG_DIR . '/access.log');
    }
    
    /**
     * Registra logout nos logs
     */
    private static function logLogout($userId) {
        // Obter informações do cliente
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $date = date('Y-m-d H:i:s');
        
        // Registrar no arquivo de log
        $logMessage = "[$date] [$ip] Logout para usuário ID: $userId" . PHP_EOL;
        error_log($logMessage, 3, LOG_DIR . '/access.log');
        
        // Atualizar registro de acesso no banco de dados
        $sql = "UPDATE access_logs SET logout_time = ? WHERE user_id = ? AND logout_time IS NULL ORDER BY id DESC LIMIT 1";
        Database::query($sql, [$date, $userId]);
    }
    
    /**
     * Gera uma senha aleatória segura
     */
    public static function generatePassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+;:,.?';
        $password = '';
        
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        
        return $password;
    }
    
    /**
     * Verifica a força da senha
     * Retorna array com: [bool $forte, string $mensagem]
     */
    public static function checkPasswordStrength($password) {
        // Critérios de força
        $minLength = 8;
        $requiresUpper = true;
        $requiresLower = true;
        $requiresNumber = true;
        $requiresSpecial = true;
        
        // Mensagens de erro
        $errors = [];
        
        // Verificar comprimento
        if (strlen($password) < $minLength) {
            $errors[] = "A senha deve ter pelo menos {$minLength} caracteres";
        }
        
        // Verificar letra maiúscula
        if ($requiresUpper && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "A senha deve conter pelo menos uma letra maiúscula";
        }
        
        // Verificar letra minúscula
        if ($requiresLower && !preg_match('/[a-z]/', $password)) {
            $errors[] = "A senha deve conter pelo menos uma letra minúscula";
        }
        
        // Verificar número
        if ($requiresNumber && !preg_match('/[0-9]/', $password)) {
            $errors[] = "A senha deve conter pelo menos um número";
        }
        
        // Verificar caractere especial
        if ($requiresSpecial && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = "A senha deve conter pelo menos um caractere especial";
        }
        
        // Verificar se há erros
        if (count($errors) > 0) {
            return [false, implode("; ", $errors)];
        }
        
        return [true, "Senha forte"];
    }
    
    /**
     * Cria hash da senha usando bcrypt
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Obtém informações do usuário atual
     */
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            header('Location: /ged2.0/login.php');
            exit;
        }
        
        $sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
        return Database::selectOne($sql, [$_SESSION['user_id']]);
    }
    
    /**
     * Obtém o ID do usuário atual
     */
    public static function getCurrentUserId() {
        if (!self::isLoggedIn()) {
            header('Location: /ged2.0/login.php');
            exit;
        }
        
        return $_SESSION['user_id'];
    }
}