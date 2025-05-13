<?php

// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}

/**
 * Sistema Contabilidade Estrela 2.0
 * Gerenciamento de Assinantes
 */

// Verificar se as configurações já foram incluídas
if (!defined('ROOT_DIR')) {
    require_once __DIR__ . '/../../../...../app/Config/App.php';
    require_once __DIR__ . '/../../../...../app/Config/Database.php';
    require_once __DIR__ . '/../../../...../app/Config/Auth.php';
    require_once __DIR__ . '/../../../...../app/Config/Logger.php';
}

// Incluir modelos necessários
require_once ROOT_PATH . '/models/newsletter_model.php';

// Verificar autenticação
Auth::requireLogin();

// Registrar acesso
Logger::activity('acesso', 'Acessou o gerenciamento de assinantes');

// Obter parâmetros de filtro e paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Obter assinantes
$result = SubscriberModel::getAll($page, $limit, $search, $status);
$subscribers = $result['data'];
$total = $result['total'];
$totalPages = $result['pages'];

// Obter empresas para o select de empresas associadas
$empresas = Database::select("SELECT id, emp_name, emp_cnpj FROM empresas ORDER BY emp_name ASC");

// Função para formatar data
function formatarData($data) {
    if (empty($data)) return '-';
    
    $timestamp = strtotime($data);
    return date('d/m/Y H:i', $timestamp);
}

// Obter mensagem flash
function getFlashMessage() {
    $message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : null;
    unset($_SESSION['flash_message']);
    return $message;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinantes - <?php echo SITE_NAME; ?></title>
    
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
        .status-badge {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-unsubscribed {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .table th, .table td {
            vertical-align: middle;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(10, 75, 120, 0.05);
        }
        
        .actions-column {
            width: 120px;
        }
        
        /* Alerta de IR */
        .tax-alert {
            background-color: #ff7e00;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .tax-alert i {
            margin-right: 10px;
        }
        
        .import-section {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .import-icon {
            font-size: 24px;
            color: #0a4b78;
            margin-right: 10px;
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
            <?php include_once ROOT_PATH . '/views/partials/header.php'; ?>
            
            <!-- Conteúdo da Página -->
            <div class="dashboard-content">
                <div class="container-fluid">
                    <!-- Cabeçalho da Página -->
                    <div class="page-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="page-title">Assinantes da Newsletter</h1>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="/GED2.0/views/dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="/GED2.0/views/newsletter/list.php">Newsletters</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Assinantes</li>
                                    </ol>
                                </nav>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubscriberModal">
                                    <i class="fas fa-plus me-2"></i> Novo Assinante
                                </button>
                                <div class="btn-group ms-2">
                                    <button type="button" class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-download me-1"></i> Exportar
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="/GED2.0/controllers/newsletter_controller.php?acao=exportar&formato=csv"><i class="fas fa-file-csv me-2"></i> CSV</a></li>
                                        <li><a class="dropdown-item" href="/GED2.0/controllers/newsletter_controller.php?acao=exportar&formato=excel"><i class="fas fa-file-excel me-2"></i> Excel</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Alerta de Imposto de Renda -->
                    <div class="tax-alert">
                        <div class="d-flex align-items-center">
                            <div>
                                <i class="fas fa-exclamation-circle fa-2x"></i>
                            </div>
                            <div class="ms-3">
                                <h5 class="mb-1">Imposto de Renda 2025 - Prazo final: 31 de maio!</h5>
                                <p class="mb-0">Aproveite para importar seus clientes como assinantes e enviar uma newsletter informando sobre o prazo!</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mensagens de feedback -->
                    <?php 
                    $flashMessage = getFlashMessage();
                    if ($flashMessage): 
                    ?>
                    <div class="alert alert-<?php echo ($flashMessage['type'] == 'error' ? 'danger' : $flashMessage['type']); ?> alert-dismissible fade show" role="alert">
                        <?php echo $flashMessage['message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Seção de importação -->
                    <div class="import-section">
                        <div class="row align-items-center">
                            <div class="col-md-1 text-center">
                                <i class="fas fa-file-import import-icon"></i>
                            </div>
                            <div class="col-md-8">
                                <h5>Importar Clientes como Assinantes</h5>
                                <p class="mb-0">Importe automaticamente seus clientes do sistema para a lista de assinantes da newsletter.</p>
                            </div>
                            <div class="col-md-3 text-end">
                                <form action="/GED2.0/controllers/newsletter_controller.php" method="post">
                                    <input type="hidden" name="acao" value="importar_assinantes">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-sync me-2"></i> Importar Clientes
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card de listagem -->
                    <div class="card">
                        <div class="card-body">
                            <!-- Filtros de busca -->
                            <form action="" method="get" class="mb-4">
                                <div class="row align-items-end">
                                    <div class="col-md-6">
                                        <label for="search" class="form-label">Buscar</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                            <input type="text" class="form-control" id="search" name="search" placeholder="Buscar por nome ou e-mail..." value="<?php echo htmlspecialchars($search); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="" <?php echo $status == '' ? 'selected' : ''; ?>>Todos</option>
                                            <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Ativos</option>
                                            <option value="unsubscribed" <?php echo $status == 'unsubscribed' ? 'selected' : ''; ?>>Cancelados</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-flex">
                                        <button type="submit" class="btn btn-primary me-2">
                                            <i class="fas fa-filter"></i>
                                        </button>
                                        <a href="/GED2.0/views/newsletter/subscribers.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-eraser"></i>
                                        </a>
                                    </div>
                                </div>
                            </form>
                            
                            <!-- Estatísticas -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="alert alert-info mb-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-info-circle me-2"></i> 
                                                Exibindo <?php echo count($subscribers); ?> de <?php echo $total; ?> assinantes.
                                            </div>
                                            <div>
                                                <?php 
                                                // Contar assinantes por status
                                                $sqlAtivos = "SELECT COUNT(*) FROM subscribers WHERE status = 'active'";
                                                $sqlCancelados = "SELECT COUNT(*) FROM subscribers WHERE status = 'unsubscribed'";
                                                
                                                $ativos = Database::selectOne($sqlAtivos)['COUNT(*)'];
                                                $cancelados = Database::selectOne($sqlCancelados)['COUNT(*)'];
                                                ?>
                                                <span class="badge rounded-pill bg-light text-dark me-2">
                                                    <i class="fas fa-users me-1"></i> Total: <?php echo $total; ?>
                                                </span>
                                                <span class="badge rounded-pill bg-success me-2">
                                                    <i class="fas fa-check-circle me-1"></i> Ativos: <?php echo $ativos; ?>
                                                </span>
                                                <span class="badge rounded-pill bg-danger">
                                                    <i class="fas fa-times-circle me-1"></i> Cancelados: <?php echo $cancelados; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tabela de resultados -->
                            <div class="table-responsive">
                                <table class="table table-hover table-striped" id="subscribers-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="25%">Nome</th>
                                            <th width="25%">E-mail</th>
                                            <th width="20%">Empresa</th>
                                            <th width="15%">Status</th>
                                            <th width="10%" class="actions-column">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($subscribers)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-3">
                                                    <i class="fas fa-search me-2"></i> Nenhum assinante encontrado.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($subscribers as $index => $subscriber): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($subscriber['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($subscriber['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($subscriber['company_name'] ?? '-'); ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $subscriber['status']; ?>">
                                                            <?php echo $subscriber['status'] == 'active' ? 'Ativo' : 'Cancelado'; ?>
                                                        </span>
                                                    </td>
                                                    <td class="actions-column">
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-sm btn-outline-secondary edit-subscriber" 
                                                                    data-bs-toggle="modal" data-bs-target="#editSubscriberModal"
                                                                    data-id="<?php echo $subscriber['id']; ?>"
                                                                    data-name="<?php echo htmlspecialchars($subscriber['name']); ?>"
                                                                    data-email="<?php echo htmlspecialchars($subscriber['email']); ?>"
                                                                    data-company="<?php echo $subscriber['company_id'] ?? ''; ?>"
                                                                    data-status="<?php echo $subscriber['status']; ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger delete-subscriber" 
                                                                    data-bs-toggle="modal" data-bs-target="#deleteSubscriberModal"
                                                                    data-id="<?php echo $subscriber['id']; ?>"
                                                                    data-name="<?php echo htmlspecialchars($subscriber['name']); ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Paginação -->
                            <?php if ($totalPages > 1): ?>
                                <nav aria-label="Navegação de páginas">
                                    <ul class="pagination justify-content-center mt-4">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" aria-label="Anterior">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                        
                                        <?php

// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}

                                        $startPage = max(1, $page - 2);
                                        $endPage = min($totalPages, $startPage + 4);
                                        
                                        if ($endPage - $startPage < 4) {
                                            $startPage = max(1, $endPage - 4);
                                        }
                                        
                                        for ($i = $startPage; $i <= $endPage; $i++):
                                        ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($endPage < $totalPages): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                                    <?php echo $totalPages; ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" aria-label="Próxima">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
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
    
    <!-- Modal de Adicionar Assinante -->
    <div class="modal fade" id="addSubscriberModal" tabindex="-1" aria-labelledby="addSubscriberModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSubscriberModalLabel">Novo Assinante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form action="/GED2.0/controllers/newsletter_controller.php" method="post">
                    <input type="hidden" name="acao" value="salvar_assinante">
                    <input type="hidden" name="id" value="0">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="company_id" class="form-label">Empresa (opcional)</label>
                            <select class="form-select" id="company_id" name="company_id">
                                <option value="">-- Selecione --</option>
                                <?php foreach ($empresas as $empresa): ?>
                                    <option value="<?php echo $empresa['id']; ?>">
                                        <?php echo htmlspecialchars($empresa['emp_name']); ?> (<?php echo $empresa['emp_cnpj']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="add_status" name="status">
                                <option value="active">Ativo</option>
                                <option value="unsubscribed">Cancelado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de Editar Assinante -->
    <div class="modal fade" id="editSubscriberModal" tabindex="-1" aria-labelledby="editSubscriberModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSubscriberModalLabel">Editar Assinante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form action="/GED2.0/controllers/newsletter_controller.php" method="post">
                    <input type="hidden" name="acao" value="salvar_assinante">
                    <input type="hidden" name="id" id="edit_id" value="">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Nome <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">E-mail <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_company_id" class="form-label">Empresa (opcional)</label>
                            <select class="form-select" id="edit_company_id" name="company_id">
                                <option value="">-- Selecione --</option>
                                <?php foreach ($empresas as $empresa): ?>
                                    <option value="<?php echo $empresa['id']; ?>">
                                        <?php echo htmlspecialchars($empresa['emp_name']); ?> (<?php echo $empresa['emp_cnpj']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="active">Ativo</option>
                                <option value="unsubscribed">Cancelado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de Exclusão -->
    <div class="modal fade" id="deleteSubscriberModal" tabindex="-1" aria-labelledby="deleteSubscriberModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteSubscriberModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o assinante <strong id="deleteSubscriberName"></strong>?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="/GED2.0/controllers/newsletter_controller.php" method="post" id="deleteForm">
                        <input type="hidden" name="acao" value="remover_assinante">
                        <input type="hidden" name="id" id="deleteSubscriberId">
                        <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
                    </form>
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
        $(document).ready(function() {
            // Inicializar DataTables
            $('#subscribers-table').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
                },
                paging: false,
                info: false,
                searching: false
            });
            
            // Preencher modal de edição
            $('.edit-subscriber').click(function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const email = $(this).data('email');
                const company = $(this).data('company');
                const status = $(this).data('status');
                
                $('#edit_id').val(id);
                $('#edit_name').val(name);
                $('#edit_email').val(email);
                $('#edit_company_id').val(company);
                $('#edit_status').val(status);
            });
            
            // Configurar modal de exclusão
            $('.delete-subscriber').click(function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                
                $('#deleteSubscriberId').val(id);
                $('#deleteSubscriberName').text(name);
            });
            
            // Submeter formulário quando mudar os selects
            $('#status').change(function() {
                $(this).closest('form').submit();
            });
        });
    </script>
</body>
</html>