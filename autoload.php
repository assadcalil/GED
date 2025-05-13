<?php
// Autoloader simples
spl_autoload_register(function($class) {
    // Converter namespace para caminho de arquivo
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    
    // Verificar se o arquivo existe
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    
    return false;
});