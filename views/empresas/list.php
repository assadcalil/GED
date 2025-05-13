<?php

// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}

/**
 * Sistema Contabilidade Estrela 2.0
 * Listagem de Empresas
 */

// Verificar se as configurações já foram incluídas
if (!defined('ROOT_DIR')) {
    require_once __DIR__ . '/../../../...../app/Config/App.php';
    require_once __DIR__ . '/../../../...../app/Config/Database.php';
    require_once __DIR__ . '/../../../...../app/Config/Auth.php';
    require_once __DIR__ . '/../../../...../app/Config/Logger.php';
}

// Verificar autenticação
Auth::requireLogin();

// Registrar acesso
Logger::activity('acesso', 'Acessou a listagem de empresas');

// Obter parâmetros de filtro e paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$filtroTipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtroSituacao = isset($_GET['situacao']) ? $_GET['situacao'] : '';
$filtroUf = isset($_GET['uf']) ? $_GET['uf'] : '';
$nome = isset($_GET['name']) ? $_GET['name'] : '';

// Montar consulta SQL
$sql = "SELECT id, emp_code, emp_name, emp_cnpj, emp_sit_cad, name, emp_porte, emp_tipo_jur, emp_cid, emp_uf, data 
         FROM empresas WHERE 1=1";
$countSql = "SELECT COUNT(*) FROM empresas WHERE 1=1";
$params = [];

// Aplicar filtros
if (!empty($search)) {
    $sql .= " AND (emp_name LIKE ? OR emp_cnpj LIKE ? OR emp_code LIKE ? OR name LIKE ?)";
    $countSql .= " AND (emp_name LIKE ? OR emp_cnpj LIKE ? OR emp_code LIKE ? OR name LIKE ?)";
    $termoBusca = "%{$search}%";
    $params[] = $termoBusca;
    $params[] = $termoBusca;
    $params[] = $termoBusca;
    $params[] = $termoBusca; // Adicionar mais um parâmetro para o name
}

if (!empty($filtroTipo)) {
    $sql .= " AND emp_tipo_jur = ?";
    $countSql .= " AND emp_tipo_jur = ?";
    $params[] = $filtroTipo;
}

if (!empty($filtroSituacao)) {
    $sql .= " AND emp_sit_cad = ?";
    $countSql .= " AND emp_sit_cad = ?";
    $params[] = $filtroSituacao;
}

if (!empty($filtroUf)) {
    $sql .= " AND emp_uf = ?";
    $countSql .= " AND emp_uf = ?";
    $params[] = $filtroUf;
}

// Adicionar ordenação
$sql .= " ORDER BY CAST(emp_code AS UNSIGNED) ASC LIMIT ? OFFSET ?";
$paramsWithPagination = $params;
$paramsWithPagination[] = $limit;
$paramsWithPagination[] = $offset;

// Executar consulta
$empresas = Database::select($sql, $paramsWithPagination);
$total = Database::selectOne($countSql, $params)['COUNT(*)'];

$totalPages = ceil($total / $limit);

// Arrays para campos de seleção
$situacoesCadastrais = [
    '' => 'Todas',
    'ATIVA' => 'Ativa',
    'INATIVA' => 'Inativa',
    'SUSPENSA' => 'Suspensa',
    'CANCELADA' => 'Cancelada',
    'RETIRADA' => 'Retirada',
    'DISPENSADA' => 'Dispensada',
    'PARADA' => 'Parada'
];

$tiposJuridicos = [
    '' => 'Todos',
    'EI' => 'Empresário Individual',
    'EIRELI' => 'Empresa Individual de Responsabilidade Limitada',
    'LTDA' => 'Sociedade Limitada',
    'SA' => 'Sociedade Anônima',
    'SLU' => 'Sociedade Limitada Unipessoal',
    'OUTROS' => 'Outros'
];

$ufs = [
    '' => 'Todos',
    'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
    'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
    'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
    'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
    'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
    'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
    'SE' => 'Sergipe', 'TO' => 'Tocantins'
];

// Função para formatar CNPJ
function formatarCnpj($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    if (strlen($cnpj) != 14) {
        return $cnpj;
    }
    
    return substr($cnpj, 0, 2) . '.' . 
           substr($cnpj, 2, 3) . '.' . 
           substr($cnpj, 5, 3) . '/' . 
           substr($cnpj, 8, 4) . '-' . 
           substr($cnpj, 12, 2);
}

// Função para obter o nome da situação cadastral
function getSituacaoCadastral($situacao) {
    $situacoes = [
        'ATIVA' => 'Ativa',
        'INATIVA' => 'Inativa',
        'SUSPENSA' => 'Suspensa',
        'CANCELADA' => 'Cancelada',
        'RETIRADA' => 'Retirada',
        'DISPENSADA' => 'Dispensada',
        'PARADA' => 'Parada'
    ];
    
    return isset($situacoes[$situacao]) ? $situacoes[$situacao] : $situacao;
}

// Função para obter o tipo jurídico
function getTipoJuridico($tipo) {
    $tipos = [
        'EI' => 'Empresário Individual',
        'EIRELI' => 'EIRELI',
        'LTDA' => 'Ltda',
        'SA' => 'S.A.',
        'SLU' => 'SLU',
        'OUTROS' => 'Outros'
    ];
    
    return isset($tipos[$tipo]) ? $tipos[$tipo] : $tipo;
}

// Função para formatar data
function formatarData($data) {
    if (empty($data)) return '';
    
    $timestamp = strtotime($data);
    return date('d/m/Y', $timestamp);
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listagem de Empresas - <?php echo SITE_NAME; ?></title>
    
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
        .table-responsive {
            overflow-x: auto;
        }
        
        .table th, .table td {
            vertical-align: middle;
        }
        
        .actions-column {
            width: 120px;
        }
        
        .status-badge {
            width: 85px;
            text-align: center;
        }
        
        .status-ativa {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-inativa {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .status-suspensa {
            background-color: #fff3cd;
            color: #664d03;
        }

        .status-cancelada {
            background-color: #343a40;
            color: #ffffff;
        }

        .status-retirada {
            background-color: #ffc107;
            color: #000000;
        }

        .status-dispensada {
            background-color: #6c757d;
            color: #ffffff;
        }

        .status-parada {
            background-color: #0dcaf0;
            color: #000000;
        }
        /* Adicione estas classes ao bloco de estilo existente (por volta da linha 281) */
        .search-box {
            position: relative;
        }
        
        .search-icon {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .search-input {
            padding-left: 30px;
        }
        
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
        
        .filter-buttons {
            margin-top: 10px;
        }
        
        .export-button {
            margin-left: 10px;
        }

        /* Adicione ao seu bloco de estilo */
        .modal-content.bg-opacity-95 {
            background-color: rgba(255, 255, 255, 0.95) !important;
        }

        .modal-backdrop.show {
            opacity: 0.6;
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
                                <h1 class="page-title">Empresas</h1>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Empresas</li>
                                    </ol>
                                </nav>
                            </div>
                            <div class="col-auto">
                                <a href="/ged2.0/views/empresas/create.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i> Nova Empresa
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card de listagem -->
                    <div class="card">
                        <div class="card-body">
                            <!-- Filtros de busca -->
                            <form action="" method="get" class="mb-4">
                                <div class="row align-items-end">
                                    <div class="col-md-5">
                                        <label for="search" class="form-label">Buscar</label>
                                        <div class="search-box">
                                            <i class="fas fa-search search-icon"></i>
                                            <input type="text" class="form-control search-input" id="search" name="search" placeholder="Buscar por nome, CNPJ ou código..." value="<?php echo htmlspecialchars($search); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="situacao" class="form-label">Situação</label>
                                        <select class="form-select" id="situacao" name="situacao">
                                            <?php foreach ($situacoesCadastrais as $key => $value): ?>
                                                <option value="<?php echo $key; ?>" <?php echo $filtroSituacao == $key ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($value); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-1 filter-buttons">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-filter"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Botões de filtros -->
                                <div class="row mt-2">
                                    <div class="col-12 text-end">
                                        <a href="/ged2.0/views/empresas/list.php" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-eraser me-1"></i> Limpar Filtros
                                        </a>
                                        <div class="btn-group export-button">
                                            <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-download me-1"></i> Exportar
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" id="exportExcel"><i class="fas fa-file-excel me-2"></i> Excel</a></li>
                                                <li><a class="dropdown-item" href="#" id="exportPDF"><i class="fas fa-file-pdf me-2"></i> PDF</a></li>
                                                <li><a class="dropdown-item" href="#" id="exportCSV"><i class="fas fa-file-csv me-2"></i> CSV</a></li>
                                            </ul>
                                        </div>
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
                                                Exibindo <?php echo count($empresas); ?> de <?php echo $total; ?> empresas.
                                            </div>
                                            <div>
                                                <span class="badge rounded-pill bg-light text-dark me-2">
                                                    <i class="fas fa-building me-1"></i> Total: <?php echo $total; ?>
                                                </span>
                                                <?php 
                                                    // Contar empresas por situação
                                                    $sqlAtivas = "SELECT COUNT(*) FROM empresas WHERE emp_sit_cad = 'ATIVA'";
                                                    $sqlInativas = "SELECT COUNT(*) FROM empresas WHERE emp_sit_cad = 'INATIVA'";
                                                    $sqlCancelada = "SELECT COUNT(*) FROM empresas WHERE emp_sit_cad = 'CANCELADA'";
                                                    $sqlRetirada = "SELECT COUNT(*) FROM empresas WHERE emp_sit_cad = 'RETIRADA'";
                                                    $sqlDispensada = "SELECT COUNT(*) FROM empresas WHERE emp_sit_cad = 'DISPENSADA'";
                                                    $sqlParada = "SELECT COUNT(*) FROM empresas WHERE emp_sit_cad = 'PARADA'";

                                                        $ativas = Database::selectOne($sqlAtivas)['COUNT(*)'];
                                                        $inativas = Database::selectOne($sqlInativas)['COUNT(*)'];
                                                        $cancelada = Database::selectOne($sqlCancelada)['COUNT(*)'];
                                                        $retirada = Database::selectOne($sqlRetirada)['COUNT(*)'];
                                                        $dispensada = Database::selectOne($sqlDispensada)['COUNT(*)'];
                                                        $parada = Database::selectOne($sqlParada)['COUNT(*)'];
                                                ?>
                                                <span class="badge rounded-pill bg-success me-2">
                                                    <i class="fas fa-check-circle me-1"></i> Ativas: <?php echo $ativas; ?>
                                                </span>
                                                <span class="badge rounded-pill bg-danger">
                                                    <i class="fas fa-times-circle me-1"></i> Inativas: <?php echo $inativas; ?>
                                                </span>
                                                <span class="badge rounded-pill bg-dark me-2">
                                                    <i class="fas fa-check-circle me-1"></i> Cancelada: <?php echo $cancelada; ?>
                                                </span>
                                                <span class="badge rounded-pill bg-warning me-2">
                                                    <i class="fas fa-check-circle me-1"></i> Retirada: <?php echo $retirada; ?>
                                                </span>
                                                <span class="badge rounded-pill bg-secondary me-2">
                                                    <i class="fas fa-check-circle me-1"></i> Dispensada: <?php echo $dispensada; ?>
                                                </span>
                                                <span class="badge rounded-pill bg-info me-2">
                                                    <i class="fas fa-check-circle me-1"></i> Parada: <?php echo $parada; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tabela de resultados -->
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Código</th>
                                            <th>Razão Social</th>
                                            <th>CNPJ</th>
                                            <th>Responsavel</th>
                                            <th>Situação</th>
                                            <th class="actions-column">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($empresas)): ?>
                                            <tr>
                                                <td colspan="9" class="text-center py-3">
                                                    <i class="fas fa-search me-2"></i> Nenhuma empresa encontrada.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($empresas as $empresa): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($empresa['emp_code']); ?></td>
                                                    <td><?php echo htmlspecialchars($empresa['emp_name']); ?></td>
                                                    <td><?php echo formatarCnpj($empresa['emp_cnpj']); ?></td>
                                                    <td><?php echo htmlspecialchars($empresa['name']); ?></td>
                                                    <td>
                                                        <span class="badge status-badge status-<?php echo strtolower($empresa['emp_sit_cad']); ?>">
                                                            <?php echo getSituacaoCadastral($empresa['emp_sit_cad']); ?>
                                                        </span>
                                                    </td>
                                
                                                    <td class="actions-column">
                                                        <div class="btn-group">
                                                            <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary view-empresa" 
                                                                data-id="<?php echo $empresa['id']; ?>" title="Visualizar">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="/ged2.0/views/empresas/edit.php?id=<?php echo $empresa['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="/GED2.0/views/empresas/documentos.php?id=<?php echo $empresa['id']; ?>" class="btn btn-sm btn-outline-success" title="Documentos">
                                                                <i class="fas fa-folder"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-outline-danger delete-empresa" data-id="<?php echo $empresa['id']; ?>" data-name="<?php echo htmlspecialchars($empresa['emp_name']); ?>" title="Excluir">
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
                                    <ul class="pagination">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&tipo=<?php echo urlencode($filtroTipo); ?>&situacao=<?php echo urlencode($filtroSituacao); ?>&uf=<?php echo urlencode($filtroUf); ?>" aria-label="Anterior">
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
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&tipo=<?php echo urlencode($filtroTipo); ?>&situacao=<?php echo urlencode($filtroSituacao); ?>&uf=<?php echo urlencode($filtroUf); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($endPage < $totalPages): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>&tipo=<?php echo urlencode($filtroTipo); ?>&situacao=<?php echo urlencode($filtroSituacao); ?>&uf=<?php echo urlencode($filtroUf); ?>">
                                                    <?php echo $totalPages; ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&tipo=<?php echo urlencode($filtroTipo); ?>&situacao=<?php echo urlencode($filtroSituacao); ?>&uf=<?php echo urlencode($filtroUf); ?>" aria-label="Próxima">
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
    
    <!-- Modal de Exclusão -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir a empresa <strong id="empresaName"></strong>?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Esta ação não pode ser desfeita e todos os documentos relacionados serão removidos.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="/ged2.0/controllers/empresas_controller.php" method="post" id="deleteForm">
                        <input type="hidden" name="acao" value="remover">
                        <input type="hidden" name="id" id="empresaId">
                        <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Visualização -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-white bg-opacity-95">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalLabel">Detalhes da Empresa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p>Carregando informações...</p>
                    </div>
                    <div id="empresa-detalhes" style="display:none;">
                        <!-- Os detalhes da empresa serão carregados aqui via AJAX -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <a href="#" id="editar-empresa-btn" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> Editar
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap Bundle com Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script personalizado -->
    <script src="/GED2.0/assets/js/dashboard.js"></script>
    
    <script>
        $(document).ready(function() {
            // Configurar modal de exclusão
            $('.delete-empresa').click(function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                
                $('#empresaId').val(id);
                $('#empresaName').text(name);
                
                $('#deleteModal').modal('show');
            });
            
            // Exportação para Excel
            $('#exportExcel').click(function(e) {
                e.preventDefault();
                window.location.href = '/ged2.0/controllers/empresas_controller.php?acao=exportar&formato=excel&search=<?php echo urlencode($search); ?>&tipo=<?php echo urlencode($filtroTipo); ?>&situacao=<?php echo urlencode($filtroSituacao); ?>&uf=<?php echo urlencode($filtroUf); ?>';
            });
            
            // Exportação para PDF
            $('#exportPDF').click(function(e) {
                e.preventDefault();
                window.location.href = '/ged2.0/controllers/empresas_controller.php?acao=exportar&formato=pdf&search=<?php echo urlencode($search); ?>&tipo=<?php echo urlencode($filtroTipo); ?>&situacao=<?php echo urlencode($filtroSituacao); ?>&uf=<?php echo urlencode($filtroUf); ?>';
            });
            
            // Exportação para CSV
            $('#exportCSV').click(function(e) {
                e.preventDefault();
                window.location.href = '/ged2.0/controllers/empresas_controller.php?acao=exportar&formato=csv&search=<?php echo urlencode($search); ?>&tipo=<?php echo urlencode($filtroTipo); ?>&situacao=<?php echo urlencode($filtroSituacao); ?>&uf=<?php echo urlencode($filtroUf); ?>';
            });
            
            // Submeter formulário quando mudar os selects
            $('#tipo, #situacao, #uf').change(function() {
                $(this).closest('form').submit();
            });
        });
    </script>

    <!-- Script personalizado para visualização de empresas -->
    <script src="/GED2.0/assets/js/empresas-views.js"></script>
</body>
</html>