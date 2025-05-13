<?php
// /GED2.0/api/empresas.php

// Desabilitar exibição de erros na tela (exibir apenas no log)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Verificar se as configurações já foram incluídas
if (!defined('ROOT_DIR')) {
    require_once __DIR__ . '/.././../app/Config/App.php';
    require_once __DIR__ . '/.././../app/Config/Database.php';
    require_once __DIR__ . '/.././../app/Config/Auth.php';
    require_once __DIR__ . '/.././../app/Config/Logger.php';
}

// Verificar autenticação
Auth::requireLogin();

// Definir cabeçalho JSON
header('Content-Type: application/json');

try {
    // Verificar ação
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if ($action === 'view' && isset($_GET['id'])) {
        $empresaId = intval($_GET['id']);
        
        // Primeiro, tentar buscar pelo ID
        $empresa = Database::selectOne("
            SELECT id, emp_code, emp_name, emp_cnpj, emp_sit_cad, name, 
                   emp_porte, emp_tipo_jur, emp_cid, emp_uf, data 
            FROM empresas 
            WHERE id = ?
        ", [$empresaId]);
        
        // Se não encontrar pelo ID, tentar pelo emp_code
        if (!$empresa && isset($_GET['emp_code'])) {
            $emp_code = $_GET['emp_code'];
            $empresa = Database::selectOne("
                SELECT id, emp_code, emp_name, emp_cnpj, emp_sit_cad, name, 
                       emp_porte, emp_tipo_jur, emp_cid, emp_uf, data 
                FROM empresas 
                WHERE emp_code = ?
            ", [$emp_code]);
        }
        
        if ($empresa) {
            // Registrar acesso
            Logger::activity('visualização', "Visualizou detalhes da empresa ID: {$empresaId}");
            
            // Retornar dados
            echo json_encode([
                'success' => true,
                'data' => $empresa
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Empresa não encontrada.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Ação inválida ou ID não fornecido.'
        ]);
    }
} catch (Exception $e) {
    // Registrar erro
    Logger::error("Erro na API de empresas: " . $e->getMessage());
    
    // Retornar erro
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno ao processar requisição.',
        'error' => $e->getMessage() // Apenas para debug - remover em produção
    ]);
}