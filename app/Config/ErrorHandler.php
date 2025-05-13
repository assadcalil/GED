<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Sistema de Tratamento de Erros
 * 
 * Este arquivo contém as funções necessárias para capturar,
 * exibir e registrar erros do sistema de forma consistente.
 */

// Definir constante para o diretório de logs
define('LOG_DIR', dirname(__DIR__) . '/logs');

// Garantir que o diretório de logs existe
if (!file_exists(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

/**
 * Função para manipular erros e exceções
 */
class ErrorHandler {
    // Níveis de erro em formato legível
    private static $errorTypes = [
        E_ERROR             => 'Erro Fatal',
        E_WARNING           => 'Aviso',
        E_PARSE             => 'Erro de Análise',
        E_NOTICE            => 'Notificação',
        E_CORE_ERROR        => 'Erro de Core',
        E_CORE_WARNING      => 'Aviso de Core',
        E_COMPILE_ERROR     => 'Erro de Compilação',
        E_COMPILE_WARNING   => 'Aviso de Compilação',
        E_USER_ERROR        => 'Erro de Usuário',
        E_USER_WARNING      => 'Aviso de Usuário',
        E_USER_NOTICE       => 'Notificação de Usuário',
        E_STRICT            => 'Notificação Estrita',
        E_RECOVERABLE_ERROR => 'Erro Recuperável',
        E_DEPRECATED        => 'Função Obsoleta',
        E_USER_DEPRECATED   => 'Função Obsoleta de Usuário',
        E_ALL               => 'Todos os Erros'
    ];

    /**
     * Inicializa o tratamento de erros
     */
    public static function initialize() {
        // Definir manipulador de erros
        set_error_handler([self::class, 'handleError']);
        
        // Definir manipulador de exceções
        set_exception_handler([self::class, 'handleException']);
        
        // Registrar função de encerramento
        register_shutdown_function([self::class, 'handleFatalError']);
        
        // Definir nível de relatório de erros (ajuste conforme necessário)
        error_reporting(E_ALL);
        
        // Desativar exibição de erros padrão do PHP
        ini_set('display_errors', 0);
        
        return true;
    }

    /**
     * Manipulador de erros
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        // Verificar se o erro deve ser reportado
        if (!(error_reporting() & $errno)) {
            return false;
        }

        // Obter tipo de erro em formato legível
        $errorType = isset(self::$errorTypes[$errno]) ? self::$errorTypes[$errno] : "Erro Desconhecido ($errno)";
        
        // Registrar erro no log
        self::logError($errorType, $errstr, $errfile, $errline);
        
        // Exibir erro para o usuário de forma amigável
        self::displayError($errorType, $errstr, $errfile, $errline);
        
        // Erros fatais devem terminar a execução
        if ($errno == E_ERROR || $errno == E_USER_ERROR) {
            exit(1);
        }
        
        // Prevenir o manipulador de erros padrão do PHP
        return true;
    }

    /**
     * Manipulador de exceções
     */
    public static function handleException($exception) {
        $errorType = get_class($exception);
        $errstr = $exception->getMessage();
        $errfile = $exception->getFile();
        $errline = $exception->getLine();
        $trace = $exception->getTraceAsString();
        
        // Registrar exceção no log
        self::logError($errorType, $errstr, $errfile, $errline, $trace);
        
        // Exibir erro para o usuário de forma amigável
        self::displayError($errorType, $errstr, $errfile, $errline, $trace);
        
        exit(1);
    }

    /**
     * Manipulador de erros fatais
     */
    public static function handleFatalError() {
        $error = error_get_last();
        
        if ($error !== null && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
            $errorType = isset(self::$errorTypes[$error['type']]) ? self::$errorTypes[$error['type']] : "Erro Fatal";
            $errstr = $error['message'];
            $errfile = $error['file'];
            $errline = $error['line'];
            
            // Registrar erro fatal no log
            self::logError($errorType, $errstr, $errfile, $errline);
            
            // Exibir erro para o usuário de forma amigável
            self::displayError($errorType, $errstr, $errfile, $errline);
        }
    }

    /**
     * Registra erro no arquivo de log
     */
    private static function logError($errorType, $errstr, $errfile, $errline, $trace = null) {
        $date = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? 'Unknown';
        $user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Não autenticado';
        
        $logMessage = "[$date] [$ip] [$user] [$uri] [$errorType] $errstr em $errfile na linha $errline" . PHP_EOL;
        
        if ($trace) {
            $logMessage .= "Stack Trace:\n$trace" . PHP_EOL;
        }
        
        $logFile = LOG_DIR . '/errors.log';
        error_log($logMessage, 3, $logFile);
        
        return true;
    }

    /**
     * Exibe erro para o usuário de forma amigável
     */
    private static function displayError($errorType, $errstr, $errfile, $errline, $trace = null) {
        // Verificar se é uma requisição AJAX
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                   strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
        
        // Para requisições AJAX, retornar JSON
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'type' => $errorType,
                'message' => $errstr,
                'file' => $errfile,
                'line' => $errline,
                'trace' => $trace
            ]);
            exit;
        }
        
        // Para requisições normais, exibir HTML formatado
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Erro do Sistema - Contabilidade Estrela</title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background-color: #f8f9fa;
                    color: #333;
                    margin: 0;
                    padding: 20px;
                }
                .error-container {
                    max-width: 900px;
                    margin: 50px auto;
                    background-color: #fff;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    overflow: hidden;
                }
                .error-header {
                    background-color: #dc3545;
                    color: white;
                    padding: 15px 20px;
                    font-size: 1.2em;
                    font-weight: 600;
                }
                .error-body {
                    padding: 20px;
                }
                .error-title {
                    font-size: 1.5em;
                    color: #dc3545;
                    margin-bottom: 15px;
                }
                .error-details {
                    background-color: #f8f9fa;
                    border-radius: 5px;
                    padding: 15px;
                    margin-top: 15px;
                    border-left: 4px solid #dc3545;
                }
                .error-stack {
                    background-color: #f8f9fa;
                    border-radius: 5px;
                    padding: 15px;
                    margin-top: 15px;
                    border-left: 4px solid #6c757d;
                    overflow-x: auto;
                    font-family: monospace;
                    white-space: pre-wrap;
                }
                .error-footer {
                    background-color: #f8f9fa;
                    padding: 15px 20px;
                    text-align: center;
                    border-top: 1px solid #e9ecef;
                    font-size: 0.9em;
                }
                .highlight {
                    font-weight: bold;
                }
                .file-info {
                    font-family: monospace;
                    margin-top: 10px;
                }
                .button {
                    display: inline-block;
                    padding: 10px 15px;
                    background-color: #007bff;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px; 
                    margin-top: 20px;
                }
                .button:hover {
                    background-color: #0069d9;
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-header">
                    <center>Sistema Contabilidade Estrela - Erro Detectado</center>
                </div>
                <div class="error-body">
                    <div class="error-title"><?php echo $errorType; ?></div>
                    <p><?php echo htmlspecialchars($errstr); ?></p>
                    <div class="error-details">
                        <div class="highlight">Arquivo:</div>
                        <div class="file-info"><?php echo htmlspecialchars($errfile); ?></div>
                        <div class="highlight">Linha:</div>
                        <div class="file-info"><?php echo $errline; ?></div>
                    </div>
                    <?php if ($trace): ?>
                    <div class="error-stack">
                        <div class="highlight">Stack Trace:</div>
                        <?php echo nl2br(htmlspecialchars($trace)); ?>
                    </div>
                    <?php endif; ?>
                    <a href="/" class="button">Voltar ao Início</a>
                </div>
                <div class="error-footer">
                    Este erro foi registrado e será analisado pela equipe de desenvolvimento.
                </div>
            </div>
            <div class="system-name"><center>
                <h3><?php echo SITE_NAME; ?></h3>
                <div class="version">Versão <?php echo SITE_VERSION; ?></div>
                <div class="version"> 2017 - <?php echo date('Y'); ?></div></center>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Registra mensagem personalizada no log de erros
     */
    public static function logCustomError($message, $context = []) {
        $date = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? 'Unknown';
        $user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Não autenticado';
        
        $logMessage = "[$date] [$ip] [$user] [$uri] [Erro Personalizado] $message" . PHP_EOL;
        
        if (!empty($context)) {
            $logMessage .= "Contexto: " . json_encode($context, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }
        
        $logFile = LOG_DIR . '/custom_errors.log';
        error_log($logMessage, 3, $logFile);
        
        return true;
    }
}

// Inicializar o tratamento de erros
ErrorHandler::initialize();