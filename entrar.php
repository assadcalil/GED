<?php
// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir o bootstrap
define('ROOT_DIR', __DIR__);
require_once ROOT_DIR . '/app/Config/App.php';
require_once ROOT_DIR . '/app/Config/Database.php';
require_once ROOT_DIR . '/app/Config/Auth.php';

// Redirecionar para o dashboard se estiver logado, ou para a página de login
if (Auth::isLoggedIn()) {
    header('Location: views/dashboard/index.php');
    exit;
} else {
    header('Location: login.php');
    exit;
}