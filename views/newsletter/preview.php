<?php

// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}

/**
 * Sistema Contabilidade Estrela 2.0
 * Visualização de Newsletter
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

// Verificar ID da newsletter
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: /GED2.0/views/newsletter/list.php');
    exit;
}

// Obter dados da newsletter
$newsletter = NewsletterModel::getById($id);
if (!$newsletter) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Newsletter não encontrada.'
    ];
    header('Location: /GED2.0/views/newsletter/list.php');
    exit;
}

// Obter estatísticas se a newsletter foi enviada
$stats = [];
if ($newsletter['status'] == 'sent') {
    $stats = NewsletterModel::getStats($id);
}

// Registrar acesso
Logger::activity('acesso', 'Visualizou a newsletter #' . $id);

// Função para formatar data
function formatarData($data) {
    if (empty($data)) return '-';
    
    $timestamp = strtotime($data);
    return date('d/m/Y H:i', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Newsletter - <?php echo SITE_NAME; ?></title>
    
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
        .email-preview-frame {
            width: 100%;
            height: 600px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            background-color: #fff;
        }
        
        .newsletter-info {
            margin-bottom: 1.5rem;
        }
        
        .newsletter-info-item {
            margin-bottom: 0.5rem;
        }
        
        .newsletter-info-label {
            font-weight: 600;
            color: #0a4b78;
            width: 150px;
            display: inline-block;
        }
        
        .newsletter-stats {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #0a4b78;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .actions-card {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 20px;
        }
        
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
                                <h1 class="page-title">Visualizar Newsletter</h1>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="/GED2.0/views/dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="/GED2.0/views/newsletter/list.php">Newsletters</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Visualizar</li>
                                    </ol>
                                </nav>
                            </div>
                            <div class="col-auto">
                                <?php if ($newsletter['status'] == 'draft'): ?>
                                <a href="/GED2.0/views/newsletter/edit.php?id=<?php echo $newsletter['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i> Editar Newsletter
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Coluna de informações -->
                        <div class="col-lg-4">
                            <!-- Cartão de informações -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Informações da Newsletter</h5>
                                </div>
                                <div class="card-body">
                                    <div class="newsletter-info">
                                        <div class="newsletter-info-item">
                                            <span class="newsletter-info-label">Título:</span>
                                            <span><?php echo htmlspecialchars($newsletter['title']); ?></span>
                                        </div>
                                        <div class="newsletter-info-item">
                                            <span class="newsletter-info-label">Assunto:</span>
                                            <span><?php echo htmlspecialchars($newsletter['subject']); ?></span>
                                        </div>
                                        <div class="newsletter-info-item">
                                            <span class="newsletter-info-label">Status:</span>
                                            <span class="status-badge status-<?php echo $newsletter['status']; ?>">
                                                <?php echo $newsletter['status'] == 'draft' ? 'Rascunho' : 'Enviada'; ?>
                                            </span>
                                        </div>
                                        <div class="newsletter-info-item">
                                            <span class="newsletter-info-label">Criada em:</span>
                                            <span><?php echo formatarData($newsletter['created_at']); ?></span>
                                        </div>
                                        
                                        <?php if ($newsletter['status'] == 'sent'): ?>
                                        <div class="newsletter-info-item">
                                            <span class="newsletter-info-label">Enviada em:</span>
                                            <span><?php echo formatarData($newsletter['sent_at']); ?></span>
                                        </div>
                                        <?php elseif (!empty($newsletter['scheduled_for'])): ?>
                                        <div class="newsletter-info-item">
                                            <span class="newsletter-info-label">Agendada para:</span>
                                            <span><?php echo formatarData($newsletter['scheduled_for']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="newsletter-info-item">
                                            <span class="newsletter-info-label">Template:</span>
                                            <span><?php echo ucfirst($newsletter['template']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($newsletter['status'] == 'sent' && !empty($stats)): ?>
                                    <hr>
                                    
                                    <div class="my-3">
                                        <h6 class="mb-3">Estatísticas de Envio</h6>
                                        
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="stat-card">
                                                    <div class="stat-value"><?php echo $newsletter['sent_count']; ?></div>
                                                    <div class="stat-label">Enviados</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="stat-card">
                                                    <div class="stat-value"><?php echo $stats['opened']; ?></div>
                                                    <div class="stat-label">Abertos</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="stat-card">
                                                    <div class="stat-value"><?php echo $stats['open_rate']; ?>%</div>
                                                    <div class="stat-label">Taxa de Abertura</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="stat-card">
                                                    <div class="stat-value"><?php echo $stats['clicked']; ?></div>
                                                    <div class="stat-label">Cliques</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Cartão de ações -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Ações</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="/GED2.0/views/newsletter/list.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-2"></i> Voltar para Lista
                                        </a>
                                        
                                        <?php if ($newsletter['status'] == 'draft'): ?>
                                        <a href="/GED2.0/views/newsletter/edit.php?id=<?php echo $newsletter['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-edit me-2"></i> Editar Newsletter
                                        </a>
                                        
                                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#sendModal">
                                            <i class="fas fa-paper-plane me-2"></i> Enviar Newsletter
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-primary" id="btnSendTest">
                                            <i class="fas fa-vial me-2"></i> Enviar Cópia de Teste
                                        </button>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                            <i class="fas fa-trash me-2"></i> Excluir Newsletter
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Coluna de visualização -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Visualização da Newsletter</h5>
                                </div>
                                <div class="card-body p-0">
                                    <iframe id="previewFrame" class="email-preview-frame"></iframe>
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
    
    <!-- Modal de Envio -->
    <div class="modal fade" id="sendModal" tabindex="-1" aria-labelledby="sendModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sendModalLabel">Enviar Newsletter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Você está prestes a enviar a newsletter <strong><?php echo htmlspecialchars($newsletter['title']); ?></strong> para todos os assinantes ativos.</p>
                    
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
                    <form action="/GED2.0/controllers/newsletter_controller.php" method="post">
                        <input type="hidden" name="acao" value="enviar">
                        <input type="hidden" name="id" value="<?php echo $newsletter['id']; ?>">
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
                    <p>Tem certeza que deseja excluir a newsletter <strong><?php echo htmlspecialchars($newsletter['title']); ?></strong>?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="/GED2.0/controllers/newsletter_controller.php" method="post">
                        <input type="hidden" name="acao" value="remover">
                        <input type="hidden" name="id" value="<?php echo $newsletter['id']; ?>">
                        <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Teste -->
    <div class="modal fade" id="testModal" tabindex="-1" aria-labelledby="testModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testModalLabel">Enviar E-mail de Teste</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="testEmailCopy" class="form-label">E-mail para teste:</label>
                        <input type="email" class="form-control" id="testEmailCopy" placeholder="seu@email.com" required>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Um e-mail de teste será enviado para o endereço informado.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnSendTestConfirm">
                        <i class="fas fa-paper-plane me-2"></i> Enviar Teste
                    </button>
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
            // Carregar visualização da newsletter
            const newsletterContent = `<?php echo addslashes($newsletter['content']); ?>`;
            
            // Criar conteúdo para visualização
            const previewContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                        color: #333;
                        margin: 0;
                        padding: 0;
                        background-color: #f5f5f5;
                    }
                    
                    .container {
                        max-width: 650px;
                        margin: 0 auto;
                        background: #ffffff;
                        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    }
                    
                    .header {
                        background-color: #0a4b78;
                        color: white;
                        padding: 20px;
                        text-align: center;
                    }
                    
                    .logo {
                        max-width: 200px;
                        margin: 0 auto;
                    }
                    
                    .tax-alert {
                        background-color: #ff7e00;
                        color: white;
                        padding: 15px;
                        text-align: center;
                        font-weight: 600;
                    }
                    
                    .content {
                        padding: 25px;
                    }
                    
                    .footer {
                        background-color: #222;
                        color: #f1f1f1;
                        padding: 25px;
                        text-align: center;
                        font-size: 13px;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <!-- Cabeçalho -->
                    <div class="header">
                        <h2>Contabilidade Estrela</h2>
                    </div>
                    
                    <!-- Alerta tributário IR -->
                    <div class="tax-alert">
                        <p>📢 ÚLTIMA CHANCE: Imposto de Renda 2025 - O prazo termina em 31 de maio! Entre em contato conosco para garantir sua declaração sem complicações.</p>
                    </div>
                    
                    <!-- Conteúdo principal -->
                    <div class="content">
                        ${newsletterContent}
                    </div>
                    
                    <!-- Rodapé -->
                    <div class="footer">
                        <p><strong>Contabilidade Estrela</strong><br>
                        Rua das Estrelas, 123 - Centro<br>
                        São Paulo/SP - CEP 01234-567<br>
                        Tel: (11) 1234-5678</p>
                        
                        <p>Newsletter enviada em ${new Date().toLocaleDateString('pt-BR')}</p>
                    </div>
                </div>
            </body>
            </html>
            `;
            
            // Mostrar visualização no iframe
            const iframe = document.getElementById('previewFrame');
            iframe.contentWindow.document.open();
            iframe.contentWindow.document.write(previewContent);
            iframe.contentWindow.document.close();
            
            // Enviar e-mail de teste no modal
            $('.btn-send-test').click(function() {
                const id = <?php echo $newsletter['id']; ?>;
                const email = $('#testEmail').val();
                
                if (!email) {
                    alert('Por favor, informe um e-mail válido para o teste.');
                    return;
                }
                
                // Enviar requisição AJAX
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
            
            // Abrir modal de teste quando clicar no botão
            $('#btnSendTest').click(function() {
                $('#testModal').modal('show');
            });
            
            // Enviar e-mail de teste do botão confirmar
            $('#btnSendTestConfirm').click(function() {
                const id = <?php echo $newsletter['id']; ?>;
                const email = $('#testEmailCopy').val();
                
                if (!email) {
                    alert('Por favor, informe um e-mail válido para o teste.');
                    return;
                }
                
                // Enviar requisição AJAX
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
                        $('#testModal').modal('hide');
                    },
                    error: function() {
                        alert('Erro ao enviar e-mail de teste.');
                    }
                });
            });
        });
    </script>
</body>
</html>