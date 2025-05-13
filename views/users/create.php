<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Sistema Contabilidade Estrela 2.0
 * Cadastro de Usuários
 */

// Verificar se as configurações já foram incluídas
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(dirname(__FILE__))));
    require_once ROOT_DIR . '/app/Config/App.php';
    require_once ROOT_DIR . '/app/Config/Database.php';
    require_once ROOT_DIR . '/app/Config/Auth.php';
    require_once ROOT_DIR . '/app/Config/Logger.php';
    require_once ROOT_DIR . '/app/Config/Email.php';
    require_once ROOT_DIR . '/app/Services/EmailService.php';
}

// Verificar autenticação e permissão
Auth::requireLogin();

// Apenas administradores e editores podem cadastrar usuários
if (!Auth::isAdmin() && !Auth::isUserType(Auth::EDITOR)) {
    header('Location: /access-denied.php');
    exit;
}

// Registrar acesso
Logger::activity('acesso', 'Acessou o formulário de cadastro de usuários');

// Variáveis para armazenar valores e mensagens
$userData = [
    'name' => '',
    'email' => '',
    'username' => '',
    'type' => Auth::EMPLOYEE, // Tipo padrão: funcionário
    'phone' => '',
    'cpf' => '',
    'active' => 1
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
        'type' => isset($_POST['type']) ? intval($_POST['type']) : Auth::EMPLOYEE,
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
        // Verificar se e-mail já existe
        $existingUser = Database::selectOne("SELECT id FROM users WHERE email = ?", [$userData['email']]);
        if ($existingUser) {
            $errors['email'] = 'Este e-mail já está sendo usado por outro usuário';
        }
    }
    
    if (empty($userData['username'])) {
        $errors['username'] = 'O nome de usuário é obrigatório';
    } elseif (strlen($userData['username']) < 4) {
        $errors['username'] = 'O nome de usuário deve ter pelo menos 4 caracteres';
    } else {
        // Verificar se username já existe
        $existingUser = Database::selectOne("SELECT id FROM users WHERE username = ?", [$userData['username']]);
        if ($existingUser) {
            $errors['username'] = 'Este nome de usuário já está sendo usado';
        }
    }
    
    // Validar tipo de usuário
    if (!array_key_exists($userData['type'], Auth::$userTypes)) {
        $errors['type'] = 'Tipo de usuário inválido';
    }
    
    // Validar senha
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($password)) {
        $errors['password'] = 'A senha é obrigatória';
    } elseif (strlen($password) < 8) {
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
    
    // Se não houver erros, prosseguir com o cadastro
    if (empty($errors)) {
        try {
            // Iniciar transação
            Database::beginTransaction();
            
            // Criar hash da senha
            $passwordHash = Auth::hashPassword($password);
            
            // Preparar dados para inserção
            $insertData = [
                'name' => $userData['name'],
                'email' => $userData['email'],
                'username' => $userData['username'],
                'password' => $passwordHash,
                'type' => $userData['type'],
                'phone' => $userData['phone'],
                'cpf' => $userData['cpf'],
                'active' => $userData['active'],
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION['user_id']
            ];
            
            // Inserir usuário
            $userId = Database::insert('users', $insertData);
                if ($userId) {
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
                                // Atualizar usuário com imagem de perfil
                                Database::update('users', ['profile_image' => $fileName], 'id = ?', [$userId]);
                                
                                // Registrar upload
                                Logger::upload($fileName, 'sucesso', 'Imagem de perfil para usuário ID: ' . $userId);
                            } else {
                                // Registrar falha no upload
                                Logger::upload($image['name'], 'falha', 'Não foi possível mover o arquivo');
                            }
                        } else {
                            // Registrar tipo de arquivo inválido
                            Logger::upload($image['name'], 'falha', 'Tipo de arquivo inválido: ' . $image['type']);
                        }
                    }
                    
                    // Enviar email de boas-vindas (dentro da transação)
                    try {
                        $emailService = new EmailUsuarioService();
                        $resultado = $emailService->enviarEmailBoasVindas($userData, $password);
                        
                        if (!$resultado['sucesso']) {
                            // Se o email falhar, lança exceção para reverter a transação
                            throw new Exception("Falha ao enviar e-mail de boas-vindas: " . $resultado['mensagem']);
                        }
                    } catch (Exception $e) {
                        // Se houver erro no envio do email, lança exceção para desfazer todo o cadastro
                        throw new Exception("Erro ao enviar e-mail de boas-vindas: " . $e->getMessage());
                    }
                    
                    // Se chegou aqui, tudo deu certo - confirma a transação
                    Database::commit();
                    
                    // Registrar atividade
                    Logger::activity('usuário', "Usuário ID: {$userId} ({$userData['name']}) foi cadastrado");
                    
                    // Definir mensagem de sucesso
                    $success = "Usuário cadastrado com sucesso e e-mail de boas-vindas enviado!";
                    
                    // Limpar formulário para novo cadastro
                    $userData = [
                        'name' => '',
                        'email' => '',
                        'username' => '',
                        'type' => Auth::EMPLOYEE,
                        'phone' => '',
                        'cpf' => '',
                        'active' => 1
                    ];
                } else {
                    throw new Exception("Erro ao inserir usuário no banco de dados");
                }
        } catch (Exception $e) {
            // Rollback em caso de erro
            Database::rollback();
            
            // Registrar erro
            Logger::error("Erro ao cadastrar usuário: " . $e->getMessage());
            
            // Definir mensagem de erro
            $error = "Erro ao cadastrar usuário: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário - <?php echo SITE_NAME; ?></title>
    
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
                                <h1 class="page-title">Cadastro de Usuário</h1>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="/users/list.php">Usuários</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Novo Usuário</li>
                                    </ol>
                                </nav>
                            </div>
                            <div class="col-auto">
                                <a href="/ged2.0/views/users/list.php" class="btn btn-secondary">
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
                    
                    <!-- Formulário de Cadastro -->
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
                                                <label for="password" class="form-label">Senha <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" required>
                                                    <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1">
                                                        <i class="far fa-eye"></i>
                                                    </button>
                                                    <?php if (isset($errors['password'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="form-text">Mínimo de 8 caracteres, contendo letras maiúsculas, minúsculas, números e símbolos.</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="confirm_password" class="form-label">Confirmar Senha <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password" required>
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
                                                    <img src="/GED2.0/assets/img/avatar.png" alt="Foto de Perfil" id="profile-preview" class="img-fluid rounded-circle profile-image">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="profile_image" class="form-label">Selecione uma imagem</label>
                                                    <input class="form-control" type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png">
                                                    <div class="form-text">JPG ou PNG, máximo 2MB.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="row">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i> Salvar Usuário
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
            
            if (profileInput && profilePreview) {
                profileInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            profilePreview.src = e.target.result;
                        };
                        
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
            
            // Gerador de senha aleatória
            const generateButton = document.getElementById('generate-password');
            if (generateButton) {
                generateButton.addEventListener('click', function() {
                    const password = generateStrongPassword(12);
                    document.getElementById('password').value = password;
                    document.getElementById('confirm_password').value = password;
                });
            }
        });
        
        // Função para gerar senha forte aleatória
        function generateStrongPassword(length = 12) {
            const uppercaseChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            const lowercaseChars = 'abcdefghijklmnopqrstuvwxyz';
            const numberChars = '0123456789';
            const specialChars = '!@#$%^&*()_+{}[]|:;<>,.?/~-';
            
            const allChars = uppercaseChars + lowercaseChars + numberChars + specialChars;
            let password = '';
            
            // Garantir pelo menos um de cada tipo
            password += uppercaseChars.charAt(Math.floor(Math.random() * uppercaseChars.length));
            password += lowercaseChars.charAt(Math.floor(Math.random() * lowercaseChars.length));
            password += numberChars.charAt(Math.floor(Math.random() * numberChars.length));
            password += specialChars.charAt(Math.floor(Math.random() * specialChars.length));
            
            // Completar o restante
            for (let i = 4; i < length; i++) {
                password += allChars.charAt(Math.floor(Math.random() * allChars.length));
            }
            
            // Embaralhar a senha
            return password.split('').sort(() => 0.5 - Math.random()).join('');
        }
    </script>
</body>
</html>