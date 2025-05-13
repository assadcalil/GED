<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Gerenciar Empresas do Cliente
 */

// Verificar se as configurações já foram incluídas
if (!defined('ROOT_DIR')) {
    require_once __DIR__ . '/../../...../app/Config/App.php';
    require_once __DIR__ . '/../../...../app/Config/Database.php';
    require_once __DIR__ . '/../../...../app/Config/Auth.php';
    require_once __DIR__ . '/../../...../app/Config/Logger.php';
}

// Verificar autenticação e permissão
Auth::requireLogin();

// Verificar permissão para visualizar clientes e empresas
if (!Auth::isAdmin() && !Auth::isUserType(Auth::EDITOR) && !Auth::hasPermission('clients.view') && !Auth::hasPermission('companies.view')) {
    header('Location: /access-denied.php');
    exit;
}

// Obter ID do cliente
$clientId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($clientId <= 0) {
    // ID inválido, redirecionar para listagem de clientes
    header('Location: /clients/list.php?error=' . urlencode('ID de cliente inválido'));
    exit;
}

// Obter dados do cliente
$client = Database::selectOne("SELECT * FROM users WHERE id = ? AND type = ?", [$clientId, Auth::CLIENT]);

if (!$client) {
    // Cliente não encontrado, redirecionar para listagem
    header('Location: /clients/list.php?error=' . urlencode('Cliente não encontrado'));
    exit;
}

// Registrar acesso
Logger::activity('acesso', "Acessou a gestão de empresas do cliente ID: {$clientId}");

// Obter todas as empresas do cliente
$companies = Database::select("
    SELECT c.*, 
           CASE WHEN c.parent_id IS NULL THEN NULL ELSE p.name END as parent_name
    FROM companies c
    LEFT JOIN companies p ON c.parent_id = p.id
    WHERE c.client_id = ?
    ORDER BY c.is_main DESC, c.name ASC
", [$clientId]);

// Processar alterações nas empresas
$success = '';
$error = '';

// Processar vinculação/desvinculação de empresa principal
if (isset($_POST['action']) && $_POST['action'] == 'set_main') {
    $companyId = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
    
    if ($companyId > 0) {
        try {
            // Iniciar transação
            Database::beginTransaction();
            
            // Remover flag de empresa principal de todas as empresas do cliente
            Database::update('companies', ['is_main' => 0], 'client_id = ?', [$clientId]);
            
            // Definir a empresa selecionada como principal
            Database::update('companies', ['is_main' => 1], 'id = ? AND client_id = ?', [$companyId, $clientId]);
            
            // Commit da transação
            Database::commit();
            
            // Registrar atividade
            Logger::activity('empresa', "Empresa ID: {$companyId} definida como principal para o cliente ID: {$clientId}");
            
            // Definir mensagem de sucesso
            $success = "Empresa principal definida com sucesso!";
            
            // Recarregar lista de empresas
            $companies = Database::select("
                SELECT c.*, 
                       CASE WHEN c.parent_id IS NULL THEN NULL ELSE p.name END as parent_name
                FROM companies c
                LEFT JOIN companies p ON c.parent_id = p.id
                WHERE c.client_id = ?
                ORDER BY c.is_main DESC, c.name ASC
            ", [$clientId]);
        } catch (Exception $e) {
            // Rollback em caso de erro
            Database::rollback();
            
            // Registrar erro
            Logger::error("Erro ao definir empresa principal: " . $e->getMessage());
            
            // Definir mensagem de erro
            $error = "Erro ao definir empresa principal: " . $e->getMessage();
        }
    }
}

// Processar vinculação de empresa como filial
if (isset($_POST['action']) && $_POST['action'] == 'set_parent') {
    $companyId = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
    $parentId = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    
    if ($companyId > 0) {
        try {
            // Verificar se a empresa filial e a matriz pertencem ao mesmo cliente
            if ($parentId) {
                $parentCompany = Database::selectOne("SELECT client_id FROM companies WHERE id = ?", [$parentId]);
                
                if (!$parentCompany || $parentCompany['client_id'] != $clientId) {
                    throw new Exception("Empresa matriz inválida");
                }
                
                // Verificar se não está criando um ciclo (empresa sendo filial dela mesma)
                if ($parentId == $companyId) {
                    throw new Exception("Uma empresa não pode ser filial dela mesma");
                }
            }
            
            // Atualizar a vinculação
            Database::update('companies', [
                'parent_id' => $parentId ?: null
            ], 'id = ? AND client_id = ?', [$companyId, $clientId]);
            
            // Registrar atividade
            if ($parentId) {
                Logger::activity('empresa', "Empresa ID: {$companyId} vinculada como filial da empresa ID: {$parentId}");
                $success = "Empresa vinculada como filial com sucesso!";
            } else {
                Logger::activity('empresa', "Empresa ID: {$companyId} desvinculada como filial");
                $success = "Empresa desvinculada como filial com sucesso!";
            }
            
            // Recarregar lista de empresas
            $companies = Database::select("
                SELECT c.*, 
                       CASE WHEN c.parent_id IS NULL THEN NULL ELSE p.name END as parent_name
                FROM companies c
                LEFT JOIN companies p ON c.parent_id = p.id
                WHERE c.client_id = ?
                ORDER BY c.is_main DESC, c.name ASC
            ", [$clientId]);
        } catch (Exception $e) {
            // Registrar erro
            Logger::error("Erro ao vincular empresa: " . $e->getMessage());
            
            // Definir mensagem de erro
            $error = "Erro ao vincular empresa: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empresas do Cliente - <?php echo SITE_NAME; ?></title>
    
    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- Estilo personalizado -->
    <link rel="stylesheet" href="/GED2.0/assets/css/dashboard.css">
    
    <style>
        .company-card {
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .company-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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
        
        .company-subsidiary-badge {
            position: absolute;
            top: -10px;
            right: 10px;
            background-color: #007bff;
            color: white;
            font-size: 12px;
            padding: 2px 10px;
            border-radius: 10px;
        }
    </style>
</head>
<body data-user-type="<?php echo $_SESSION['user_type']; ?>">
    <div class="dashboard-container">
        <!-- Menu Lateral -->
        <?php include_once __DIR__ . '/../views/partials/sidebar.php'; ?>
        
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
                                <h1 class="page-title">Empresas do Cliente</h1>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="/clients/list.php">Clientes</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Empresas do Cliente</li>
                                    </ol>
                                </nav>
                            </div>
                            <div class="col-auto">
                                <a href="/companies/create.php?client_id=<?php echo $clientId; ?>" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i> Nova Empresa
                                </a>
                                <a href="/clients/list.php" class="btn btn-secondary ms-2">
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
                    
                    <!-- Informações do Cliente -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informações do Cliente</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Nome:</strong> <?php echo htmlspecialchars($client['name']); ?></p>
                                    <p><strong>E-mail:</strong> <?php echo htmlspecialchars($client['email']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Telefone:</strong> <?php echo !empty($client['phone']) ? htmlspecialchars($client['phone']) : 'Não informado'; ?></p>
                                    <p><strong>CPF:</strong> <?php echo !empty($client['cpf']) ? htmlspecialchars($client['cpf']) : 'Não informado'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lista de Empresas -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Empresas Vinculadas</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($companies)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> Este cliente não possui empresas vinculadas.
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($companies as $company): ?>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="company-card <?php echo $company['is_main'] ? 'main' : ''; ?>">
                                                <?php if ($company['is_main']): ?>
                                                    <div class="company-main-badge">Matriz</div>
                                                <?php elseif (!empty($company['parent_id'])): ?>
                                                    <div class="company-subsidiary-badge">Filial</div>
                                                <?php endif; ?>
                                                
                                                <h5><?php echo htmlspecialchars($company['name']); ?></h5>
                                                <p class="mb-1"><strong>Código:</strong> <?php echo htmlspecialchars($company['company_code']); ?></p>
                                                <p class="mb-1"><strong>CNPJ:</strong> <?php echo htmlspecialchars($company['cnpj']); ?></p>
                                                
                                                <?php if (!empty($company['parent_id'])): ?>
                                                    <p class="mb-1"><strong>Matriz:</strong> <?php echo htmlspecialchars($company['parent_name']); ?></p>
                                                <?php endif; ?>
                                                
                                                <p class="text-muted mb-3"><?php echo $company['active'] ? '<span class="text-success">Ativa</span>' : '<span class="text-danger">Inativa</span>'; ?></p>
                                                
                                                <div class="d-flex justify-content-between">
                                                    <div class="btn-group">
                                                        <a href="/companies/edit.php?id=<?php echo $company['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-edit me-1"></i> Editar
                                                        </a>
                                                        <a href="/companies/view.php?id=<?php echo $company['id']; ?>" class="btn btn-sm btn-outline-info">
                                                            <i class="fas fa-eye me-1"></i> Detalhes
                                                        </a>
                                                    </div>
                                                    
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="company-actions-<?php echo $company['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                            Ações
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="company-actions-<?php echo $company['id']; ?>">
                                                            <?php if (!$company['is_main']): ?>
                                                                <li>
                                                                    <form action="" method="post">
                                                                        <input type="hidden" name="action" value="set_main">
                                                                        <input type="hidden" name="company_id" value="<?php echo $company['id']; ?>">
                                                                        <button type="submit" class="dropdown-item">
                                                                            <i class="fas fa-star me-1 text-warning"></i> Definir como Principal
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($company['parent_id']): ?>
                                                                <li>
                                                                    <form action="" method="post">
                                                                        <input type="hidden" name="action" value="set_parent">
                                                                        <input type="hidden" name="company_id" value="<?php echo $company['id']; ?>">
                                                                        <input type="hidden" name="parent_id" value="">
                                                                        <button type="submit" class="dropdown-item">
                                                                            <i class="fas fa-unlink me-1 text-danger"></i> Desvincular como Filial
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php else: ?>
                                                                <!-- Opção para vincular como filial de outra empresa -->
                                                                <li>
                                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#linkModal-<?php echo $company['id']; ?>">
                                                                        <i class="fas fa-link me-1 text-primary"></i> Vincular como Filial
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a href="/documents/list.php?company_id=<?php echo $company['id']; ?>" class="dropdown-item">
                                                                    <i class="fas fa-file-alt me-1 text-info"></i> Ver Documentos
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a href="/documents/upload.php?company_id=<?php echo $company['id']; ?>" class="dropdown-item">
                                                                    <i class="fas fa-upload me-1 text-success"></i> Enviar Documento
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Modal para vincular como filial -->
                                            <div class="modal fade" id="linkModal-<?php echo $company['id']; ?>" tabindex="-1" aria-labelledby="linkModalLabel-<?php echo $company['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="linkModalLabel-<?php echo $company['id']; ?>">Vincular como Filial</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                        </div>
                                                        <form action="" method="post">
                                                            <div class="modal-body">
                                                                <p>Selecione a empresa matriz para <strong><?php echo htmlspecialchars($company['name']); ?></strong>:</p>
                                                                <input type="hidden" name="action" value="set_parent">
                                                                <input type="hidden" name="company_id" value="<?php echo $company['id']; ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <select name="parent_id" class="form-select" required>
                                                                        <option value="">Selecione uma empresa matriz</option>
                                                                        <?php foreach ($companies as $potential_parent): ?>
                                                                            <?php if ($potential_parent['id'] != $company['id'] && empty($potential_parent['parent_id'])): ?>
                                                                                <option value="<?php echo $potential_parent['id']; ?>">
                                                                                    <?php echo htmlspecialchars($potential_parent['name']); ?>
                                                                                    <?php echo $potential_parent['is_main'] ? ' (Principal)' : ''; ?>
                                                                                </option>
                                                                            <?php endif; ?>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                    <div class="form-text">Apenas empresas que não são filiais podem ser selecionadas como matriz.</div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" class="btn btn-primary">Vincular</button>
                                                            </div>
                                                        </form>
                                                    </div>
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
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Script personalizado -->
    <script src="/GED2.0/assets/js/dashboard.js"></script>
</body>
</html>