<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Definir constante para a raiz do projeto
define('ROOT_DIR', __DIR__ . '/../../');

// Verificar se as configurações já foram incluídas
if (!defined('CONFIG_LOADED')) {
    require_once(ROOT_DIR . 'app/Config/App.php');
    require_once(ROOT_DIR . 'app/Config/Database.php');
    require_once(ROOT_DIR . 'app/Config/Auth.php');
    require_once(ROOT_DIR . 'app/Config/Logger.php');
    require_once(ROOT_DIR . 'app/Services/CalendarioFiscalService.php');
    define('CONFIG_LOADED', true);
}

// Verificar autenticação
Auth::requireLogin();

// Registrar acesso ao dashboard
Logger::activity('acesso', 'Visualizou o dashboard');

// Obter dados para o dashboard
$userId = Auth::getCurrentUserId();
$user = Auth::getCurrentUser();
$userType = Auth::getUserTypeName();

// Exemplo seguro para verificar se uma tabela existe antes de acessá-la
$stats['total_users'] = 0;
try {
    $stats['total_users'] = Database::selectOne("SELECT COUNT(*) as count FROM users WHERE active = 1")['count'];
} catch (Exception $e) {
    // Tabela users pode não existir ainda
}

// Estatísticas para o dashboard (variam conforme o tipo de usuário)
$stats = [];

if (Auth::isAdmin() || Auth::isUserType(Auth::EDITOR)) {
    // Estatísticas para administradores e editores
    $stats['total_users'] = Database::selectOne("SELECT COUNT(*) as count FROM users WHERE active = 1")['count'];
    //$stats['total_clients'] = Database::selectOne("SELECT COUNT(*) as count FROM users WHERE type = ? AND active = 1", [Auth::CLIENT])['count'];
    $stats['total_empresas'] = Database::selectOne("SELECT COUNT(*) as count FROM empresas WHERE emp_sit_cad = 'ATIVA' LIMIT 0, 25")['count'];
    $stats['total_certificado_vigente'] = Database::selectOne("SELECT COUNT(*) as count FROM certificado_digital WHERE certificado_situacao = 'VIGENTE' LIMIT 0, 25")['count'];
    $stats['total_certificado_vencido'] = Database::selectOne("SELECT COUNT(*) as count FROM certificado_digital WHERE certificado_situacao = 'VENCIDO' LIMIT 0, 25")['count'];
    //$stats['total_documents'] = Database::selectOne("SELECT COUNT(*) as count FROM documents")['count'];
    $stats['recent_activities'] = Database::select("SELECT * FROM users WHERE active = 1 ORDER BY created_at DESC LIMIT 10");
} elseif (Auth::isUserType(Auth::TAX)) {
    // Estatísticas para usuários de imposto de renda
    //$stats['total_tax_documents'] = Database::selectOne("SELECT COUNT(*) as count FROM tax_documents")['count'];
    //$stats['pending_tax_documents'] = Database::selectOne("SELECT COUNT(*) as count FROM tax_documents WHERE status = 'pending'")['count'];
    //$stats['recent_tax_activities'] = Database::select("SELECT * FROM tax_activities ORDER BY created_at DESC LIMIT 10");
} elseif (Auth::isUserType(Auth::FINANCIAL)) {
    // Estatísticas para usuários financeiros
    //$stats['total_invoices'] = Database::selectOne("SELECT COUNT(*) as count FROM invoices")['count'];
    //$stats['pending_payments'] = Database::selectOne("SELECT COUNT(*) as count FROM invoices WHERE status = 'pending'")['count'];
    //$stats['total_amount'] = Database::selectOne("SELECT SUM(amount) as total FROM invoices")['total'] ?? 0;
} elseif (Auth::isUserType(Auth::CLIENT)) {
    // Estatísticas para clientes
    //$stats['total_companies'] = Database::selectOne("SELECT COUNT(*) as count FROM companies WHERE id = ?", [$userId])['count'];
    //$stats['total_documents'] = Database::selectOne("SELECT COUNT(*) as count FROM documents WHERE id = ?", [$userId])['count'];
    //$stats['pending_documents'] = Database::selectOne("SELECT COUNT(*) as count FROM documents WHERE id = ? AND status = 'pending'", [$userId])['count'];
}

// Obter mês e ano da URL ou usar o mês atual
$month = isset($_GET['month']) ? intval($_GET['month']) : null;
$year = isset($_GET['year']) ? intval($_GET['year']) : null;

// Se o mês for inválido, usar mês atual
if ($month < 1 || $month > 12) {
    $month = null;
}

// Criar e renderizar o calendário
$calendar = new FiscalCalendar($month, $year);

// Obter hora atual em Brasília
$brasiliaTime = Config::getCurrentBrasiliaHour();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    
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
<body>
    <div class="dashboard-container">
        <!-- Menu Lateral -->
        <?php include_once __DIR__ . '/../partials/sidebar.php'; ?>
        
        <!-- Conteúdo Principal -->
        <div class="main-content">
            <!-- Cabeçalho -->
            <?php include_once ROOT_PATH . '/views/partials/header.php'; ?>
            
            <!-- Conteúdo do Dashboard -->
            <div class="dashboard-content">
                <div class="container-fluid">
                    <div class="page-header">
                        <h1 class="page-title">Dashboard</h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                            </ol>
                        </nav>
                    </div>
                    
                    <!-- Cartões de Estatísticas -->
                    <div class="row stats-cards">
                        <?php if (Auth::isAdmin() || Auth::isUserType(Auth::EDITOR)): ?>
                            <!-- Estatísticas para administradores e editores -->
                            <div class="col-md-6 col-lg-3">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-icon">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="stat-details">
                                            <h3 class="stat-number"><?php echo $stats['total_users']; ?></h3>
                                            <span class="stat-label">Usuários</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 col-lg-3">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-icon">
                                            <i class="fas fa-user-tie"></i>
                                        </div>
                                        <div class="stat-details">
                                        <h3 class="stat-number"><?php echo $stats['total_empresas']; ?></h3>
                                            <span class="stat-label">Empresas</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 col-lg-3">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-icon">
                                            <i class="fas fa-file-alt "></i>
                                        </div>
                                        <div class="stat-details">
                                            <h3 class="stat-number">
                                            <h3 class="stat-number"><?php echo $stats['total_certificado_vigente']; ?></h3>
                                            <span class="stat-label">Certificado VIGENTE</span>
                                        </div>
                                        <div class="stat-details">
                                            <h3 class="stat-number">
                                            <h3 class="stat-number"><?php echo $stats['total_certificado_vencido']; ?></h3>
                                            <span class="stat-label">Certificado VENCIDO</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 col-lg-3">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-icon">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <div class="stat-details">
                                            <h3 class="stat-number">
                                            <span class="stat-label">Documentos</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php elseif (Auth::isUserType(Auth::TAX)): ?>
                            <!-- Estatísticas para usuários de imposto de renda -->
                            <div class="col-md-6">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-icon">
                                            <i class="fas fa-file-invoice-dollar"></i>
                                        </div>
                                        <div class="stat-details">
                                            <h3 class="stat-number"><?php echo $stats['total_tax_documents']; ?></h3>
                                            <span class="stat-label">Documentos Fiscais</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-icon">
                                            <i class="fas fa-hourglass-half"></i>
                                        </div>
                                        <div class="stat-details">
                                            <h3 class="stat-number"><!--<?php echo $stats['pending_tax_documents']; ?></h3>-->
                                            <span class="stat-label">Documentos Pendentes</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php elseif (Auth::isUserType(Auth::FINANCIAL)): ?>
                            <!-- Estatísticas para usuários financeiros -->
                            <div class="col-md-4">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-icon">
                                            <i class="fas fa-file-invoice"></i>
                                        </div>
                                        <div class="stat-details">
                                            <h3 class="stat-number"><?php echo $stats['total_invoices']; ?></h3>
                                            <span class="stat-label">Faturas</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="stat-details">
                                            <h3 class="stat-number"><?php echo $stats['pending_payments']; ?></h3>
                                            <span class="stat-label">Pagamentos Pendentes</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-icon">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                        <div class="stat-details">
                                            <h3 class="stat-number"><?php echo Config::formatMoney($stats['total_amount']); ?></h3>
                                            <span class="stat-label">Valor Total</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php elseif (Auth::isUserType(Auth::CLIENT)): ?>
                            <!-- Estatísticas para clientes -->
                            <div class="col-md-4">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-icon">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <div class="stat-details">
                                            
                                            <span class="stat-label">Minhas Empresas</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-icon">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                        <div class="stat-details">
                                            <h3 class="stat-number"><?php echo $stats['total_documents']; ?></h3>
                                            <span class="stat-label">Documentos Totais</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-icon">
                                            <i class="fas fa-hourglass-half"></i>
                                        </div>
                                        <div class="stat-details">
                                            <h3 class="stat-number"><?php echo $stats['pending_documents']; ?></h3>
                                            <span class="stat-label">Documentos Pendentes</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row mt-4 justify-content-center">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-body p-0">
                                    <!-- Widget de Notícias Contábeis -->
                                    <?php include_once __DIR__ . '/../partials/news_widget.php'; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Calendário de Obrigações Fiscais -->
                    <div class="row mt-4 justify-content-center">
                        <div class="col-md-5">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Calendário de Obrigações Fiscais e Contábeis</h5>
                                </div>
                                <div class="card-body p-0">
                                    <?php echo $calendar->render(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Atividades Recentes -->
                    <!-- Localizar a seção "Atividades Recentes" no arquivo index.php do Dashboard (aproximadamente linha 279) -->
                    <!-- Substituir o conteúdo atual por: -->

                    <!-- Atividades Recentes e Notícias -->
                    <div class="row mt-4 justify-content-center">
                        <div class="col-md-10">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Últimas Atividades</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Usuário</th>
                                                    <th>Ação</th>
                                                    <th>Data/Hora</th>
                                                    <th>Detalhes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (isset($stats['recent_activities']) && !empty($stats['recent_activities'])): ?>
                                                    <?php foreach ($stats['recent_activities'] as $activity): ?>
                                                        <tr>
                                                            <td><?php echo $activity['name']; ?></td>
                                                            <td><?php echo $activity['type']; ?></td>
                                                            <td><?php echo Config::formatDate($activity['created_at'], 'd/m/Y H:i'); ?></td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-info">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">Nenhuma atividade recente encontrada.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
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
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Script personalizado -->
    <script src="/GED2.0/assets/js/dashboard.js"></script>

    <!-- Incluir arquivos CSS e JS necessários -->
    <link rel="stylesheet" href="/GED2.0/assets/css/calendar.css">
    <script src="/GED2.0/assets/js/calendar.js"></script>
    
    <!-- Script para Relógio de Brasília -->
    <script>
        // Atualizar relógio a cada segundo
        function updateClock() {
            const now = new Date();
            // Ajustar para horário de Brasília (UTC-3)
            const brasiliaTime = new Date(now.getTime() - (now.getTimezoneOffset() + 180) * 60000);
            
            const hours = String(brasiliaTime.getHours()).padStart(2, '0');
            const minutes = String(brasiliaTime.getMinutes()).padStart(2, '0');
            const seconds = String(brasiliaTime.getSeconds()).padStart(2, '0');
            
            document.getElementById('brasilia-clock').textContent = `${hours}:${minutes}:${seconds}`;
        }
        
        // Chamar imediatamente e depois a cada segundo
        updateClock();
        setInterval(updateClock, 1000);
        
        // Inicializar gráficos de exemplo
        document.addEventListener('DOMContentLoaded', function() {
            // Gráfico de atividades
            const activityCtx = document.getElementById('activityChart').getContext('2d');
            const activityChart = new Chart(activityCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul'],
                    datasets: [{
                        label: 'Documentos',
                        data: [65, 59, 80, 81, 56, 55, 40],
                        borderColor: 'rgba(0, 123, 255, 1)',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Acessos',
                        data: [28, 48, 40, 19, 86, 27, 90],
                        borderColor: 'rgba(40, 167, 69, 1)',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Gráfico de documentos
            const documentsCtx = document.getElementById('documentsChart').getContext('2d');
            const documentsChart = new Chart(documentsCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Contratos', 'Notas Fiscais', 'Relatórios', 'Certificados'],
                    datasets: [{
                        data: [30, 40, 20, 10],
                        backgroundColor: [
                            'rgba(0, 123, 255, 0.8)',
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(255, 193, 7, 0.8)',
                            'rgba(220, 53, 69, 0.8)'
                        ],
                        borderColor: [
                            'rgba(0, 123, 255, 1)',
                            'rgba(40, 167, 69, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(220, 53, 69, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>