<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Configurações Gerais
 * 
 * Este arquivo contém as configurações gerais do sistema,
 * constantes e funções utilitárias.
 */

// Definir constantes do sistema
define('SITE_NAME', 'Sistema Contabilidade Estrela');
define('SITE_VERSION', '2.0');
define('SITE_URL', 'http://localhost'); // Altere para seu domínio em produção
define('ROOT_PATH', dirname(dirname(__DIR__))); // Aponta para a raiz do projeto (GED2.0/)
define('BASE_PATH', ROOT_PATH); // Adicionado para compatibilidade
define('UPLOAD_DIR', ROOT_PATH . '/uploads');
define('ALLOWED_FILE_TYPES', 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,txt,zip');
define('MAX_UPLOAD_SIZE', 20 * 1024 * 1024); // 20MB em bytes
define('DEFAULT_TIMEZONE', 'America/Sao_Paulo');
define('ADMIN_EMAIL', 'admin@contabilidadeestrela.com.br');

// Definir fuso horário
date_default_timezone_set(DEFAULT_TIMEZONE);

// Configurações de template
define('VIEW_PATH', ROOT_PATH . '/views');
define('PARTIAL_PATH', VIEW_PATH . '/partials');

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Mude para 1 em produção com HTTPS

// Configurações de Erro
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sessão se ainda não estiver ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir arquivos de configuração adicionais
require_once ROOT_PATH . '/app/Config/Database.php';
require_once ROOT_PATH . '/app/Config/Auth.php';

// Autoload classes
spl_autoload_register(function ($class_name) {
    // Array de diretórios para procurar as classes (novos diretórios)
    $dirs = [
        ROOT_PATH . '/app/Models/',
        ROOT_PATH . '/app/Controllers/',
        ROOT_PATH . '/app/Dao/',
        ROOT_PATH . '/app/Services/',
        ROOT_PATH . '/app/Utils/'
    ];
    
    // Diretórios antigos (para compatibilidade durante a transição)
    $oldDirs = [
        ROOT_PATH . '/models/',
        ROOT_PATH . '/controllers/',
        ROOT_PATH . '/includes/'
    ];
    
    // Combinar os diretórios
    $allDirs = array_merge($dirs, $oldDirs);
    
    foreach ($allDirs as $dir) {
        $file = $dir . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // Tratamento especial para namespaces
    // Se o nome da classe contém "\" (é um namespace)
    if (strpos($class_name, '\\') !== false) {
        // Converter namespace para caminho de arquivo (App\Models\User -> app/Models/User.php)
        $file = ROOT_PATH . '/' . str_replace('\\', '/', $class_name) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Funções utilitárias
class Config {
    /**
     * Carregar um arquivo de visualização
     */
    public static function view($name, $data = []) {
        // Extrair dados para que possam ser usados na view como variáveis
        extract($data);
        
        // Caminho completo para o arquivo de visualização
        $filePath = VIEW_PATH . '/' . $name . '.php';
        
        // Verificar se o arquivo existe
        if (!file_exists($filePath)) {
            throw new Exception("View não encontrada: {$name}.php");
        }
        
        // Iniciar buffer de saída
        ob_start();
        
        // Incluir o arquivo
        include $filePath;
        
        // Obter conteúdo do buffer e limpá-lo
        return ob_get_clean();
    }
    
    /**
     * Exibir um arquivo de visualização (atalho para echo view())
     */
    public static function render($name, $data = []) {
        echo self::view($name, $data);
    }
    
    /**
     * Carregar um arquivo parcial de visualização
     */
    public static function partial($name, $data = []) {
        // Caminho completo para o arquivo parcial
        $filePath = PARTIAL_PATH . '/' . $name . '.php';
        
        // Extrair dados para que possam ser usados no parcial como variáveis
        extract($data);
        
        // Verificar se o arquivo existe
        if (!file_exists($filePath)) {
            throw new Exception("Partial não encontrado: {$name}.php");
        }
        
        // Incluir o arquivo
        include $filePath;
    }
    
    /**
     * Redirecionar para outra URL
     */
    public static function redirect($url, $status = 302) {
        header("Location: {$url}", true, $status);
        exit;
    }
    
    /**
     * Obter URL absoluta
     */
    public static function url($path = '') {
        return SITE_URL . '/' . ltrim($path, '/');
    }
    
    /**
     * Formatar valor monetário
     */
    public static function formatMoney($value) {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
    
    /**
     * Formatar data no padrão brasileiro
     */
    public static function formatDate($date, $format = 'd/m/Y') {
        if (!$date) return '';
        $datetime = new DateTime($date);
        return $datetime->format($format);
    }
    
    /**
     * Limpar string para segurança
     */
    public static function sanitize($string) {
        return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Verificar se é uma requisição AJAX
     */
    public static function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
    
    /**
     * Obter o método da requisição HTTP atual
     */
    public static function getRequestMethod() {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }
    
    /**
     * Verificar se é uma requisição POST
     */
    public static function isPost() {
        return self::getRequestMethod() === 'POST';
    }
    
    /**
     * Verificar se é uma requisição GET
     */
    public static function isGet() {
        return self::getRequestMethod() === 'GET';
    }
    
    /**
     * Obter o horário atual de Brasília formatado
     */
    public static function getCurrentBrasiliaTIme($format = 'd/m/Y H:i:s') {
        $datetime = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
        return $datetime->format($format);
    }
    
    /**
     * Obter apenas a hora atual de Brasília
     */
    public static function getCurrentBrasiliaHour($format = 'H:i:s') {
        return self::getCurrentBrasiliaTIme($format);
    }
    
    /**
     * Verifica se uma string está vazia
     */
    public static function isEmpty($value) {
        return empty(trim($value));
    }
    
    /**
     * Verifica se um arquivo existe e é legível
     */
    public static function fileExists($path) {
        return file_exists($path) && is_readable($path);
    }
    
    /**
     * Gera um identificador único para o sistema
     */
    public static function generateUniqueId($prefix = '') {
        return uniqid($prefix) . bin2hex(random_bytes(8));
    }
    
    /**
     * Verifica se a extensão de um arquivo é permitida
     */
    public static function isAllowedFileType($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowedTypes = explode(',', ALLOWED_FILE_TYPES);
        
        return in_array($extension, $allowedTypes);
    }
    
    /**
     * Verifica se o tamanho de um arquivo está dentro do limite
     */
    public static function isAllowedFileSize($filesize) {
        return $filesize <= MAX_UPLOAD_SIZE;
    }
    
    /**
     * Obtém a extensão de um arquivo
     */
    public static function getFileExtension($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
    
    /**
     * Obtém o tipo MIME de um arquivo
     */
    public static function getFileMimeType($filePath) {
        if (function_exists('mime_content_type')) {
            return mime_content_type($filePath);
        }
        
        // Fallback se mime_content_type não estiver disponível
        $extension = self::getFileExtension($filePath);
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'txt' => 'text/plain',
            'zip' => 'application/zip'
        ];
        
        return isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'application/octet-stream';
    }
}