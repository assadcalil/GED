<?php

// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}

/**
 * Sistema Contabilidade Estrela 2.0
 * Listagem de Newsletters
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
Logger::activity('acesso', 'Acessou a listagem de newsletters');

// Obter parâmetros de filtro e paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Obter newsletters
$result = NewsletterModel::getAll($page, $limit, $search, $status);
$newsletters = $result['data'];
$total = $result['total'];
$totalPages = $result['pages'];

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
    <title>Newsletters - <?php echo SITE_NAME; ?></title>
    
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
    <link rel="stylesheet" href="/GED2.0/assets/css/newsletter.css">
    
    <style>
        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-draft {
            background-color: #e9ecef;
            color: #495057;
        }
        
        .status-sent {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .card-newsletter {
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        
        .card-newsletter:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .newsletter-title {
            font-weight: 600;
            font-size: 18px;
            color: #0a4b78;
            margin-bottom: 5px;
        }
        
        .newsletter-subject {
            color: #495057;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .newsletter-date {
            font-size: 12px;
            color: #6c757d;
        }
        
        .newsletter-stats {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-weight: 600;
            font-size: 24px;
            color: #0a4b78;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
        }
        
        .btn-send-newsletter {
            background-color: #0a4b78;
            border-color: #0a4b78;
        }
        
        .btn-send-newsletter:hover {
            background-color: #083a5e;
            border-color: #083a5e;
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
                                <h1 class="page-title">Newsletters</h1>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="/GED2.0/views/dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Newsletters</li>
                                    </ol>
                                </nav>
                            </div>
                            <div class="col-auto">
                                <a href="/GED2.0/views/newsletter/create.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i> Nova Newsletter
                                </a>
                                <a href="/GED2.0/views/newsletter/subscribers.php" class="btn btn-outline-secondary ms-2">
                                    <i class="fas fa-users me-2"></i> Assinantes
                                </a>
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
                                <p class="mb-0">Informe seus clientes sobre a proximidade do prazo para declaração do IR. Crie uma newsletter específica para lembrar e oferecer seus serviços!</p>
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
                    
                    <!-- Filtros de busca -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form action="" method="get" class="row g-3">
                                <div class="col-md-6">
                                    <label for="search" class="form-label">Buscar</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" id="search" name="search" placeholder="Buscar por título ou assunto..." value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="" <?php echo $status == '' ? 'selected' : ''; ?>>Todos</option>
                                        <option value="draft" <?php echo $status == 'draft' ? 'selected' : ''; ?>>Rascunho</option>
                                        <option value="sent" <?php echo $status == 'sent' ? 'selected' : ''; ?>>Enviada</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-filter me-2"></i> Filtrar
                                    </button>
                                    <a href="/GED2.0/views/newsletter/list.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-eraser me-2"></i> Limpar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Lista de newsletters -->
                    <div class="row">
                        <?php if (empty($newsletters)): ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> Nenhuma newsletter encontrada.
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($newsletters as $newsletter): ?>
                                <?php 
                                // Obter estatísticas se a newsletter foi enviada
                                $stats = [];
                                if ($newsletter['status'] == 'sent') {
                                    $stats = NewsletterModel::getStats($newsletter['id']);
                                }
                                ?>
                                <div class="col-lg-6">
                                    <div class="card card-newsletter">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h3 class="newsletter-title"><?php echo htmlspecialchars($newsletter['title']); ?></h3>
                                                    <p class="newsletter-subject">
                                                        <i class="fas fa-envelope-open-text me-2"></i> 
                                                        <?php echo htmlspecialchars($newsletter['subject']); ?>
                                                    </p>
                                                </div>
                                                <span class="status-badge status-<?php echo $newsletter['status']; ?>">
                                                    <?php echo $newsletter['status'] == 'draft' ? 'Rascunho' : 'Enviada'; ?>
                                                </span>
                                            </div>
                                            
                                            <div class="newsletter-date">
                                                <small>
                                                    <i class="fas fa-calendar-alt me-1"></i> Criada em: <?php echo formatarData($newsletter['created_at']); ?>
                                                </small>
                                                
                                                <?php if ($newsletter['status'] == 'sent'): ?>
                                                <br>
                                                <small>
                                                    <i class="fas fa-paper-plane me-1"></i> Enviada em: <?php echo formatarData($newsletter['sent_at']); ?>
                                                </small>
                                                <?php elseif (!empty($newsletter['scheduled_for'])): ?>
                                                <br>
                                                <small>
                                                    <i class="fas fa-clock me-1"></i> Agendada para: <?php echo formatarData($newsletter['scheduled_for']); ?>
                                                </small>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($newsletter['status'] == 'sent' && !empty($stats)): ?>
                                            <!-- Estatísticas de envio -->
                                            <div class="newsletter-stats">
                                                <div class="row">
                                                    <div class="col-3 stat-item">
                                                        <div class="stat-value"><?php echo $newsletter['sent_count']; ?></div>
                                                        <div class="stat-label">Enviados</div>
                                                    </div>
                                                    <div class="col-3 stat-item">
                                                        <div class="stat-value"><?php echo $stats['opened']; ?></div>
                                                        <div class="stat-label">Abertos</div>
                                                    </div>
                                                    <div class="col-3 stat-item">
                                                        <div class="stat-value"><?php echo $stats['open_rate']; ?>%</div>
                                                        <div class="stat-label">Taxa</div>
                                                    </div>
                                                    <div class="col-3 stat-item">
                                                        <div class="stat-value"><?php echo $stats['clicked']; ?></div>
                                                        <div class="stat-label">Cliques</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <!-- Ações -->
                                            <div class="mt-3 d-flex justify-content-between">
                                                <div>
                                                    <a href="/GED2.0/views/newsletter/preview.php?id=<?php echo $newsletter['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye me-1"></i> Visualizar
                                                    </a>
                                                    
                                                    <?php if ($newsletter['status'] == 'draft'): ?>
                                                    <a href="/GED2.0/views/newsletter/edit.php?id=<?php echo $newsletter['id']; ?>" class="btn btn-sm btn-outline-secondary ms-1">
                                                        <i class="fas fa-edit me-1"></i> Editar
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div>
                                                    <?php if ($newsletter['status'] == 'draft'): ?>
                                                    <button type="button" class="btn btn-sm btn-primary btn-send-newsletter" 
                                                            data-bs-toggle="modal" data-bs-target="#sendModal" 
                                                            data-id="<?php echo $newsletter['id']; ?>"
                                                            data-title="<?php echo htmlspecialchars($newsletter['title']); ?>">
                                                        <i class="fas fa-paper-plane me-1"></i> Enviar
                                                    </button>
                                                    <?php endif; ?>
                                                    
                                                    <button type="button" class="btn btn-sm btn-outline-danger ms-1 delete-newsletter" 
                                                            data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                                            data-id="<?php echo $newsletter['id']; ?>"
                                                            data-title="<?php echo htmlspecialchars($newsletter['title']); ?>">
                                                        <i class="fas fa-trash me-1"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Paginação -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Navegação de páginas">
                        <ul class="pagination justify-content-center">
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
    
    <!-- Modal de Envio -->
    <div class="modal fade" id="sendModal" tabindex="-1" aria-labelledby="sendModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sendModalLabel">Enviar Newsletter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Você está prestes a enviar a newsletter <strong id="newsletterTitle"></strong> para todos os assinantes ativos.</p>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Após o envio, não será possível editar o conteúdo da newsletter.
                    </div>
                    
                    <p>Deseja enviar um teste antes?</p>
                    <div class="mb-3">
                        <label for="testEmail" class="form-label">E-mail para teste:</label>
                        <input type="email" class="form-control" id="testEmail" placeholder="seu@email.com">
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-send-test mb-3">
                        <i class="fas fa-vial me-1"></i> Enviar Teste
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="/GED2.0/controllers/newsletter_controller.php" method="post" id="sendForm">
                        <input type="hidden" name="acao" value="enviar">
                        <input type="hidden" name="id" id="newsletterId">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Confirmar Envio
                        </button>
                    </form>
                </div>
            </div>
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
                    <p>Tem certeza que deseja excluir a newsletter <strong id="deleteNewsletterTitle"></strong>?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="/GED2.0/controllers/newsletter_controller.php" method="post" id="deleteForm">
                        <input type="hidden" name="acao" value="remover">
                        <input type="hidden" name="id" id="deleteNewsletterId">
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
    
    <!-- Script personalizado -->
    <script src="/GED2.0/assets/js/dashboard.js"></script>
    
    <script>
        $(document).ready(function() {
            // Configurar modal de envio
            $('.btn-send-newsletter').click(function() {
                const id = $(this).data('id');
                const title = $(this).data('title');
                
                $('#newsletterId').val(id);
                $('#newsletterTitle').text(title);
            });
            
            // Configurar modal de exclusão
            $('.delete-newsletter').click(function() {
                const id = $(this).data('id');
                const title = $(this).data('title');
                
                $('#deleteNewsletterId').val(id);
                $('#deleteNewsletterTitle').text(title);
            });
            
            // Enviar e-mail de teste
            $('.btn-send-test').click(function() {
                const id = $('#newsletterId').val();
                const email = $('#testEmail').val();
                
                if (!email) {
                    alert('Por favor, informe um e-mail válido para o teste.');
                    return;
                }
                
                $.ajax({
                    url: '/GED2.0/controllers/newsletter_controller.php',
                    method: 'POST',
                    data: {
                        acao: 'enviar_teste',
                        id: id,
                        email: email
                    },
                    success: function(response) {
                        alert('E-mail de teste enviado com sucesso para ' + email);
                    },
                    error: function() {
                        alert('Erro ao enviar e-mail de teste.');
                    }
                });
            });
            
            // Submeter formulário quando mudar os selects
            $('#status').change(function() {
                $(this).closest('form').submit();
            });
        });
    </script>
</body>
</html>