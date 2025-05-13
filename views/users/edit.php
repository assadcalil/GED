<?php

// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Sistema Contabilidade Estrela 2.0
 * Edição de Usuário
 */

// Verificar se as configurações já foram incluídas
if (!defined('ROOT_DIR')) {
    require_once __DIR__ . '/../../../...../app/Config/App.php';
    require_once __DIR__ . '/../../../...../app/Config/Database.php';
    require_once __DIR__ . '/../../../...../app/Config/Auth.php';
    require_once __DIR__ . '/../../../...../app/Config/Logger.php';
}

// Verificar autenticação e permissão
Auth::requireLogin();

// Apenas administradores e editores podem editar usuários
if (!Auth::isAdmin() && !Auth::isUserType(Auth::EDITOR)) {
    header('Location: /Ged2.0/views/errors/access-denied.php');
    exit;
}

// Obter ID do usuário a ser editado
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($userId <= 0) {
    // ID inválido, redirecionar para listagem
    header('Location: /users/list.php?error=' . urlencode('ID de usuário inválido'));
    exit;
}

// Verificar se editor está tentando editar um administrador
if (Auth::isUserType(Auth::EDITOR)) {
    $userToEdit = Database::selectOne("SELECT type FROM users WHERE id = ?", [$userId]);
    
    if ($userToEdit && $userToEdit['type'] == Auth::ADMIN) {
        header('Location: /Ged2.0/views/errors/access-denied.php');
        exit;
    }
}

// Registrar acesso
Logger::activity('acesso', "Acessou a edição do usuário ID: {$userId}");

// Obter dados do usuário
$user = Database::selectOne("SELECT * FROM users WHERE id = ?", [$userId]);

if (!$user) {
    // Usuário não encontrado, redirecionar para listagem
    header('Location: /users/list.php?error=' . urlencode('Usuário não encontrado'));
    exit;
}

// Obter empresas vinculadas (se for cliente)
$linkedCompanies = [];
if ($user['type'] == Auth::CLIENT) {
    $linkedCompanies = Database::select("
        SELECT c.* 
        FROM companies c
        WHERE c.client_id = ?
        ORDER BY c.is_main DESC, c.name ASC
    ", [$userId]);
}

// Obter permissões atuais do usuário
$userPermissions = [];
$permissionsResult = Database::select("
    SELECT p.id, p.permission_name, p.description, CASE WHEN up.id IS NOT NULL THEN 1 ELSE 0 END as has_permission
    FROM permissions p
    LEFT JOIN user_permissions up ON p.id = up.permission_id AND up.user_id = ?
    ORDER BY p.permission_name
", [$userId]);

foreach ($permissionsResult as $permission) {
    $userPermissions[$permission['id']] = [
        'name' => $permission['permission_name'],
        'description' => $permission['description'],
        'has_permission' => $permission['has_permission']
    ];
}

// Agrupar permissões por módulo para exibição
$permissionGroups = [
    'users' => ['name' => 'Usuários', 'permissions' => []],
    'clients' => ['name' => 'Clientes', 'permissions' => []],
    'companies' => ['name' => 'Empresas', 'permissions' => []],
    'documents' => ['name' => 'Documentos', 'permissions' => []],
    'certificates' => ['name' => 'Certificados', 'permissions' => []],
    'tax' => ['name' => 'Imposto de Renda', 'permissions' => []],
    'financial' => ['name' => 'Financeiro', 'permissions' => []],
    'reports' => ['name' => 'Relatórios', 'permissions' => []],
    'system' => ['name' => 'Sistema', 'permissions' => []]
];

foreach ($userPermissions as $id => $permission) {
    $parts = explode('.', $permission['name']);
    $module = $parts[0];
    
    if (isset($permissionGroups[$module])) {
        $permissionGroups[$module]['permissions'][$id] = $permission;
    } else {
        $permissionGroups['system']['permissions'][$id] = $permission;
    }
}

// Variáveis para armazenar valores e mensagens
$userData = [
    'name' => $user['name'],
    'email' => $user['email'],
    'username' => $user['username'],
    'type' => $user['type'],
    'phone' => $user['phone'],
    'cpf' => $user['cpf'],
    'active' => $user['active']
];

$success = '';
$error = '';
$errors = [];

// Processar formulário se for uma requisição POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $userData = [
        'name' => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'username' => $_POST['username'] ?? '',
        'type' => isset($_POST['type']) ? intval($_POST['type']) : $user['type'],
        'phone' => $_POST['phone'] ?? '',
        'cpf' => $_POST['cpf'] ?? '',
        'active' => isset($_POST['active']) ? 1 : 0
    ];
    
    // Validar campos obrigatórios
    if (empty($userData['name'])) {
        $errors['name'] = 'O nome é obrigatório';
    }
    
    if (empty($userData['email'])) {
        $errors['email'] = 'O e-mail é obrigatório';
    } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'O e-mail informado é inválido';
    } else {
        // Verificar se e-mail já existe para outro usuário
        $existingUser = Database::selectOne("SELECT id FROM users WHERE email = ? AND id != ?", [$userData['email'], $userId]);
        if ($existingUser) {
            $errors['email'] = 'Este e-mail já está sendo usado por outro usuário';
        }
    }
    
    if (empty($userData['username'])) {
        $errors['username'] = 'O nome de usuário é obrigatório';
    } elseif (strlen($userData['username']) < 4) {
        $errors['username'] = 'O nome de usuário deve ter pelo menos 4 caracteres';
    } else {
        // Verificar se username já existe para outro usuário
        $existingUser = Database::selectOne("SELECT id FROM users WHERE username = ? AND id != ?", [$userData['username'], $userId]);
        if ($existingUser) {
            $errors['username'] = 'Este nome de usuário já está sendo usado';
        }
    }
    
    // Validar tipo de usuário
    if (!array_key_exists($userData['type'], Auth::$userTypes)) {
        $errors['type'] = 'Tipo de usuário inválido';
    }
    
    // Verificar se editor está tentando alterar um usuário para administrador
    if (!Auth::isAdmin() && $userData['type'] == Auth::ADMIN) {
        $errors['type'] = 'Você não tem permissão para criar ou editar administradores';
    }
    
    // Validar senha se informada
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (!empty($password)) {
        if (strlen($password) < 8) {
            $errors['password'] = 'A senha deve ter pelo menos 8 caracteres';
        } elseif ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'As senhas não conferem';
        } else {
            // Verificar força da senha
            list($isStrong, $message) = Auth::checkPasswordStrength($password);
            if (!$isStrong) {
                $errors['password'] = $message;
            }
        }
    }
    
    // Se não houver erros, prosseguir com a atualização
    if (empty($errors)) {
        try {
            // Iniciar transação
            Database::beginTransaction();
            
            // Preparar dados para atualização
            $updateData = [
                'name' => $userData['name'],
                'email' => $userData['email'],
                'username' => $userData['username'],
                'type' => $userData['type'],
                'phone' => $userData['phone'],
                'cpf' => $userData['cpf'],
                'active' => $userData['active'],
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $_SESSION['user_id']
            ];
            
            // Adicionar senha se informada
            if (!empty($password)) {
                $updateData['password'] = Auth::hashPassword($password);
            }
            
            // Atualizar usuário
            $result = Database::update('users', $updateData, 'id = ?', [$userId]);
            
            if ($result) {
                // Processar imagem de perfil se enviada
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $image = $_FILES['profile_image'];
                    
                    // Validar tipo de arquivo
                    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                    if (in_array($image['type'], $allowedTypes)) {
                        // Criar diretório de upload se não existir
                        $uploadDir = ROOT_PATH . '/uploads/profile/';
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        // Gerar nome de arquivo único
                        $fileName = 'profile_' . $userId . '_' . time() . '.' . pathinfo($image['name'], PATHINFO_EXTENSION);
                        $targetFile = $uploadDir . $fileName;
                        
                        // Mover arquivo para diretório de upload
                        if (move_uploaded_file($image['tmp_name'], $targetFile)) {
                            // Remover imagem antiga se existir
                            if (!empty($user['profile_image'])) {
                                $oldFile = $uploadDir . $user['profile_image'];
                                if (file_exists($oldFile)) {
                                    unlink($oldFile);
                                }
                            }
                            
                            // Atualizar usuário com nova imagem de perfil
                            Database::update('users', ['profile_image' => $fileName], 'id = ?', [$userId]);
                            
                            // Registrar upload
                            Logger::upload($fileName, 'sucesso', 'Imagem de perfil atualizada para usuário ID: ' . $userId);
                        } else {
                            // Registrar falha no upload
                            Logger::upload($image['name'], 'falha', 'Não foi possível mover o arquivo');
                        }
                    } else {
                        // Registrar tipo de arquivo inválido
                        Logger::upload($image['name'], 'falha', 'Tipo de arquivo inválido: ' . $image['type']);
                    }
                }
                
                // Atualizar permissões (apenas para administradores)
                if (Auth::isAdmin() && isset($_POST['permissions']) && is_array($_POST['permissions'])) {
                    // Remover permissões atuais
                    Database::delete('user_permissions', 'user_id = ?', [$userId]);
                    
                    // Inserir novas permissões
                    foreach ($_POST['permissions'] as $permissionId) {
                        $permissionData = [
                            'user_id' => $userId,
                            'permission_id' => $permissionId,
                            'granted_by' => $_SESSION['user_id'],
                            'granted_at' => date('Y-m-d H:i:s')
                        ];
                        
                        Database::insert('user_permissions', $permissionData);
                    }
                    
                    // Registrar atualização de permissões
                    Logger::activity('permissões', "Permissões atualizadas para usuário ID: {$userId}");
                }
                
                // Commit da transação
                Database::commit();
                
                // Registrar atividade
                Logger::activity('usuário', "Usuário ID: {$userId} ({$userData['name']}) foi atualizado");
                
                // Definir mensagem de sucesso
                $success = "Usuário atualizado com sucesso!";
                
                // Recarregar dados do usuário para exibição
                $user = Database::selectOne("SELECT * FROM users WHERE id = ?", [$userId]);
            } else {
                throw new Exception("Erro ao atualizar usuário no banco de dados");
            }
        } catch (Exception $e) {
            // Rollback em caso de erro
            Database::rollback();
            
            // Registrar erro
            Logger::error("Erro ao atualizar usuário: " . $e->getMessage());
            
            // Definir mensagem de erro
            $error = "Erro ao atualizar usuário: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - <?php echo SITE_NAME; ?></title>
    
    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Estilo personalizado -->
    <link rel="stylesheet" href="/GED2.0/assets/css/dashboard.css">
    
    <style>
        .permissions-group {
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .permissions-header {
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
        }
        
        .permissions-body {
            padding: 15px;
        }
        
        .permission-item {
            display: flex;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .permission-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .permission-checkbox {
            margin-right: 10px;
        }
        
        .permission-info {
            flex: 1;
        }
        
        .permission-name {
            font-weight: 500;
        }
        
        .permission-description {
            font-size: 14px;
            color: #6c757d;
        }
        
        .company-card {
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .company-card.main {
            border-color: #28a745;
            border-width: 2px;
        }
        
        .company-main-badge {
            position: absolute;
            top: -10px;
            right: 10px;
            background-color: #28a745;
            color: white;
            font-size: 12px;
            padding: 2px 10px;
            border-radius: 10px;
        }
        
        .profile-image-container {
            width: 150px;
            height: 150px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            border-radius: 50%;
            border: 3px solid #f8f9fa;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .edit-image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0, 0, 0, 0.5);
            padding: 5px;
            color: white;
            font-size: 12px;
            text-align: center;
            cursor: pointer;
        }
    </style>
</head>
<body data-user-type="<?php echo $_SESSION['user_type']; ?>">
    <div class="dashboard-container">
        <!-- Menu Lateral -->
        <?php include_once ROOT_PATH . '/views/partials/sidebar.php'; ?>
        
        <!-- Conteúdo Principal -->
        <div class="main-content">
            <!-- Cabeçalho -->
            <header class="dashboard-header">
                <div class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </div>
                
                <div class="brasilia-time">
                    <i class="far fa-clock"></i> Horário de Brasília: <span id="brasilia-clock"><?php echo Config::getCurrentBrasiliaHour(); ?></span>
                </div>
                
                <div class="header-right">
                    <div class="notifications dropdown">
                        <button class="btn dropdown-toggle" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="far fa-bell"></i>
                            <span class="notification-badge">3</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                            <li><h6 class="dropdown-header">Notificações</h6></li>
                            <li><a class="dropdown-item" href="#">Novo documento adicionado</a></li>
                            <li><a class="dropdown-item" href="#">Certificado expirando em 10 dias</a></li>
                            <li><a class="dropdown-item" href="#">Solicitação de acesso pendente</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="#">Ver todas</a></li>
                        </ul>
                    </div>
                    
                    <div class="user-profile dropdown">
                        <button class="btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar">
                                <img src="/GED2.0/assets/img/avatar.png" alt="Avatar do Usuário">
                            </div>
                            <div class="user-info">
                                <span class="user-name"><?php echo $_SESSION['user_name']; ?></span>
                                <span class="user-role"><?php echo Auth::getUserTypeName(); ?></span>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="/profile"><i class="fas fa-user-circle me-2"></i> Meu Perfil</a></li>
                            <li><a class="dropdown-item" href="/settings"><i class="fas fa-cog me-2"></i> Configurações</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/?logout=1"><i class="fas fa-sign-out-alt me-2"></i> Sair</a></li>
                        </ul>
                    </div>
                </div>
            </header>
            
            <!-- Conteúdo da Página -->
            <div class="dashboard-content">
                <div class="container-fluid">
                    <!-- Cabeçalho da Página -->
                    <div class="page-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="page-title">Editar Usuário</h1>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="/users/list.php">Usuários</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Editar Usuário</li>
                                    </ol>
                                </nav>
                            </div>
                            <div class="col-auto">
                                <a href="/GED2.0/Views/users/list.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Voltar para Lista
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Formulário de Edição -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informações do Usuário</h5>
                        </div>
                        <div class="card-body">
                            <form action="" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <div class="row">
                                    <!-- Coluna da Esquerda -->
                                    <div class="col-md-8">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="name" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" id="name" name="name" value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                                                <?php if (isset($errors['name'])): ?>
                                                    <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                                                <?php if (isset($errors['email'])): ?>
                                                    <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="username" class="form-label">Nome de Usuário <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                                                <?php if (isset($errors['username'])): ?>
                                                    <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
                                                <?php else: ?>
                                                    <div class="form-text">Mínimo de 4 caracteres, sem espaços.</div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="type" class="form-label">Tipo de Usuário <span class="text-danger">*</span></label>
                                                <select class="form-select <?php echo isset($errors['type']) ? 'is-invalid' : ''; ?>" id="type" name="type" required>
                                                    <?php foreach (Auth::$userTypes as $typeId => $typeName): ?>
                                                        <?php if (Auth::isAdmin() || $typeId != Auth::ADMIN): // Apenas admin pode criar outros admins ?>
                                                            <option value="<?php echo $typeId; ?>" <?php echo $userData['type'] == $typeId ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($typeName); ?>
                                                            </option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                                <?php if (isset($errors['type'])): ?>
                                                    <div class="invalid-feedback"><?php echo $errors['type']; ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="password" class="form-label">Nova Senha</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password">
                                                    <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1">
                                                        <i class="far fa-eye"></i>
                                                    </button>
                                                    <?php if (isset($errors['password'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="form-text">Deixe em branco para manter a senha atual.</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password">
                                                    <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1">
                                                        <i class="far fa-eye"></i>
                                                    </button>
                                                    <?php if (isset($errors['confirm_password'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['confirm_password']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="phone" class="form-label">Telefone</label>
                                                <input type="text" class="form-control mask-phone" id="phone" name="phone" value="<?php echo htmlspecialchars($userData['phone']); ?>">
                                                <div class="form-text">Formato: (99) 99999-9999</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="cpf" class="form-label">CPF</label>
                                                <input type="text" class="form-control mask-cpf" id="cpf" name="cpf" value="<?php echo htmlspecialchars($userData['cpf']); ?>">
                                                <div class="form-text">Formato: 999.999.999-99</div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="active" name="active" <?php echo $userData['active'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="active">Usuário Ativo</label>
                                        </div>
                                    </div>
                                    
                                    <!-- Coluna da Direita (Foto de Perfil) -->
                                    <div class="col-md-4">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="card-title mb-0">Foto de Perfil</h6>
                                            </div>
                                            <div class="card-body text-center">
                                                <div class="profile-image-container mb-3">
                                                    <?php if (!empty($user['profile_image'])): ?>
                                                        <img src="/uploads/profile/<?php echo $user['profile_image']; ?>" alt="Foto de Perfil" id="profile-preview" class="img-fluid profile-image">
                                                    <?php else: ?>
                                                        <div class="profile-image d-flex align-items-center justify-content-center bg-primary text-white">
                                                            <span style="font-size: 60px;"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="edit-image-overlay" id="image-overlay">
                                                        <i class="fas fa-camera me-1"></i> Alterar imagem
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="profile_image" class="form-label">Selecione uma imagem</label>
                                                    <input class="form-control" type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png" style="display: none;">
                                                    <div class="form-text">JPG ou PNG, máximo 2MB.</div>
                                                </div>
                                                <p class="text-muted mb-0">
                                                    <small>Último acesso: 
                                                        <?php echo !empty($user['last_login']) ? Config::formatDate($user['last_login'], 'd/m/Y H:i') : 'Nunca acessou'; ?>
                                                    </small>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Permissões (apenas para administradores) -->
                                <?php if (Auth::isAdmin() && $user['type'] != Auth::ADMIN && $user['type'] != Auth::CLIENT): ?>
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="card-title mb-0">Permissões de Acesso</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i> Defina quais funcionalidades este usuário pode acessar. 
                                                    Administradores têm acesso completo ao sistema.
                                                </div>
                                                
                                                <div class="permissions-container">
                                                    <?php foreach ($permissionGroups as $groupKey => $group): ?>
                                                        <?php if (!empty($group['permissions'])): ?>
                                                            <div class="permissions-group">
                                                                <div class="permissions-header" data-bs-toggle="collapse" data-bs-target="#permissions-<?php echo $groupKey; ?>">
                                                                    <i class="fas fa-caret-down me-2"></i> <?php echo $group['name']; ?>
                                                                </div>
                                                                <div class="permissions-body collapse show" id="permissions-<?php echo $groupKey; ?>">
                                                                    <?php foreach ($group['permissions'] as $permissionId => $permission): ?>
                                                                        <div class="permission-item">
                                                                            <div class="permission-checkbox">
                                                                                <div class="form-check">
                                                                                    <input class="form-check-input" type="checkbox" name="permissions[]" 
                                                                                        value="<?php echo $permissionId; ?>" 
                                                                                        id="permission-<?php echo $permissionId; ?>" 
                                                                                        <?php echo $permission['has_permission'] ? 'checked' : ''; ?>>
                                                                                </div>
                                                                            </div>
                                                                            <div class="permission-info">
                                                                                <label class="permission-name form-check-label" for="permission-<?php echo $permissionId; ?>">
                                                                                    <?php echo $permission['name']; ?>
                                                                                </label>
                                                                                <?php if (!empty($permission['description'])): ?>
                                                                                    <div class="permission-description">
                                                                                        <?php echo $permission['description']; ?>
                                                                                    </div>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Empresas Vinculadas (apenas para clientes) -->
                                <?php if ($user['type'] == Auth::CLIENT): ?>
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h5 class="card-title mb-0">Empresas Vinculadas</h5>
                                                <a href="/companies/create.php?client_id=<?php echo $userId; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-plus-circle me-1"></i> Nova Empresa
                                                </a>
                                            </div>
                                            <div class="card-body">
                                                <?php if (empty($linkedCompanies)): ?>
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle me-2"></i> Este cliente não possui empresas vinculadas.
                                                    </div>
                                                <?php else: ?>
                                                    <div class="row">
                                                        <?php foreach ($linkedCompanies as $company): ?>
                                                            <div class="col-md-6 col-lg-4">
                                                                <div class="company-card <?php echo $company['is_main'] ? 'main' : ''; ?>">
                                                                    <?php if ($company['is_main']): ?>
                                                                        <div class="company-main-badge">Matriz</div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <h5><?php echo htmlspecialchars($company['name']); ?></h5>
                                                                    <p class="mb-1"><strong>Código:</strong> <?php echo htmlspecialchars($company['company_code']); ?></p>
                                                                    <p class="mb-1"><strong>CNPJ:</strong> <?php echo htmlspecialchars($company['cnpj']); ?></p>
                                                                    <p class="text-muted mb-2"><?php echo $company['active'] ? '<span class="text-success">Ativa</span>' : '<span class="text-danger">Inativa</span>'; ?></p>
                                                                    
                                                                    <div class="btn-group btn-group-sm">
                                                                        <a href="/companies/edit.php?id=<?php echo $company['id']; ?>" class="btn btn-outline-primary">
                                                                            <i class="fas fa-edit me-1"></i> Editar
                                                                        </a>
                                                                        <a href="/companies/view.php?id=<?php echo $company['id']; ?>" class="btn btn-outline-info">
                                                                            <i class="fas fa-eye me-1"></i> Detalhes
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <hr>
                                
                                <div class="row">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i> Salvar Alterações
                                        </button>
                                        <a href="/ged2.0/views/users/list.php" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i> Cancelar
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Rodapé -->
            <footer class="dashboard-footer">
                <div class="container-fluid">
                    <div class="copyright">
                        GED Contabilidade Estrela &copy; <?php echo date('Y'); ?>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap Bundle com Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script personalizado -->
    <script src="/GED2.0/assets/js/dashboard.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Visualização da senha
            const toggleButtons = document.querySelectorAll('.toggle-password');
            
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const icon = this.querySelector('i');
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });
            
            // Pré-visualização da imagem de perfil
            const profileInput = document.getElementById('profile_image');
            const profilePreview = document.getElementById('profile-preview');
            const imageOverlay = document.getElementById('image-overlay');
            
            if (profileInput && imageOverlay) {
                imageOverlay.addEventListener('click', function() {
                    profileInput.click();
                });
                
                profileInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            if (profilePreview) {
                                profilePreview.src = e.target.result;
                            } else {
                                // Se não existir a imagem, criar uma
                                const container = document.querySelector('.profile-image-container');
                                if (container) {
                                    container.innerHTML = `<img src="${e.target.result}" alt="Foto de Perfil" id="profile-preview" class="img-fluid profile-image">
                                    <div class="edit-image-overlay" id="image-overlay">
                                        <i class="fas fa-camera me-1"></i> Alterar imagem
                                    </div>`;
                                }
                            }
                        };
                        
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
            
            // Tipo de usuário altera visualização
            const typeSelect = document.getElementById('type');
            if (typeSelect) {
                typeSelect.addEventListener('change', function() {
                    const permissionsSection = document.querySelector('.permissions-container').closest('.row');
                    if (this.value == '<?php echo Auth::ADMIN; ?>' || this.value == '<?php echo Auth::CLIENT; ?>') {
                        if (permissionsSection) {
                            permissionsSection.style.display = 'none';
                        }
                    } else {
                        if (permissionsSection) {
                            permissionsSection.style.display = 'flex';
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>