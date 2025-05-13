<?php

// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}

/**
 * Sistema Contabilidade Estrela 2.0
 * Listagem de Certificados Digitais
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
Logger::activity('acesso', 'Acessou a listagem de certificados digitais');

// Obter parâmetros de filtro e paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$filtroEmpresa = isset($_GET['empresa']) ? $_GET['empresa'] : '';
$filtroSituacao = isset($_GET['situacao']) ? $_GET['situacao'] : '';

// Inicializar $params antes de usar
$params = [];

// Montar consulta SQL
$sql = "SELECT cd.certificado_id, e.emp_code, e.emp_name, 
                cd.certificado_emissao, 
                cd.certificado_validade, 
                cd.certificado_situacao 
         FROM empresas e
         INNER JOIN certificado_digital cd ON e.id = cd.empresa_id
         WHERE 1=1";
$countSql = "SELECT COUNT(*) FROM empresas e
             INNER JOIN certificado_digital cd ON e.id = cd.empresa_id
             WHERE 1=1";

// Aplicar filtros
if (!empty($search)) {
    $sql .= " AND (e.emp_name LIKE ? OR e.emp_code LIKE ?)";
    $countSql .= " AND (e.emp_name LIKE ? OR e.emp_code LIKE ?)";
    $termoBusca = "%{$search}%";
    $params[] = $termoBusca;
    $params[] = $termoBusca;
}

if (!empty($filtroEmpresa)) {
    $sql .= " AND e.id = ?";
    $countSql .= " AND e.id = ?";
    $params[] = $filtroEmpresa;
}

if (!empty($filtroSituacao)) {
    $sql .= " AND cd.certificado_situacao = ?";
    $countSql .= " AND cd.certificado_situacao = ?";
    $params[] = $filtroSituacao;
}

// Adicionar ordenação
$sql .= " ORDER BY cd.certificado_validade ASC LIMIT ? OFFSET ?";
$paramsWithPagination = $params;
$paramsWithPagination[] = $limit;
$paramsWithPagination[] = $offset;

// Executar consulta
$certificados = Database::select($sql, $paramsWithPagination);
$total = Database::selectOne($countSql, $params)['COUNT(*)'];

$totalPages = ceil($total / $limit);

// Arrays para campos de seleção
$situacoesCertificados = [
    '' => 'Todas',
    'VIGENTE' => 'Vigente',
    'VENCIDO' => 'Vencido',
    'PRESTES_A_VENCER' => 'Prestes a Vencer',
    'RENOVACAO_PENDENTE' => 'Renovação Pendente'
];

// Função para formatar data
function formatarData($data) {
    if (empty($data)) return '';
    
    $timestamp = strtotime($data);
    return date('d/m/Y', $timestamp);
}

// Função para calcular dias até vencimento
function calcularDiasVencimento($dataValidade) {
    if (empty($dataValidade)) return null;
    
    $hoje = new DateTime();
    $validade = new DateTime($dataValidade);
    $diferenca = $hoje->diff($validade);
    
    return $diferenca->invert ? -$diferenca->days : $diferenca->days;
}

// Função para determinar situação do certificado
function determinarSituacaoCertificado($dataValidade) {
    $diasVencimento = calcularDiasVencimento($dataValidade);
    
    if ($diasVencimento === null) return 'DESCONHECIDO';
    if ($diasVencimento < 0) return 'VENCIDO';
    if ($diasVencimento <= 30) return 'PRESTES_A_VENCER';
    return 'VIGENTE';
}

// Obter lista de empresas para filtro
$sqlEmpresas = "SELECT id, emp_code, emp_name FROM empresas ORDER BY emp_name ASC";
$empresas = Database::select($sqlEmpresas);

// Contar certificados por situação
$sqlVigentes = "SELECT COUNT(*) FROM certificado_digital WHERE certificado_situacao = 'VIGENTE'";
$sqlVencidos = "SELECT COUNT(*) FROM certificado_digital WHERE certificado_situacao = 'VENCIDO'";
$sqlPrestesAVencer = "SELECT COUNT(*) FROM certificado_digital WHERE certificado_situacao = 'PRESTES_A_VENCER'";
$sqlRenovacaoPendente = "SELECT COUNT(*) FROM certificado_digital WHERE certificado_situacao = 'RENOVACAO_PENDENTE'";

$vigentes = Database::selectOne($sqlVigentes)['COUNT(*)'];
$vencidos = Database::selectOne($sqlVencidos)['COUNT(*)'];
$prestesAVencer = Database::selectOne($sqlPrestesAVencer)['COUNT(*)'];
$renovacaoPendente = Database::selectOne($sqlRenovacaoPendente)['COUNT(*)'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- Cabeçalho do documento (meta tags, títulos, links CSS) - mantido igual ao anterior -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificados Digitais - <?php echo SITE_NAME; ?></title>
    
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
        /* Estilos mantidos iguais ao anterior */
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
            width: 120px;
            text-align: center;
        }
        
        .status-vigente {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-vencido {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .status-prestes_a_vencer {
            background-color: #fff3cd;
            color: #664d03;
        }
        
        .status-renovacao_pendente {
            background-color: #e2e3e5;
            color: #41464b;
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
                                <h1 class="page-title">Certificados Digitais</h1>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Certificados Digitais</li>
                                    </ol>
                                </nav>
                            </div>
                            <div class="col-auto">
                                <div class="d-flex gap-2">
                                    <a href="/ged2.0/views/certificates/create.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i> Novo Certificado
                                    </a>
                                    <a href="/ged2.0/views/certificates/enviar_email.php" class="btn btn-info text-white">
                                        <i class="fas fa-envelope me-2"></i> Enviar Certificado
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card de listagem -->
                    <div class="card">
                        <div class="card-body">
                            <!-- Filtros de busca -->
                            <form action="" method="get" class="mb-4">
                                <div class="row align-items-end">
                                    <div class="col-md-7">
                                        <label for="search" class="form-label">Buscar</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                            <input type="text" class="form-control" id="search" name="search" placeholder="Buscar por empresa, código..." value="<?php echo htmlspecialchars($search); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="situacao" class="form-label">Situação</label>
                                        <select class="form-select" id="situacao" name="situacao">
                                            <?php foreach ($situacoesCertificados as $key => $value): ?>
                                                <option value="<?php echo $key; ?>" <?php echo $filtroSituacao == $key ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($value); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-filter"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                            
                            <!-- Estatísticas -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="alert alert-info">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-info-circle me-2"></i> 
                                                Exibindo <?php echo count($certificados); ?> de <?php echo $total; ?> certificados.
                                            </div>
                                            <div>
                                                <span class="badge rounded-pill bg-light text-dark me-2">
                                                    <i class="fas fa-certificate me-1"></i> Total: <?php echo $total; ?>
                                                </span>
                                                <span class="badge rounded-pill bg-success me-2">
                                                    <i class="fas fa-check-circle me-1"></i> Vigentes: <?php echo $vigentes; ?>
                                                </span>
                                                <span class="badge rounded-pill bg-danger me-2">
                                                    <i class="fas fa-times-circle me-1"></i> Vencidos: <?php echo $vencidos; ?>
                                                </span>
                                                <span class="badge rounded-pill bg-warning me-2">
                                                    <i class="fas fa-exclamation-triangle me-1"></i> Prestes a Vencer: <?php echo $prestesAVencer; ?>
                                                </span>
                                                <span class="badge rounded-pill bg-secondary">
                                                    <i class="fas fa-sync me-1"></i> Renovação Pendente: <?php echo $renovacaoPendente; ?>
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
                                            <th>Código Empresa</th>
                                            <th>Razão Social</th>
                                            <th>Data Certificado</th>
                                            <th>Validade</th>
                                            <th>Situação</th>
                                            <th class="actions-column">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($certificados)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-3">
                                                    <i class="fas fa-search me-2"></i> Nenhum certificado encontrado.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($certificados as $certificado): 
                                                $situacao = determinarSituacaoCertificado($certificado['certificado_validade']);
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($certificado['emp_code']); ?></td>
                                                    <td><?php echo htmlspecialchars($certificado['emp_name']); ?></td>
                                                    <td><?php echo formatarData($certificado['certificado_emissao']); ?></td>
                                                    <td><?php echo formatarData($certificado['certificado_validade']); ?></td>
                                                    <td>
                                                        <span class="badge status-badge status-<?php echo strtolower($certificado['certificado_situacao']); ?>">
                                                            <?php 
                                                            switch($certificado['certificado_situacao']) {
                                                                case 'VIGENTE':
                                                                    echo 'Vigente';
                                                                    break;
                                                                case 'VENCIDO':
                                                                    echo 'Vencido';
                                                                    break;
                                                                case 'PRESTES_A_VENCER':
                                                                    echo 'Prestes a Vencer';
                                                                    break;
                                                                case 'RENOVACAO_PENDENTE':
                                                                    echo 'Renovação Pendente';
                                                                    break;
                                                                default:
                                                                    echo 'Desconhecido';
                                                            }
                                                            ?>
                                                        </span>
                                                    </td>
                                                    <td class="actions-column">
                                                        <div class="btn-group">
                                                            <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary view-certificado" 
                                                                data-id="<?php echo $certificado['certificado_id']; ?>" title="Visualizar">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="/ged2.0/views/certificates/edit.php?id=<?php echo $certificado['certificado_id']; ?>" class="btn btn-sm btn-outline-secondary" title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-outline-danger delete-certificado" 
                                                                data-id="<?php echo $certificado['certificado_id']; ?>" 
                                                                data-empresa="<?php echo htmlspecialchars($certificado['emp_name']); ?>" 
                                                                title="Excluir">
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
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&empresa=<?php echo urlencode($filtroEmpresa); ?>&situacao=<?php echo urlencode($filtroSituacao); ?>" aria-label="Anterior">
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
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&empresa=<?php echo urlencode($filtroEmpresa); ?>&situacao=<?php echo urlencode($filtroSituacao); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($endPage < $totalPages): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>&empresa=<?php echo urlencode($filtroEmpresa); ?>&situacao=<?php echo urlencode($filtroSituacao); ?>">
                                                    <?php echo $totalPages; ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&empresa=<?php echo urlencode($filtroEmpresa); ?>&situacao=<?php echo urlencode($filtroSituacao); ?>" aria-label="Próxima">
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
                    <p>Tem certeza que deseja excluir o certificado da empresa <strong id="empresaName"></strong>?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="/ged2.0/controllers/certificados_controller.php" method="post" id="deleteForm">
                        <input type="hidden" name="acao" value="remover">
                        <input type="hidden" name="id" id="certificadoId">
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
                    <h5 class="modal-title" id="viewModalLabel">Detalhes do Certificado Digital</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p>Carregando informações...</p>
                    </div>
                    <div id="certificado-detalhes" style="display:none;">
                        <!-- Os detalhes do certificado serão carregados aqui via AJAX -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <a href="#" id="editar-certificado-btn" class="btn btn-primary">
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
        $('.delete-certificado').click(function() {
            const id = $(this).data('id');
            const empresa = $(this).data('empresa');
            
            $('#certificadoId').val(id);
            $('#empresaName').text(empresa);
            
            $('#deleteModal').modal('show');
        });
        
        // Exportação para Excel
        $('#exportExcel').click(function(e) {
            e.preventDefault();
            window.location.href = '/ged2.0/controllers/certificados_controller.php?acao=exportar&formato=excel&search=<?php echo urlencode($search); ?>&empresa=<?php echo urlencode($filtroEmpresa); ?>&situacao=<?php echo urlencode($filtroSituacao); ?>';
        });
        
        // Exportação para PDF
        $('#exportPDF').click(function(e) {
            e.preventDefault();
            window.location.href = '/ged2.0/controllers/certificados_controller.php?acao=exportar&formato=pdf&search=<?php echo urlencode($search); ?>&empresa=<?php echo urlencode($filtroEmpresa); ?>&situacao=<?php echo urlencode($filtroSituacao); ?>';
        });
        
        // Exportação para CSV
        $('#exportCSV').click(function(e) {
            e.preventDefault();
            window.location.href = '/ged2.0/controllers/certificados_controller.php?acao=exportar&formato=csv&search=<?php echo urlencode($search); ?>&empresa=<?php echo urlencode($filtroEmpresa); ?>&situacao=<?php echo urlencode($filtroSituacao); ?>';
        });
        
        // Submeter formulário quando mudar os selects
        $('#empresa, #situacao').change(function() {
            $(this).closest('form').submit();
        });
    });
    </script>

    <!-- Script personalizado para visualização de certificados -->
    <script src="/GED2.0/assets/js/certificados-views.js"></script>
</body>
</html>