<?php
// /GED2.0/views/empresas/views.php

// Definir cabeçalho JSON
header('Content-Type: application/json');

// Verificar se as configurações já foram incluídas
if (!defined('ROOT_DIR')) {
    // Ajuste o caminho conforme sua estrutura
    define('ROOT_DIR', __DIR__ . '/../..');
    require_once ROOT_DIR . '/../...../app/Config/App.php';
    require_once ROOT_DIR . '/../...../app/Config/Database.php';
    require_once ROOT_DIR . '/../...../app/Config/Auth.php';
    require_once ROOT_DIR . '/../...../app/Config/Logger.php';
}

// Verificar autenticação
if (!Auth::isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado.'
    ]);
    exit;
}

// Obter ID da empresa
$empresaId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($empresaId <= 0) {
    // Se não foi fornecido ID, retorna erro
    echo json_encode([
        'success' => false,
        'message' => 'ID da empresa não fornecido ou inválido.'
    ]);
    exit;
}

try {
    // Buscar dados da empresa
    $sql = "SELECT * FROM empresas WHERE id = ?";
    $empresa = Database::selectOne($sql, [$empresaId]);
    
    if (!$empresa) {
        echo json_encode([
            'success' => false,
            'message' => 'Empresa não encontrada no banco de dados.'
        ]);
        exit;
    }
    
    // Registrar visualização
    Logger::activity('visualização', "Visualizou detalhes da empresa ID: {$empresaId}");
    
    // Retornar dados em JSON
    echo json_encode([
        'success' => true,
        'data' => $empresa
    ]);
} catch (Exception $e) {
    Logger::error("Erro ao visualizar empresa: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar dados da empresa: ' . $e->getMessage()
    ]);
}
?>