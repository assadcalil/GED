<?php
// Definir diretório raiz para includes (definição única)
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(dirname(__FILE__))));
}

// Definir ROOT_PATH para compatibilidade com código existente
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', ROOT_DIR);
}

// Configurações para exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Sistema Contabilidade Estrela 2.0
 * Listagem de Usuários
 */

// Incluir arquivos necessários
require_once ROOT_DIR . '/app/Config/App.php';
require_once ROOT_DIR . '/app/Config/Database.php';
require_once ROOT_DIR . '/app/Config/Auth.php';
require_once ROOT_DIR . '/app/Config/Logger.php';

// Autoloader para encontrar classes
spl_autoload_register(function ($class_name) {
    // Verificar se é uma classe com namespace
    if (strpos($class_name, '\\') !== false) {
        // Classe com namespace
        $file = ROOT_DIR . '/' . str_replace('\\', '/', $class_name) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // Classes sem namespace - verificar em locais comuns
    $paths = [
        ROOT_DIR . '/app/Config/',
        ROOT_DIR . '/app/Models/',
        ROOT_DIR . '/app/Controllers/',
        ROOT_DIR . '/app/Services/',
        ROOT_DIR . '/app/Utils/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Verificar se a classe Auth existe, caso contrário tentar carregá-la diretamente
if (!class_exists('Auth')) {
    $authPaths = [
        ROOT_DIR . '/app/Config/Auth.php',
        ROOT_DIR . '/app/Models/Auth.php',
        ROOT_DIR . '/app/Services/Auth.php',
        ROOT_DIR . '/Auth.php'
    ];
    
    foreach ($authPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
    
    // Se ainda não existir, procurar em toda a estrutura de diretórios
    if (!class_exists('Auth')) {
        function findAuthFile($dir) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $path = $dir . '/' . $file;
                if (is_dir($path)) {
                    $result = findAuthFile($path);
                    if ($result) return $result;
                } elseif ($file === 'Auth.php' || $file === 'auth.php') {
                    return $path;
                }
            }
            return false;
        }
        
        $authFile = findAuthFile(ROOT_DIR);
        if ($authFile) {
            require_once $authFile;
        }
    }
}

// Verificar se a sessão está ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação e permissão
if (class_exists('Auth')) {
    Auth::requireLogin();
    
    // Apenas administradores e editores podem acessar a listagem de usuários
    if (!Auth::isAdmin() && !Auth::isUserType(Auth::EDITOR)) {
        header('Location: ' . ROOT_DIR . '/access-denied.php');
        exit;
    }
    
    // Registrar acesso
    if (class_exists('Logger')) {
        Logger::activity('acesso', 'Acessou a listagem de usuários');
    }
} else {
    die("Erro crítico: Classe Auth não encontrada. Verifique a instalação do sistema.");
}

// Parâmetros de filtro e paginação
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$type = isset($_GET['type']) ? intval($_GET['type']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Configurar offset para paginação
$offset = ($page - 1) * $limit;

// Construir consulta base
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM users WHERE id = u.id) as users_count
        FROM users u
        WHERE 1=1";
$params = [];

// Adicionar filtros à consulta
if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($type > 0) {
    $sql .= " AND u.type = ?";
    $params[] = $type;
}

if ($status === 'active') {
    $sql .= " AND u.active = 1";
} elseif ($status === 'inactive') {
    $sql .= " AND u.active = 0";
}

// Consulta para contagem total
$countSql = "SELECT COUNT(*) as total FROM ($sql) as subquery";
$totalUsers = Database::selectOne($countSql, $params);
$totalUsers = $totalUsers['total'];

// Adicionar ordenação e limite à consulta principal
$sql .= " ORDER BY u.name ASC LIMIT {$offset}, {$limit}";

// Executar consulta
$users = Database::select($sql, $params);

// Calcular total de páginas
$totalPages = ceil($totalUsers / $limit);

// Processar ações (ativar/desativar usuário)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $userId = intval($_GET['id']);
    
    if ($action === 'activate' || $action === 'deactivate') {
        $active = ($action === 'activate') ? 1 : 0;
        $result = Database::update('users', ['active' => $active], 'id = ?', [$userId]);
        
        if ($result) {
            $actionText = $active ? 'ativado' : 'desativado';
            Logger::activity('usuário', "Usuário ID: {$userId} foi {$actionText}");
            $message = "Usuário {$actionText} com sucesso.";
            header("Location: " . ROOT_DIR . "/views/users/list.php?success=" . urlencode($message));
            exit;
        } else {
            $message = "Erro ao alterar status do usuário.";
            header("Location: " . ROOT_DIR . "/views/users/list.php?error=" . urlencode($message));
            exit;
        }
    }
}

// Verificar mensagens de sucesso ou erro
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listagem de Usuários - <?php echo SITE_NAME; ?></title>
    
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
</head>
<body data-user-type="<?php echo $_SESSION['user_type']; ?>">
    <div class="dashboard-container">
        <!-- Menu Lateral -->
        <?php include_once ROOT_DIR . '/views/partials/sidebar.php'; ?>
        
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
                            <li><a class="dropdown-item" href="<?php echo ROOT_DIR; ?>/profile"><i class="fas fa-user-circle me-2"></i> Meu Perfil</a></li>
                            <li><a class="dropdown-item" href="<?php echo ROOT_DIR; ?>/settings"><i class="fas fa-cog me-2"></i> Configurações</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo ROOT_DIR; ?>/?logout=1"><i class="fas fa-sign-out-alt me-2"></i> Sair</a></li>
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
                                <h1 class="page-title">Usuários</h1>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="<?php echo ROOT_DIR; ?>/dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Listagem de Usuários</li>
                                    </ol>
                                </nav>
                            </div>
                            <div class="col-auto">
                                <a href="<?php echo ROOT_DIR; ?>/views/users/create.php" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i> Novo Usuário
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
                    
                    <!-- Filtros de Busca -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Filtros</h5>
                        </div>
                        <div class="card-body">
                            <form action="" method="get" class="row g-3">
                                <div class="col-md-4">
                                    <label for="search" class="form-label">Buscar</label>
                                    <input type="text" class="form-control" id="search" name="search" placeholder="Nome, e-mail ou usuário" value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="type" class="form-label">Tipo de Usuário</label>
                                    <select class="form-select" id="type" name="type">
                                        <option value="0">Todos</option>
                                        <?php foreach (Auth::$userTypes as $typeId => $typeName): ?>
                                        <option value="<?php echo $typeId; ?>" <?php echo $type == $typeId ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($typeName); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">Todos</option>
                                        <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Ativos</option>
                                        <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inativos</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="limit" class="form-label">Itens por página</label>
                                    <select class="form-select" id="limit" name="limit">
                                        <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                                        <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                        <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                                    </select>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Tabela de Usuários -->
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="card-title mb-0">Lista de Usuários</h5>
                                </div>
                                <div class="col-auto">
                                    <span class="badge bg-primary">Total: <?php echo $totalUsers; ?> usuários</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th width="50">#</th>
                                            <th>Nome</th>
                                            <th>E-mail</th>
                                            <th>Tipo</th>
                                            <th>Empresas</th>
                                            <th>Status</th>
                                            <th>Último Acesso</th>
                                            <th width="100">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($users) > 0): ?>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td><?php echo $user['id']; ?></td>
                                                    <td>
                                                        <div>
                                                            <?php echo htmlspecialchars($user['name']); ?>
                                                            <?php if (Auth::isAdmin() && $user['type'] == Auth::ADMIN): ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars(Auth::$userTypes[$user['type']] ?? 'Desconhecido'); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td>
                                                        <?php if ($user['type'] == Auth::CLIENT): ?>
                                                            <?php echo $user['users_count']; ?>
                                                            <?php if ($user['users_count'] > 0): ?>
                                                                <a href="<?php echo ROOT_DIR; ?>/views/users/list.php?client_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-link p-0 ms-2" data-bs-toggle="tooltip" title="Ver empresas">
                                                                    <i class="fas fa-external-link-alt"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">N/A</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($user['active'] == 1): ?>
                                                            <span class="badge bg-success">Ativo</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Inativo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($user['last_login'])): ?>
                                                            <?php echo Config::formatDate($user['last_login'], 'd/m/Y H:i'); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Nunca acessou</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="<?php echo ROOT_DIR; ?>/views/users/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            
                                                            <?php if ($user['id'] != $_SESSION['user_id']): // Não permitir alterar status do próprio usuário ?>
                                                                <?php if ($user['active'] == 1): ?>
                                                                    <a href="<?php echo ROOT_DIR; ?>/views/users/list.php?action=deactivate&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning btn-status-toggle" data-bs-toggle="tooltip" title="Desativar">
                                                                        <i class="fas fa-user-times"></i>
                                                                    </a>
                                                                <?php else: ?>
                                                                    <a href="<?php echo ROOT_DIR; ?>/views/users/list.php?action=activate&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-success btn-status-toggle" data-bs-toggle="tooltip" title="Ativar">
                                                                        <i class="fas fa-user-check"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="fas fa-info-circle me-2"></i> Nenhum usuário encontrado com os filtros atuais.
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Paginação -->
                        <?php if ($totalPages > 1): ?>
                        <div class="card-footer">
                            <nav aria-label="Navegação de página">
                                <ul class="pagination justify-content-center mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type; ?>&status=<?php echo $status; ?>" aria-label="Primeira">
                                                <i class="fas fa-angle-double-left"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type; ?>&status=<?php echo $status; ?>" aria-label="Anterior">
                                                <i class="fas fa-angle-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    // Exibir no máximo 5 links de página
                                    $startPage = max(1, min($page - 2, $totalPages - 4));
                                    $endPage = min($totalPages, max($page + 2, 5));
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++):
                                    ?>
                                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type; ?>&status=<?php echo $status; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type; ?>&status=<?php echo $status; ?>" aria-label="Próxima">
                                                <i class="fas fa-angle-right"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $totalPages; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type; ?>&status=<?php echo $status; ?>" aria-label="Última">
                                                <i class="fas fa-angle-double-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
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
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Confirmar alteração de status
            const statusButtons = document.querySelectorAll('.btn-status-toggle');
            
            statusButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const url = this.getAttribute('href');
                    const isActivate = url.includes('action=activate');
                    const action = isActivate ? 'ativar' : 'desativar';
                    
                    if (confirm(`Tem certeza que deseja ${action} este usuário?`)) {
                        window.location.href = url;
                    }
                });
            });
        });
    </script>
</body>
</html>