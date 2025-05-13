<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Seed de Permissões
 * 
 * Este arquivo contém as permissões básicas que serão
 * inseridas no banco de dados durante a instalação.
 */

// Array de permissões do sistema
$permissions = [
    // Permissões de Usuários
    ['permission_name' => 'users.view', 'description' => 'Visualizar usuários'],
    ['permission_name' => 'users.create', 'description' => 'Criar novos usuários'],
    ['permission_name' => 'users.edit', 'description' => 'Editar usuários existentes'],
    ['permission_name' => 'users.delete', 'description' => 'Desativar usuários'],
    
    // Permissões de Clientes
    ['permission_name' => 'clients.view', 'description' => 'Visualizar clientes'],
    ['permission_name' => 'clients.create', 'description' => 'Criar novos clientes'],
    ['permission_name' => 'clients.edit', 'description' => 'Editar clientes existentes'],
    ['permission_name' => 'clients.delete', 'description' => 'Desativar clientes'],
    
    // Permissões de Empresas
    ['permission_name' => 'companies.view', 'description' => 'Visualizar empresas'],
    ['permission_name' => 'companies.create', 'description' => 'Criar novas empresas'],
    ['permission_name' => 'companies.edit', 'description' => 'Editar empresas existentes'],
    ['permission_name' => 'companies.delete', 'description' => 'Desativar empresas'],
    
    // Permissões de Documentos
    ['permission_name' => 'documents.view', 'description' => 'Visualizar documentos'],
    ['permission_name' => 'documents.upload', 'description' => 'Fazer upload de documentos'],
    ['permission_name' => 'documents.download', 'description' => 'Baixar documentos'],
    ['permission_name' => 'documents.delete', 'description' => 'Excluir documentos'],
    ['permission_name' => 'documents.categories', 'description' => 'Gerenciar categorias de documentos'],
    
    // Permissões de Certificados
    ['permission_name' => 'certificates.view', 'description' => 'Visualizar certificados'],
    ['permission_name' => 'certificates.upload', 'description' => 'Fazer upload de certificados'],
    ['permission_name' => 'certificates.download', 'description' => 'Baixar certificados'],
    ['permission_name' => 'certificates.delete', 'description' => 'Excluir certificados'],
    
    // Permissões de Imposto de Renda
    ['permission_name' => 'tax.view', 'description' => 'Visualizar impostos de renda'],
    ['permission_name' => 'tax.create', 'description' => 'Criar declarações de imposto'],
    ['permission_name' => 'tax.edit', 'description' => 'Editar declarações de imposto'],
    ['permission_name' => 'tax.delete', 'description' => 'Excluir declarações de imposto'],
    ['permission_name' => 'tax.receipt', 'description' => 'Gerar recibos de imposto'],
    ['permission_name' => 'tax.payment', 'description' => 'Gerar boletos de pagamento'],
    
    // Permissões de Financeiro
    ['permission_name' => 'financial.view', 'description' => 'Visualizar dados financeiros'],
    ['permission_name' => 'financial.create', 'description' => 'Criar registros financeiros'],
    ['permission_name' => 'financial.edit', 'description' => 'Editar registros financeiros'],
    ['permission_name' => 'financial.delete', 'description' => 'Excluir registros financeiros'],
    
    // Permissões de Relatórios
    ['permission_name' => 'reports.documents', 'description' => 'Acessar relatórios de documentos'],
    ['permission_name' => 'reports.access', 'description' => 'Acessar relatórios de acesso'],
    ['permission_name' => 'reports.activities', 'description' => 'Acessar relatórios de atividades'],
    
    // Permissões de Sistema
    ['permission_name' => 'system.settings', 'description' => 'Acessar configurações do sistema'],
    ['permission_name' => 'system.logs', 'description' => 'Visualizar logs do sistema']
];

/**
 * Função para inserir permissões no banco de dados
 */
function insertPermissions($pdo, $permissions) {
    // Preparar a declaração SQL
    $stmt = $pdo->prepare("INSERT INTO permissions (permission_name, description, created_at) VALUES (?, ?, ?)");
    
    // Data atual
    $now = date('Y-m-d H:i:s');
    
    // Contador de permissões inseridas
    $count = 0;
    
    // Inserir cada permissão
    foreach ($permissions as $permission) {
        try {
            $stmt->execute([$permission['permission_name'], $permission['description'], $now]);
            $count++;
        } catch (PDOException $e) {
            // Ignorar erros de duplicidade
            if ($e->getCode() != 23000) { // 23000 é o código para "Duplicate entry"
                throw $e;
            }
        }
    }
    
    return $count;
}

/**
 * Atribuir todas as permissões ao usuário administrador
 */
function assignAdminPermissions($pdo, $adminId) {
    // Obter todas as permissões
    $permissions = $pdo->query("SELECT id FROM permissions")->fetchAll(PDO::FETCH_COLUMN);
    
    // Preparar a declaração SQL
    $stmt = $pdo->prepare("INSERT INTO user_permissions (user_id, permission_id, granted_by, granted_at) VALUES (?, ?, ?, ?)");
    
    // Data atual
    $now = date('Y-m-d H:i:s');
    
    // Contador de permissões atribuídas
    $count = 0;
    
    // Atribuir cada permissão ao administrador
    foreach ($permissions as $permissionId) {
        try {
            $stmt->execute([$adminId, $permissionId, $adminId, $now]);
            $count++;
        } catch (PDOException $e) {
            // Ignorar erros de duplicidade
            if ($e->getCode() != 23000) {
                throw $e;
            }
        }
    }
    
    return $count;
}

/**
 * Atribuir permissões padrão para cada tipo de usuário
 */
function assignDefaultPermissions($pdo, $userId, $userType) {
    // Definir permissões básicas por tipo de usuário
    $typePermissions = [];
    
    // Editor
    if ($userType == 2) {
        $typePermissions = [
            'users.view', 'users.create', 'users.edit',
            'clients.view', 'clients.create', 'clients.edit',
            'companies.view', 'companies.create', 'companies.edit',
            'documents.view', 'documents.upload', 'documents.download', 'documents.categories',
            'certificates.view', 'certificates.upload', 'certificates.download',
            'reports.documents', 'reports.activities'
        ];
    }
    
    // Imposto de Renda
    elseif ($userType == 3) {
        $typePermissions = [
            'clients.view',
            'companies.view',
            'documents.view', 'documents.upload', 'documents.download',
            'tax.view', 'tax.create', 'tax.edit', 'tax.receipt', 'tax.payment',
            'reports.documents'
        ];
    }
    
    // Funcionário
    elseif ($userType == 4) {
        $typePermissions = [
            'clients.view',
            'companies.view',
            'documents.view', 'documents.upload', 'documents.download',
            'certificates.view', 'certificates.upload'
        ];
    }
    
    // Financeiro
    elseif ($userType == 5) {
        $typePermissions = [
            'clients.view',
            'companies.view',
            'documents.view', 'documents.upload', 'documents.download',
            'financial.view', 'financial.create', 'financial.edit',
            'reports.documents'
        ];
    }
    
    // Se não houver permissões para o tipo, retornar
    if (empty($typePermissions)) {
        return 0;
    }
    
    // Data atual
    $now = date('Y-m-d H:i:s');
    
    // Obter IDs das permissões
    $placeholders = str_repeat('?,', count($typePermissions) - 1) . '?';
    $stmt = $pdo->prepare("SELECT id, permission_name FROM permissions WHERE permission_name IN ($placeholders)");
    $stmt->execute($typePermissions);
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Preparar a declaração SQL para inserção
    $insertStmt = $pdo->prepare("INSERT INTO user_permissions (user_id, permission_id, granted_by, granted_at) VALUES (?, ?, ?, ?)");
    
    // Contador de permissões atribuídas
    $count = 0;
    
    // Atribuir cada permissão ao usuário
    foreach ($permissions as $permission) {
        try {
            $insertStmt->execute([$userId, $permission['id'], 1, $now]); // 1 = ID do administrador
            $count++;
        } catch (PDOException $e) {
            // Ignorar erros de duplicidade
            if ($e->getCode() != 23000) {
                throw $e;
            }
        }
    }
    
    return $count;
}