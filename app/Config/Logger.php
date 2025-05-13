<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Sistema de Logs
 * 
 * Este arquivo contém funções para registro de logs de atividades,
 * acessos e operações do sistema.
 */

// Definir constante para o diretório de logs
if (!defined('LOG_DIR')) {
    define('LOG_DIR', dirname(__DIR__) . '/logs');
    
    // Garantir que o diretório de logs existe
    if (!file_exists(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
}

class Logger {
    // Tipos de logs
    const ACCESS = 'access';
    const ERROR = 'error';
    const ACTIVITY = 'activity';
    const SECURITY = 'security';
    const UPLOAD = 'upload';
    const DATABASE = 'database';
    const EMAIL = 'email';
    
    // Níveis de logs
    const DEBUG = 'debug';
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR_LEVEL = 'error';
    const CRITICAL = 'critical';
    
    /**
     * Registra um evento nos logs
     */
    public static function log($type, $level, $message, $context = []) {
        // Garantir que o diretório de logs existe
        if (!file_exists(LOG_DIR)) {
            mkdir(LOG_DIR, 0755, true);
        }
        
        // Obter detalhes do contexto
        $date = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Não autenticado';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $requestUri = $_SERVER['REQUEST_URI'] ?? 'Unknown';
        
        // Formatar a mensagem
        $logMessage = "[{$date}] [{$ip}] [{$userId}] [{$level}] {$message}";
        
        // Adicionar contexto se existir
        if (!empty($context)) {
            $contextString = json_encode($context, JSON_UNESCAPED_UNICODE);
            $logMessage .= " | Contexto: {$contextString}";
        }
        
        $logMessage .= PHP_EOL;
        
        // Definir arquivo de log baseado no tipo
        $logFile = LOG_DIR . "/{$type}.log";
        
        // Registrar no arquivo
        error_log($logMessage, 3, $logFile);
        
        // Se for um erro ou crítico, também registrar no banco de dados
        if ($level == self::ERROR_LEVEL || $level == self::CRITICAL) {
            self::logToDatabase($type, $level, $message, $context, $ip, $userId, $userAgent, $requestUri);
        }
        
        return true;
    }
    
    /**
     * Registra um evento no banco de dados
     */
    private static function logToDatabase($type, $level, $message, $context, $ip, $userId, $userAgent, $requestUri) {
        // Converter contexto para JSON
        $contextJson = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : null;
        
        // Preparar dados para inserção
        $data = [
            'log_type' => $type,
            'log_level' => $level,
            'message' => $message,
            'context' => $contextJson,
            'ip_address' => $ip,
            'user_id' => $userId != 'Não autenticado' ? $userId : null,
            'user_agent' => $userAgent,
            'request_uri' => $requestUri,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Inserir no banco de dados
        try {
            Database::insert('system_logs', $data);
        } catch (Exception $e) {
            // Falha ao inserir no banco, registrar apenas no arquivo
            $errorMessage = "[" . date('Y-m-d H:i:s') . "] Falha ao registrar log no banco de dados: " . $e->getMessage() . PHP_EOL;
            error_log($errorMessage, 3, LOG_DIR . "/db_log_errors.log");
        }
    }
    
    /**
     * Método de compatibilidade para o sistema de certificados
     * Registra uma atividade específica com tipo definido
     */
    public static function activity($tipo, $mensagem, $usuario = null) {
        // Se não foi informado o usuário, tenta obter da sessão
        if (is_null($usuario) && isset($_SESSION['user_login'])) {
            $usuario = $_SESSION['user_login'];
        } else if (is_null($usuario) && isset($_SESSION['user_id'])) {
            $usuario = $_SESSION['user_id'];
        } else if (is_null($usuario)) {
            $usuario = 'sistema';
        }
        
        // Registrar no log usando o método principal
        $context = [
            'usuario' => $usuario,
            'ip' => self::getClientIP()
        ];
        
        return self::log(self::ACTIVITY, self::INFO, "{$tipo}: {$mensagem}", $context);
    }
    
    /**
     * Método específico para registrar envios de certificados
     */
    public static function certificado($acao, $mensagem, $dados = []) {
        // Registrar atividade no log principal
        self::activity('certificado', "{$acao} - {$mensagem}");
        
        // Se há dados específicos para log de certificados, registrar na tabela especializada
        if (!empty($dados)) {
            try {
                // Formatar dados para inserção
                $logData = [
                    'emp_code' => $dados['emp_code'] ?? '',
                    'emp_name' => $dados['emp_name'] ?? '',
                    'tipo_certificado' => $dados['tipo_certificado'] ?? '',
                    'data_renovacao' => $dados['data_renovacao'] ?? null,
                    'certificado_vencimento' => $dados['certificado_vencimento'] ?? null,
                    'emails_destinatario' => $dados['emails_destinatario'] ?? '',
                    'nome_arquivo' => $dados['nome_arquivo'] ?? '',
                    'data_envio' => date('Y-m-d H:i:s'),
                    'sucesso' => $dados['sucesso'] ?? 0,
                    'mensagem_erro' => $dados['mensagem_erro'] ?? ''
                ];
                
                // Inserir no banco
                Database::insert('logs_certificados', $logData);
                
                return true;
            } catch (Exception $e) {
                // Falha ao inserir no banco, registrar o erro
                self::error("Falha ao registrar log de certificado: " . $e->getMessage(), $dados);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Registra acesso ao sistema
     */
    public static function access($status, $message = '', $context = []) {
        return self::log(self::ACCESS, self::INFO, "Acesso {$status}: {$message}", $context);
    }
    
    /**
     * Registra erro do sistema
     */
    public static function error($message, $context = []) {
        return self::log(self::ERROR, self::ERROR_LEVEL, $message, $context);
    }
    
    /**
     * Registra erro crítico do sistema
     */
    public static function critical($message, $context = []) {
        return self::log(self::ERROR, self::CRITICAL, $message, $context);
    }
    
    /**
     * Registra evento de segurança
     */
    public static function security($action, $status, $details = '', $context = []) {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Não autenticado';
        $message = "Segurança [{$status}] {$action} - Usuário {$userId}";
        
        if (!empty($details)) {
            $message .= " - {$details}";
        }
        
        $level = ($status === 'falha' || $status === 'violação') ? self::WARNING : self::INFO;
        
        return self::log(self::SECURITY, $level, $message, $context);
    }
    
    /**
     * Registra upload de arquivo
     */
    public static function upload($filename, $status, $details = '', $context = []) {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Não autenticado';
        $message = "Upload {$status} do arquivo '{$filename}' - Usuário {$userId}";
        
        if (!empty($details)) {
            $message .= " - {$details}";
        }
        
        $level = ($status === 'falha') ? self::WARNING : self::INFO;
        
        return self::log(self::UPLOAD, $level, $message, $context);
    }
    
    /**
     * Registra operação no banco de dados
     */
    public static function database($operation, $table, $recordId = null, $details = '', $context = []) {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Sistema';
        $message = "BD [{$operation}] na tabela '{$table}'";
        
        if ($recordId !== null) {
            $message .= " - Registro #{$recordId}";
        }
        
        if (!empty($details)) {
            $message .= " - {$details}";
        }
        
        $message .= " - por Usuário {$userId}";
        
        return self::log(self::DATABASE, self::INFO, $message, $context);
    }
    
    /**
     * Registra operações de email
     */
    public static function email($to, $subject, $status, $details = '', $context = []) {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Sistema';
        $message = "Email [{$status}] para '{$to}' - Assunto: '{$subject}'";
        
        if (!empty($details)) {
            $message .= " - {$details}";
        }
        
        $message .= " - por Usuário {$userId}";
        $level = ($status === 'falha') ? self::WARNING : self::INFO;
        
        return self::log(self::EMAIL, $level, $message, $context);
    }
    
    /**
     * Registra aviso no sistema
     */
    public static function warning($message, $context = []) {
        return self::log(self::ERROR, self::WARNING, $message, $context);
    }
    
    /**
     * Registra informação no sistema
     */
    public static function info($message, $context = []) {
        return self::log(self::ACTIVITY, self::INFO, $message, $context);
    }
    
    /**
     * Registra mensagem de debug no sistema
     */
    public static function debug($message, $context = []) {
        return self::log(self::ACTIVITY, self::DEBUG, $message, $context);
    }
    
    /**
     * Limpa logs antigos (mais de X dias)
     */
    public static function cleanup($days = 90) {
        $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));
        
        // Limpar do banco de dados
        $sql = "DELETE FROM system_logs WHERE created_at < ?";
        Database::query($sql, [$cutoffDate]);
        
        // Limpar logs de certificados
        $sql = "DELETE FROM logs_certificados WHERE data_envio < ?";
        Database::query($sql, [$cutoffDate]);
        
        // Limpar arquivos de log
        self::cleanupLogFiles($cutoffDate);
        
        return true;
    }
    
    /**
     * Limpa arquivos de log antigos
     */
    private static function cleanupLogFiles($cutoffDate) {
        // Implementação básica - poderia ser expandida para rotacionar logs
        // Por enquanto, apenas registra que a limpeza seria feita
        $message = "Cleanup de logs anteriores a {$cutoffDate} iniciado";
        self::info($message, ['cutoff_date' => $cutoffDate]);
    }
    
    /**
     * Obtém o IP do cliente
     */
    private static function getClientIP() {
        $ipAddress = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipAddress = 'Unknown';
        }
        
        return $ipAddress;
    }
}
?>