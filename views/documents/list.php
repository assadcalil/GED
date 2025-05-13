<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Listagem de Documentos
 */

// Verificar se as configurações já foram incluídas
if (!defined('ROOT_DIR')) {
    require_once __DIR__ . '/../../...../app/Config/App.php';
    require_once __DIR__ . '/../../...../app/Config/Database.php';
    require_once __DIR__ . '/../../...../app/Config/Auth.php';
    require_once __DIR__ . '/../../...../app/Config/Logger.php';
}

// Verificar autenticação
Auth::requireLogin();

// Verificar permissão para visualizar documentos
if (!Auth::isAdmin() && !Auth::hasPermission('documents.view')) {
    header('Location: /access-denied.php');
    exit;
}

// Registrar acesso
Logger::activity('acesso', "Acessou a listagem de documentos");

// Parâmetros de filtro e paginação
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$companyId = isset($_GET['company_id']) ? intval($_GET['company_id']) : 0;
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Configurar offset para paginação
$offset = ($page - 1) * $limit;

// Construir consulta base
$sql = "SELECT d.*, 
        c.name as company_name, c.company_code,
        u.name as uploaded_by_name,
        a.name as approved_by_name,
        cat.name as category_name, cat.color as category_color, cat.icon as category_icon
        FROM documents d
        JOIN companies c ON d.company_id = c.id
        JOIN users u ON d.uploaded_by = u.id
        LEFT JOIN users a ON d.approved_by = a.id
        LEFT JOIN document_categories cat ON d.category_id = cat.id
        WHERE 1=1";
$params = [];

// Adicionar filtros específicos para clientes
if (Auth::isUserType(Auth::CLIENT)) {
    // Cliente só pode ver documentos de suas próprias empresas
    $clientId = Auth::getCurrentUserId();
    $sql .= " AND c.client_id = ?";
    $params[] = $clientId;
}

// Adicionar filtros à consulta
if (!empty($search)) {
    $sql .= " AND (d.title LIKE ? OR d.description LIKE ? OR c.name LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($category > 0) {
    $sql .= " AND d.category_id = ?";
    $params[] = $category;
}

if ($status !== '') {
    $sql .= " AND d.status = ?";
    $params[] = $status;
}

if ($companyId > 0) {
    $sql .= " AND d.company_id = ?";
    $params[] = $companyId;
}

if (!empty($dateFrom)) {
    $sql .= " AND d.upload_date >= ?";
    $params[] = $dateFrom . ' 00:00:00';
}

if (!empty($dateTo)) {
    $sql .= " AND d.upload_date <= ?";
    $params[] = $dateTo . ' 23:59:59';
}

// Consulta para contagem total
$countSql = str_replace("d.*, \n        c.name as company_name, c.company_code,\n        u.name as uploaded_by_name,\n        a.name as approved_by_name,\n        cat.name as category_name, cat.color as category_color, cat.icon as category_icon", "COUNT(*) as total", $sql);
$totalDocuments = Database::selectOne($countSql, $params);
$totalDocuments = $totalDocuments['total'];

// Adicionar ordenação e limite à consulta principal
$sql .= " ORDER BY d.upload_date DESC LIMIT {$offset}, {$limit}";

// Executar consulta
$documents = Database::select($sql, $params);

// Calcular total de páginas
$totalPages = ceil($totalDocuments / $limit);

// Obter categorias para filtro
$categories = Database::select("
    SELECT id, name, color, icon 
    FROM document_categories 
    WHERE is_active = 1 
    ORDER BY name
");

// Obter empresas para filtro
if (Auth::isUserType(Auth::CLIENT)) {
    // Cliente só pode ver suas próprias empresas
    $clientId = Auth::getCurrentUserId();
    $companies = Database::select("
        SELECT id, name, company_code 
        FROM companies 
        WHERE client_id = ? AND active = 1 
        ORDER BY is_main DESC, name
    ", [$clientId]);
} else {
    // Administradores e outros usuários podem ver todas as empresas
    $companies = Database::select("
        SELECT c.id, c.name, c.company_code, u.name as client_name 
        FROM companies c
        JOIN users u ON c.client_id = u.id
        WHERE c.active = 1 
        ORDER BY u.name, c.is_main DESC, c.name
    ");
}

// Verificar mensagens de sucesso ou erro
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Processar download de arquivo
if (isset($_GET['action']) && $_GET['action'] == 'download' && isset($_GET['id'])) {
    $documentId = intval($_GET['id']);
    
    // Verificar permissão para download
    if (!Auth::isAdmin() && !Auth::hasPermission('documents.download')) {
        header('Location: /access-denied.php');
        exit;
    }
    
    // Obter informações do documento
    $document = Database::selectOne("
        SELECT d.*, c.client_id 
        FROM documents d
        JOIN companies c ON d.company_id = c.id
        WHERE d.id = ?
    ", [$documentId]);
    
    if (!$document) {
        header('Location: /documents/list.php?error=' . urlencode('Documento não encontrado'));
        exit;
    }
    
    // Verificar se cliente está tentando acessar documento de outra empresa
    if (Auth::isUserType(Auth::CLIENT) && $document['client_id'] != Auth::getCurrentUserId()) {
        header('Location: /access-denied.php');
        exit;
    }
    
    // Caminho completo do arquivo
    $filePath = ROOT_PATH . $document['file_path'];
    
    // Verificar se o arquivo existe
    if (!file_exists($filePath)) {
        header('Location: /documents/list.php?error=' . urlencode('Arquivo não encontrado no servidor'));
        exit;
    }
    
    // Registrar download
    Logger::activity('documento', "Baixou o documento ID: {$documentId} ({$document['title']})");
    
    // Forçar download do arquivo
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $document['file_type']);
    header('Content-Disposition: attachment; filename="' . basename($document['file_name']) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos - <?php echo SITE_NAME; ?></title>
    
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
        .category-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            color: white;
            display: inline-flex;
            align-items: center;
            margin-right: 5px;
        }
        
        .category-badge i {
            margin-right: 5px;
        }
        
        .document-table th,
        .document-table td {
            vertical-align: middle;
        }
        
        .company-selector {
            margin-bottom: 20px;
        }
        
        .document-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        
        .file-icon {
            font-size: 1.5rem;
            margin-right: 10px;
        }
        
        .document-status {
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 10px;
        }
        
        .status-pending {
            background-color: #ffc107;
            color: #212529;
        }
        
        .status-approved {
            background-color: #28a745;
            color: white;
        }
        
        .status-rejected {
            background-color: #dc3545;
            color: white;
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
                                <h1 class="page-title">Documentos</h1>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Documentos</li>
                                    </ol>
                                </nav>
                            </div>
                            <div class="col-auto">
                                <?php if (Auth::isAdmin() || Auth::hasPermission('documents.upload')): ?>
                                <a href="/documents/upload.php" class="btn btn-primary">
                                    <i class="fas fa-upload me-2"></i> Novo Documento
                                </a>
                                <?php endif; ?>
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
                    
                    <!-- Seletor de Empresa (apenas para clientes) -->
                    <?php if (Auth::isUserType(Auth::CLIENT) && count($companies) > 1): ?>
                    <div class="card mb-4 company-selector">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <label for="company_selector" class="form-label mb-0"><strong>Selecione a Empresa:</strong></label>
                                </div>
                                <div class="col-md-8">
                                    <select id="company_selector" class="form-select" onchange="location = this.value;">
                                        <option value="/documents/list.php">Todas as Empresas</option>
                                        <?php foreach ($companies as $company): ?>
                                            <option value="/documents/list.php?company_id=<?php echo $company['id']; ?>" <?php echo $companyId == $company['id'] ? 'selected' : ''; ?>>
                                                <?php echo "({$company['company_code']}) " . htmlspecialchars($company['name']); ?>
                                                <?php echo $company['is_main'] ? ' (Principal)' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Filtros de Busca -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Filtros</h5>
                        </div>
                        <div class="card-body">
                            <form action="" method="get" class="row g-3">
                                <?php if ($companyId > 0): ?>
                                    <input type="hidden" name="company_id" value="<?php echo $companyId; ?>">
                                <?php endif; ?>
                                
                                <div class="col-md-4">
                                    <label for="search" class="form-label">Buscar</label>
                                    <input type="text" class="form-control" id="search" name="search" placeholder="Título, descrição ou empresa" value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="category" class="form-label">Categoria</label>
                                    <select class="form-select" id="category" name="category">
                                        <option value="0">Todas</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">Todos</option>
                                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pendente</option>
                                        <option value="approved" <?php echo $status == 'approved' ? 'selected' : ''; ?>>Aprovado</option>
                                        <option value="rejected" <?php echo $status == 'rejected' ? 'selected' : ''; ?>>Rejeitado</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="date_from" class="form-label">Data Inicial</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $dateFrom; ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="date_to" class="form-label">Data Final</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $dateTo; ?>">
                                </div>
                                
                                <?php if (!Auth::isUserType(Auth::CLIENT) && !$companyId): ?>
                                <div class="col-md-3">
                                    <label for="company_id" class="form-label">Empresa</label>
                                    <select class="form-select" id="company_id" name="company_id">
                                        <option value="0">Todas</option>
                                        <?php foreach ($companies as $company): ?>
                                        <option value="<?php echo $company['id']; ?>" <?php echo $companyId == $company['id'] ? 'selected' : ''; ?>>
                                            <?php echo "({$company['company_code']}) " . htmlspecialchars($company['name']); ?>
                                            <?php echo isset($company['client_name']) ? ' - ' . htmlspecialchars($company['client_name']) : ''; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                                
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
                    
                    <!-- Tabela de Documentos -->
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="card-title mb-0">Documentos</h5>
                                </div>
                                <div class="col-auto">
                                    <span class="badge bg-primary">Total: <?php echo $totalDocuments; ?> documentos</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 document-table">
                                    <thead>
                                        <tr>
                                            <th width="40">#</th>
                                            <th>Documento</th>
                                            <th>Empresa</th>
                                            <th>Categoria</th>
                                            <th>Data Upload</th>
                                            <th>Status</th>
                                            <th width="120">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($documents) > 0): ?>
                                            <?php foreach ($documents as $document): ?>
                                                <tr>
                                                    <td><?php echo $document['id']; ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php
                                                            // Definir ícone com base no tipo de arquivo
                                                            $fileIcon = 'fa-file';
                                                            $fileType = strtolower(pathinfo($document['file_name'], PATHINFO_EXTENSION));
                                                            
                                                            switch ($fileType) {
                                                                case 'pdf':
                                                                    $fileIcon = 'fa-file-pdf';
                                                                    break;
                                                                case 'doc':
                                                                case 'docx':
                                                                    $fileIcon = 'fa-file-word';
                                                                    break;
                                                                case 'xls':
                                                                case 'xlsx':
                                                                    $fileIcon = 'fa-file-excel';
                                                                    break;
                                                                case 'jpg':
                                                                case 'jpeg':
                                                                case 'png':
                                                                    $fileIcon = 'fa-file-image';
                                                                    break;
                                                                case 'zip':
                                                                case 'rar':
                                                                    $fileIcon = 'fa-file-archive';
                                                                    break;
                                                            }
                                                            ?>
                                                            <i class="fas <?php echo $fileIcon; ?> text-primary file-icon"></i>
                                                            <div>
                                                                <div class="fw-bold"><?php echo htmlspecialchars($document['title']); ?></div>
                                                                <?php if (!empty($document['description'])): ?>
                                                                    <div class="text-muted small"><?php echo htmlspecialchars($document['description']); ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($document['company_name']); ?></div>
                                                            <div class="text-muted small">Código: <?php echo htmlspecialchars($document['company_code']); ?></div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($document['category_name'])): ?>
                                                            <span class="category-badge" style="background-color: <?php echo $document['category_color']; ?>">
                                                                <i class="fas fa-<?php echo $document['category_icon']; ?>"></i>
                                                                <?php echo htmlspecialchars($document['category_name']); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Não categorizado</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo Config::formatDate($document['upload_date'], 'd/m/Y H:i'); ?>
                                                        <div class="text-muted small">por <?php echo htmlspecialchars($document['uploaded_by_name']); ?></div>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $statusClass = '';
                                                        $statusText = '';
                                                        
                                                        switch ($document['status']) {
                                                            case 'pending':
                                                                $statusClass = 'status-pending';
                                                                $statusText = 'Pendente';
                                                                break;
                                                            case 'approved':
                                                                $statusClass = 'status-approved';
                                                                $statusText = 'Aprovado';
                                                                break;
                                                            case 'rejected':
                                                                $statusClass = 'status-rejected';
                                                                $statusText = 'Rejeitado';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="document-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                        
                                                        <?php if ($document['status'] !== 'pending' && !empty($document['approved_by_name'])): ?>
                                                            <div class="text-muted small">
                                                                por <?php echo htmlspecialchars($document['approved_by_name']); ?>
                                                                <?php if (!empty($document['approval_date'])): ?>
                                                                    em <?php echo Config::formatDate($document['approval_date'], 'd/m/Y'); ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="document-actions">
                                                            <?php if (Auth::isAdmin() || Auth::hasPermission('documents.download')): ?>
                                                                <a href="?action=download&id=<?php echo $document['id']; ?>" class="btn btn-sm btn-outline-primary" title="Baixar Documento">
                                                                    <i class="fas fa-download"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (Auth::isAdmin() || Auth::hasPermission('documents.view')): ?>
                                                                <a href="/documents/view.php?id=<?php echo $document['id']; ?>" class="btn btn-sm btn-outline-info" title="Visualizar Detalhes">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (Auth::isAdmin() && $document['status'] == 'pending'): ?>
                                                                <a href="/documents/approval.php?id=<?php echo $document['id']; ?>" class="btn btn-sm btn-outline-success" title="Aprovar/Rejeitar">
                                                                    <i class="fas fa-check-double"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (Auth::isAdmin() || (Auth::hasPermission('documents.delete') && $document['uploaded_by'] == Auth::getCurrentUserId())): ?>
                                                                <a href="/documents/delete.php?id=<?php echo $document['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Excluir Documento" data-document-title="<?php echo htmlspecialchars($document['title']); ?>">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="fas fa-info-circle me-2"></i> Nenhum documento encontrado com os filtros atuais.
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
                                            <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category > 0 ? '&category=' . $category : ''; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?><?php echo $companyId > 0 ? '&company_id=' . $companyId : ''; ?><?php echo !empty($dateFrom) ? '&date_from=' . $dateFrom : ''; ?><?php echo !empty($dateTo) ? '&date_to=' . $dateTo : ''; ?>&limit=<?php echo $limit; ?>" aria-label="Primeira">
                                                <i class="fas fa-angle-double-left"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category > 0 ? '&category=' . $category : ''; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?><?php echo $companyId > 0 ? '&company_id=' . $companyId : ''; ?><?php echo !empty($dateFrom) ? '&date_from=' . $dateFrom : ''; ?><?php echo !empty($dateTo) ? '&date_to=' . $dateTo : ''; ?>&limit=<?php echo $limit; ?>" aria-label="Anterior">
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
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category > 0 ? '&category=' . $category : ''; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?><?php echo $companyId > 0 ? '&company_id=' . $companyId : ''; ?><?php echo !empty($dateFrom) ? '&date_from=' . $dateFrom : ''; ?><?php echo !empty($dateTo) ? '&date_to=' . $dateTo : ''; ?>&limit=<?php echo $limit; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category > 0 ? '&category=' . $category : ''; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?><?php echo $companyId > 0 ? '&company_id=' . $companyId : ''; ?><?php echo !empty($dateFrom) ? '&date_from=' . $dateFrom : ''; ?><?php echo !empty($dateTo) ? '&date_to=' . $dateTo : ''; ?>&limit=<?php echo $limit; ?>" aria-label="Próxima">
                                                <i class="fas fa-angle-right"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category > 0 ? '&category=' . $category : ''; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?><?php echo $companyId > 0 ? '&company_id=' . $companyId : ''; ?><?php echo !empty($dateFrom) ? '&date_from=' . $dateFrom : ''; ?><?php echo !empty($dateTo) ? '&date_to=' . $dateTo : ''; ?>&limit=<?php echo $limit; ?>" aria-label="Última">
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
    
    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o documento <strong id="documentTitle"></strong>?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Excluir</a>
                </div>
            </div>
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
            // Confirmação de exclusão
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            const deleteButtons = document.querySelectorAll('.btn-delete');
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const documentTitle = this.getAttribute('data-document-title');
                    const deleteUrl = this.getAttribute('href');
                    
                    document.getElementById('documentTitle').textContent = documentTitle;
                    document.getElementById('confirmDeleteBtn').setAttribute('href', deleteUrl);
                    
                    deleteModal.show();
                });
            });
        });
    </script>
</body>
</html>