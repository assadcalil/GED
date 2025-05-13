<?php

// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}

/**
 * Sistema Contabilidade Estrela 2.0
 * Editar Newsletter
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

// Verificar se já foi enviada
if ($newsletter['status'] == 'sent') {
    $_SESSION['flash_message'] = [
        'type' => 'warning',
        'message' => 'Não é possível editar uma newsletter que já foi enviada.'
    ];
    header('Location: /GED2.0/views/newsletter/list.php');
    exit;
}

// Registrar acesso
Logger::activity('acesso', 'Acessou o formulário de edição da newsletter #' . $id);

// Obter mensagem flash
function getFlashMessage() {
    $message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : null;
    unset($_SESSION['flash_message']);
    return $message;
}

// Formatar data para o formato do campo de agendamento
function formatarDataAgendamento($data) {
    if (empty($data)) return '';
    
    $timestamp = strtotime($data);
    return date('d/m/Y H:i', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Newsletter - <?php echo SITE_NAME; ?></title>
    
    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Summernote Editor -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    
    <!-- Tempus Dominus Datetime Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.7.13/dist/css/tempus-dominus.min.css">
    
    <!-- Estilo personalizado -->
    <link rel="stylesheet" href="/GED2.0/assets/css/dashboard.css">
    <link rel="stylesheet" href="/GED2.0/assets/css/newsletter.css">
    
    <style>
        .preview-newsletter {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 20px;
            margin-top: 20px;
            background-color: #f8f9fa;
        }
        
        #summernote {
            min-height: 300px;
        }
        
        .email-preview-frame {
            width: 100%;
            height: 500px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        
        .note-editing-area {
            min-height: 300px;
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
        
        /* Estilos para os botões de modelos */
        .template-button {
            border: 2px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .template-button:hover {
            border-color: #0a4b78;
            transform: translateY(-3px);
        }
        
        .template-button.active {
            border-color: #0a4b78;
            background-color: rgba(10, 75, 120, 0.1);
        }
        
        .template-thumbnail {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 3px;
            margin-bottom: 10px;
        }
        
        .template-title {
            font-weight: 600;
            font-size: 14px;
            text-align: center;
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
                                <h1 class="page-title">Editar Newsletter</h1>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="/GED2.0/views/dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="/GED2.0/views/newsletter/list.php">Newsletters</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Editar Newsletter</li>
                                    </ol>
                                </nav>
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
                                <p class="mb-0">Aproveite para lembrar seus clientes sobre o prazo final do Imposto de Renda e oferecer seus serviços!</p>
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
                    
                    <!-- Card de formulário -->
                    <div class="card">
                        <div class="card-body">
                            <form action="/GED2.0/controllers/newsletter_controller.php" method="post" id="newsletterForm">
                                <input type="hidden" name="acao" value="salvar">
                                <input type="hidden" name="id" value="<?php echo $newsletter['id']; ?>">
                                <input type="hidden" name="template" id="template" value="<?php echo $newsletter['template']; ?>">
                                
                                <div class="row">
                                    <!-- Coluna do formulário -->
                                    <div class="col-lg-8">
                                        <div class="mb-4">
                                            <label for="title" class="form-label">Título da Newsletter <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($newsletter['title']); ?>" required>
                                            <div class="form-text">Este título é para referência interna e não será exibido no e-mail.</div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="subject" class="form-label">Assunto do E-mail <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($newsletter['subject']); ?>" required>
                                            <div class="form-text">Este será o assunto exibido na caixa de entrada do destinatário.</div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="scheduled_for" class="form-label">Agendar Envio (opcional)</label>
                                            <div class="input-group" id="scheduled_for_picker">
                                                <input type="text" class="form-control" id="scheduled_for" name="scheduled_for" value="<?php echo formatarDataAgendamento($newsletter['scheduled_for']); ?>">
                                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                            </div>
                                            <div class="form-text">Deixe em branco para enviar manualmente.</div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="summernote" class="form-label">Conteúdo <span class="text-danger">*</span></label>
                                            <textarea id="summernote" name="content"><?php echo $newsletter['content']; ?></textarea>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <button type="button" id="btnPreview" class="btn btn-outline-primary">
                                                <i class="fas fa-eye me-2"></i> Visualizar
                                            </button>
                                            <button type="button" id="btnTest" class="btn btn-outline-secondary ms-2">
                                                <i class="fas fa-vial me-2"></i> Enviar Teste
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Coluna de modelos e visualização -->
                                    <div class="col-lg-4">
                                        <div class="card h-100">
                                            <div class="card-header">
                                                <h5 class="mb-0">Modelos Prontos</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="template-button <?php echo $newsletter['template'] == 'default' ? 'active' : ''; ?>" data-template="default" data-content="">
                                                            <img src="/GED2.0/assets/img/newsletter/template-default.jpg" class="template-thumbnail" alt="Template Padrão">
                                                            <div class="template-title">Padrão</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="template-button <?php echo $newsletter['template'] == 'ir' ? 'active' : ''; ?>" data-template="ir" data-content="conteudo-ir">
                                                            <img src="/GED2.0/assets/img/newsletter/template-ir.jpg" class="template-thumbnail" alt="Template IR">
                                                            <div class="template-title">Imposto de Renda</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="template-button <?php echo $newsletter['template'] == 'dicas' ? 'active' : ''; ?>" data-template="dicas" data-content="conteudo-dicas">
                                                            <img src="/GED2.0/assets/img/newsletter/template-dicas.jpg" class="template-thumbnail" alt="Template Dicas">
                                                            <div class="template-title">Dicas Fiscais</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="template-button <?php echo $newsletter['template'] == 'noticias' ? 'active' : ''; ?>" data-template="noticias" data-content="conteudo-noticias">
                                                            <img src="/GED2.0/assets/img/newsletter/template-noticias.jpg" class="template-thumbnail" alt="Template Notícias">
                                                            <div class="template-title">Notícias</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="/GED2.0/views/newsletter/list.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i> Voltar
                                    </a>
                                    <div>
                                        <button type="submit" class="btn btn-outline-success me-2" name="status" value="draft">
                                            <i class="fas fa-save me-2"></i> Salvar Rascunho
                                        </button>
                                        <button type="submit" class="btn btn-primary" name="status" value="sent">
                                            <i class="fas fa-paper-plane me-2"></i> Salvar e Enviar
                                        </button>
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
    
    <!-- Modal de Visualização -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewModalLabel">Pré-visualização da Newsletter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <iframe id="previewFrame" class="email-preview-frame"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
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
                        <label for="testEmail" class="form-label">E-mail para teste:</label>
                        <input type="email" class="form-control" id="testEmail" placeholder="seu@email.com" required>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Um e-mail de teste será enviado para o endereço informado.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnSendTest">
                        <i class="fas fa-paper-plane me-2"></i> Enviar Teste
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Conteúdos de Modelo -->
    <div id="template-contents" style="display: none;">
        <!-- Modelo de Imposto de Renda -->
        <div id="conteudo-ir">
            <h2 style="color: #0a4b78; margin-bottom: 20px;">Último Prazo para Declaração do Imposto de Renda 2025!</h2>
            
            <p>O prazo para a entrega da declaração do Imposto de Renda termina em <strong>31 de maio de 2025</strong>. Não deixe para a última hora e evite multas e complicações!</p>
            
            <div style="background-color: #f8f9fa; border-left: 4px solid #0a4b78; padding: 15px; margin: 20px 0;">
                <h4 style="color: #0a4b78; margin-top: 0;">Fique atento aos prazos:</h4>
                <ul>
                    <li>Início do prazo de entrega: 1º de março de 2025</li>
                    <li>Término do prazo: 31 de maio de 2025</li>
                    <li>Multa por atraso: mínimo de R$ 165,74, podendo chegar a 20% do imposto devido</li>
                </ul>
            </div>
            
            <h3 style="color: #0a4b78; margin-top: 30px;">Como podemos ajudar?</h3>
            
            <p>A Contabilidade Estrela oferece serviços completos para sua declaração de Imposto de Renda:</p>
            
            <ul>
                <li>Análise detalhada da sua documentação</li>
                <li>Identificação de todas as deduções possíveis</li>
                <li>Preenchimento e envio da declaração</li>
                <li>Acompanhamento em caso de malha fina</li>
                <li>Orientação para o planejamento tributário do próximo ano</li>
            </ul>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="https://contabilidadeestrela.com.br/imposto-de-renda" style="background-color: #0a4b78; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;">AGENDE SEU ATENDIMENTO</a>
            </div>
            
            <h3 style="color: #0a4b78; margin-top: 30px;">Documentos necessários:</h3>
            
            <p>Para facilitar o processo, tenha em mãos os seguintes documentos:</p>
            
            <ul>
                <li>Informes de rendimentos (salários, aposentadorias, aluguéis)</li>
                <li>Informes de instituições financeiras</li>
                <li>Recibos e notas de despesas médicas e educação</li>
                <li>Documentos de compra e venda de bens</li>
                <li>Comprovantes de doações realizadas</li>
                <li>Declaração do ano anterior (se houver)</li>
            </ul>
            
            <p>Entre em contato conosco pelo telefone (11) 1234-5678 ou responda a este e-mail para agendar sua consultoria tributária!</p>
        </div>
        
        <!-- Modelo de Dicas Fiscais -->
        <div id="conteudo-dicas">
            <h2 style="color: #0a4b78; margin-bottom: 20px;">5 Dicas Essenciais para a Saúde Financeira da sua Empresa</h2>
            
            <p>Prezados clientes e parceiros,</p>
            
            <p>Compartilhamos hoje algumas dicas valiosas para manter a saúde financeira do seu negócio em dia e evitar problemas com o fisco!</p>
            
            <div style="margin: 30px 0;">
                <h3 style="color: #0a4b78; border-bottom: 2px solid #0a4b78; padding-bottom: 10px;">1. Mantenha a Contabilidade em Dia</h3>
                <p>A contabilidade organizada não é apenas uma obrigação legal, mas uma ferramenta estratégica para a tomada de decisões. Certifique-se de registrar todas as operações financeiras, emitir notas fiscais corretamente e manter a documentação fiscal organizada.</p>
            </div>
            
            <div style="margin: 30px 0;">
                <h3 style="color: #0a4b78; border-bottom: 2px solid #0a4b78; padding-bottom: 10px;">2. Separe as Finanças Pessoais das Empresariais</h3>
                <p>Uma das principais causas de problemas financeiros nas pequenas empresas é a mistura entre contas pessoais e empresariais. Mantenha contas bancárias separadas e estabeleça um pró-labore fixo para evitar essa confusão.</p>
            </div>
            
            <div style="margin: 30px 0;">
                <h3 style="color: #0a4b78; border-bottom: 2px solid #0a4b78; padding-bottom: 10px;">3. Fique Atento ao Regime Tributário</h3>
                <p>O regime tributário escolhido impacta diretamente na carga de impostos da sua empresa. Avalie periodicamente se o regime atual (Simples Nacional, Lucro Presumido ou Lucro Real) ainda é o mais vantajoso para seu negócio.</p>
            </div>
            
            <div style="margin: 30px 0;">
                <h3 style="color: #0a4b78; border-bottom: 2px solid #0a4b78; padding-bottom: 10px;">4. Planeje o Fluxo de Caixa</h3>
                <p>Um bom planejamento de fluxo de caixa permite prever períodos de escassez e abundância de recursos, possibilitando decisões mais acertadas sobre investimentos, contratações e pagamento de obrigações fiscais.</p>
            </div>
            
            <div style="margin: 30px 0;">
                <h3 style="color: #0a4b78; border-bottom: 2px solid #0a4b78; padding-bottom: 10px;">5. Invista em Consultoria Especializada</h3>
                <p>Contar com profissionais especializados em contabilidade e gestão fiscal não é despesa, mas investimento. A Contabilidade Estrela está à disposição para oferecer consultoria personalizada e ajudar seu negócio a crescer de forma sustentável.</p>
            </div>
            
            <div style="background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 20px; margin: 30px 0; text-align: center;">
                <h4 style="color: #0a4b78; margin-top: 0;">Quer saber mais?</h4>
                <p>Entre em contato conosco para uma avaliação gratuita da situação fiscal e contábil da sua empresa!</p>
                <a href="https://contabilidadeestrela.com.br/contato" style="background-color: #0a4b78; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;">FALE CONOSCO</a>
            </div>
        </div>
        
        <!-- Modelo de Notícias -->
        <div id="conteudo-noticias">
            <h2 style="color: #0a4b78; margin-bottom: 20px;">Boletim Contábil e Tributário - Maio/2025</h2>
            
            <p>Prezados clientes,</p>
            
            <p>Confira as principais novidades e mudanças na legislação contábil e tributária deste mês!</p>
            
            <div style="border: 1px solid #dee2e6; border-radius: 5px; overflow: hidden; margin: 25px 0;">
                <div style="background-color: #0a4b78; color: white; padding: 15px;">
                    <h3 style="margin: 0; font-size: 18px;">Prazo para Declaração do IR se Encerra em Breve</h3>
                </div>
                <div style="padding: 15px;">
                    <p>O prazo para entrega da Declaração do Imposto de Renda Pessoa Física 2025 termina em 31 de maio. A expectativa da Receita Federal é receber mais de 38 milhões de declarações este ano. Contribuintes que não entregarem dentro do prazo estarão sujeitos à multa mínima de R$ 165,74.</p>
                    <a href="#" style="color: #0a4b78; font-weight: 600;">Leia mais →</a>
                </div>
            </div>
            
            <div style="border: 1px solid #dee2e6; border-radius: 5px; overflow: hidden; margin: 25px 0;">
                <div style="background-color: #0a4b78; color: white; padding: 15px;">
                    <h3 style="margin: 0; font-size: 18px;">Novas Regras para MEI em 2025</h3>
                </div>
                <div style="padding: 15px;">
                    <p>A partir de junho de 2025, entram em vigor novas regras para Microempreendedores Individuais (MEI). O limite de faturamento anual passa para R$ 150 mil e são incluídas novas atividades permitidas. Também haverá mudanças na forma de recolhimento dos tributos.</p>
                    <a href="#" style="color: #0a4b78; font-weight: 600;">Leia mais →</a>
                </div>
            </div>
            
            <div style="border: 1px solid #dee2e6; border-radius: 5px; overflow: hidden; margin: 25px 0;">
                <div style="background-color: #0a4b78; color: white; padding: 15px;">
                    <h3 style="margin: 0; font-size: 18px;">Governo Anuncia Simplificação no eSocial</h3>
                </div>
                <div style="padding: 15px;">
                    <p>O Ministério da Economia anunciou mudanças no sistema eSocial para simplificar o processo de envio de informações por parte das empresas. As alterações incluem redução de campos obrigatórios e unificação de algumas declarações, com implementação prevista para o segundo semestre.</p>
                    <a href="#" style="color: #0a4b78; font-weight: 600;">Leia mais →</a>
                </div>
            </div>
            
            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 30px 0;">
                <h3 style="color: #0a4b78; margin-top: 0;">Agenda Tributária - Maio/2025</h3>
                <ul style="padding-left: 20px;">
                    <li><strong>07/05:</strong> Pagamento do FGTS</li>
                    <li><strong>15/05:</strong> Recolhimento do PIS/COFINS</li>
                    <li><strong>20/05:</strong> Recolhimento do INSS</li>
                    <li><strong>20/05:</strong> Pagamento do Simples Nacional</li>
                    <li><strong>25/05:</strong> Recolhimento do ICMS (para a maioria dos estados)</li>
                    <li><strong>31/05:</strong> Prazo final para entrega da DIRPF 2025</li>
                </ul>
            </div>
            
            <p style="margin-top: 30px;">Para mais informações sobre qualquer um destes temas, entre em contato com nosso time de especialistas. Estamos sempre à disposição para esclarecer suas dúvidas e oferecer orientação personalizada para sua empresa ou situação fiscal.</p>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap Bundle com Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Summernote Editor -->
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    
    <!-- Tempus Dominus Datetime Picker -->
    <script src="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.7.13/dist/js/tempus-dominus.min.js"></script>
    
    <!-- Script personalizado -->
    <script src="/GED2.0/assets/js/dashboard.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inicializar editor Summernote
            $('#summernote').summernote({
                height: 300,
                minHeight: 200,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                placeholder: 'Escreva aqui o conteúdo da sua newsletter...'
            });
            
            // Inicializar seletor de data/hora
            const picker = new tempusDominus.TempusDominus(document.getElementById('scheduled_for_picker'), {
                localization: {
                    locale: 'pt-br',
                    format: 'dd/MM/yyyy HH:mm'
                },
                display: {
                    icons: {
                        time: 'fas fa-clock',
                        date: 'fas fa-calendar',
                        up: 'fas fa-arrow-up',
                        down: 'fas fa-arrow-down',
                        previous: 'fas fa-chevron-left',
                        next: 'fas fa-chevron-right',
                        today: 'fas fa-calendar-check',
                        clear: 'fas fa-trash',
                        close: 'fas fa-times'
                    },
                    buttons: {
                        today: true,
                        clear: true,
                        close: true
                    },
                    viewMode: 'calendar',
                    components: {
                        calendar: true,
                        date: true,
                        month: true,
                        year: true,
                        decades: true,
                        clock: true,
                        hours: true,
                        minutes: true,
                        seconds: false
                    }
                }
            });
            
            // Visualizar newsletter
            $('#btnPreview').click(function() {
                const title = $('#title').val();
                const content = $('#summernote').summernote('code');
                
                if (!content) {
                    alert('Por favor, adicione conteúdo à newsletter antes de visualizar.');
                    return;
                }
                
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
                            ${content}
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
                
                // Abrir modal
                $('#previewModal').modal('show');
            });
            
            // Abrir modal de teste
            $('#btnTest').click(function() {
                $('#testModal').modal('show');
            });
            
            // Enviar e-mail de teste
            $('#btnSendTest').click(function() {
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
                        $('#testModal').modal('hide');
                    },
                    error: function() {
                        alert('Erro ao enviar e-mail de teste.');
                    }
                });
            });
            
            // Selecionar template
            $('.template-button').click(function() {
                // Remover classe ativa de todos os botões
                $('.template-button').removeClass('active');
                // Adicionar classe ativa ao botão clicado
                $(this).addClass('active');
                
                // Obter template selecionado
                const template = $(this).data('template');
                const contentId = $(this).data('content');
                
                // Atualizar campo hidden
                $('#template').val(template);
                
                // Se tiver conteúdo para preencher
                if (contentId && $('#' + contentId).length > 0) {
                    // Confirmar se quer substituir o conteúdo atual
                    if (confirm('Deseja substituir o conteúdo atual pelo modelo selecionado?')) {
                        // Preencher editor com conteúdo do template
                        const templateContent = $('#' + contentId).html();
                        $('#summernote').summernote('code', templateContent);
                    }
                }
            });
        });
    </script>
</body>
</html>