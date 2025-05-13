<?php

// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}



ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Carregue as configurações de email
require_once __DIR__ . '/../../../...../app/Config/Email.php';

// Incluir as classes do PHPMailer
require_once __DIR__ . '/../../phpmailer/src/Exception.php';
require_once __DIR__ . '/../../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../phpmailer/src/SMTP.php';

// Inclua a biblioteca PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Processe o envio de email quando o formulário for enviado
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar'])) {
    $destinatario = filter_input(INPUT_POST, 'destinatario', FILTER_VALIDATE_EMAIL);
    
    if ($destinatario) {
        try {
            // Inicializar PHPMailer
            $mail = new PHPMailer(true);
            
            // Configurações SMTP
            $mail->isSMTP();
            $mail->Host = EmailConfig::SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = EmailConfig::SMTP_USER;
            $mail->Password = EmailConfig::SMTP_PASS;
            $mail->SMTPSecure = EmailConfig::SMTP_SECURE;
            $mail->Port = EmailConfig::SMTP_PORT;
            $mail->CharSet = EmailConfig::CHARSET;
            
            // Remetente
            $mail->setFrom(EmailConfig::EMAIL_REMETENTE, EmailConfig::NOME_REMETENTE);
            $mail->addReplyTo(EmailConfig::EMAIL_REMETENTE, EmailConfig::NOME_REMETENTE);
            
            // Destinatários
            $mail->addAddress($destinatario);
            $mail->addBCC(EmailConfig::EMAIL_COPIA); // Cópia oculta
            
            // Conteúdo
            $mail->isHTML(EmailConfig::IS_HTML);
            $mail->Subject = 'ÚLTIMO PRAZO: Imposto de Renda 2025 - Não perca a data limite!';
            
            // Conteúdo HTML do email
            $mail->Body = getEmailTemplate();
            
            // Versão texto simples
            $mail->AltBody = 'Lembrete: O prazo para declaração do Imposto de Renda 2025 termina em 31 de maio! Entre em contato com a Contabilidade Estrela para garantir sua declaração sem complicações.';
            
            // Enviar
            $mail->send();
            
            $mensagem = '<div class="alert alert-success">Email enviado com sucesso para ' . htmlspecialchars($destinatario) . '!</div>';
        } catch (Exception $e) {
            $mensagem = '<div class="alert alert-danger">Erro ao enviar email: ' . $mail->ErrorInfo . '</div>';
        }
    } else {
        $mensagem = '<div class="alert alert-warning">Por favor, informe um endereço de email válido.</div>';
    }
}

// Função para obter o template do email
function getEmailTemplate() {
    // Data atual formatada
    $currentDate = date('d/m/Y');
    
    return '<!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Imposto de Renda 2025 - Contabilidade Estrela</title>
        <style>
            /* Estilos gerais */
            body {
                font-family: "Poppins", Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 0;
                padding: 0;
                background-color: #f5f5f5;
            }
            
            /* Container principal */
            .container {
                max-width: 650px;
                margin: 0 auto;
                background: #ffffff;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }
            
            /* Cabeçalho */
            .header {
                background-color: #0a4b78;
                color: white;
                padding: 25px;
                text-align: center;
            }
            
            .logo {
                max-width: 220px;
                margin: 0 auto;
            }
            
            .header h1 {
                margin: 15px 0 5px;
                font-size: 28px;
                font-weight: 600;
            }
            
            .header p {
                margin: 0;
                font-size: 16px;
                opacity: 0.9;
            }
            
            /* Alerta tributário */
            .tax-alert {
                background-color: #ff7e00;
                color: white;
                padding: 15px;
                text-align: center;
                font-weight: 600;
            }
            
            .tax-alert p {
                margin: 0;
                font-size: 16px;
            }
            
            .tax-alert strong {
                font-weight: 700;
            }
            
            /* Conteúdo principal */
            .content {
                padding: 30px;
            }
            
            .title {
                font-size: 24px;
                color: #0a4b78;
                margin-bottom: 20px;
                border-bottom: 2px solid #0a4b78;
                padding-bottom: 10px;
            }
            
            /* Call to action */
            .cta-button {
                display: inline-block;
                background-color: #0a4b78;
                color: white !important;
                text-decoration: none;
                padding: 12px 25px;
                border-radius: 5px;
                margin: 20px 0;
                font-weight: 600;
                text-align: center;
            }
            
            .cta-button:hover {
                background-color: #083a5e;
            }
            
            /* Informações destacadas */
            .info-box {
                background-color: #f8f9fa;
                border-left: 4px solid #0a4b78;
                padding: 15px;
                margin: 20px 0;
            }
            
            .info-box h3 {
                margin-top: 0;
                color: #0a4b78;
            }
            
            /* Timeline de prazos */
            .timeline {
                margin: 30px 0;
            }
            
            .timeline-item {
                position: relative;
                padding-left: 30px;
                margin-bottom: 20px;
            }
            
            .timeline-item:before {
                content: "";
                position: absolute;
                left: 0;
                top: 5px;
                width: 15px;
                height: 15px;
                border-radius: 50%;
                background: #0a4b78;
            }
            
            .timeline-item:after {
                content: "";
                position: absolute;
                left: 7px;
                top: 20px;
                height: calc(100% + 5px);
                width: 1px;
                background: #dee2e6;
            }
            
            .timeline-item:last-child:after {
                display: none;
            }
            
            .timeline-date {
                font-weight: 700;
                color: #0a4b78;
            }
            
            /* Rodapé */
            .footer {
                background-color: #212529;
                color: #f8f9fa;
                padding: 30px;
                text-align: center;
            }
            
            .social-icons {
                margin: 20px 0;
            }
            
            .social-icons a {
                display: inline-block;
                margin: 0 10px;
                color: white;
            }
            
            .social-icon {
                width: 32px;
                height: 32px;
            }
            
            .footer-links {
                margin: 15px 0;
            }
            
            .footer-links a {
                color: #9ca3af;
                text-decoration: none;
                margin: 0 10px;
                font-size: 14px;
            }
            
            .footer-info {
                font-size: 13px;
                color: #9ca3af;
                margin-bottom: 5px;
            }
            
            .unsubscribe {
                font-size: 12px;
                color: #6c757d;
                margin-top: 15px;
            }
            
            .unsubscribe a {
                color: #6c757d;
                text-decoration: underline;
            }
            
            /* Responsividade */
            @media screen and (max-width: 600px) {
                .container {
                    width: 100%;
                }
                
                .content {
                    padding: 20px;
                }
                
                .footer {
                    padding: 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <!-- Cabeçalho -->
            <div class="header">
                <h1>Contabilidade Estrela</h1>
                <p>Transparência e Excelência em Serviços Contábeis</p>
            </div>
            
            <!-- Alerta tributário IR -->
            <div class="tax-alert">
                <p><strong>ATENÇÃO:</strong> Imposto de Renda 2025 - O prazo termina em 31 de maio! Entre em contato conosco para garantir sua declaração sem complicações.</p>
            </div>
            
            <!-- Conteúdo principal -->
            <div class="content">
                <h2 class="title">Último Prazo para Declaração do Imposto de Renda 2025!</h2>
                
                <p>Prezado(a) Cliente,</p>
                
                <p>Esperamos que esta mensagem o encontre bem. Gostaríamos de lembrá-lo sobre o prazo final para a entrega da declaração do Imposto de Renda 2025, que se encerra em <strong>31 de maio</strong>.</p>
                
                <div class="info-box">
                    <h3>Informações Importantes:</h3>
                    <ul>
                        <li>A entrega deve ser feita até 31 de maio de 2025, sem possibilidade de prorrogação</li>
                        <li>Quem atrasar está sujeito à multa mínima de R$ 165,74, podendo chegar a 20% do imposto devido</li>
                        <li>É necessário declarar mesmo quem teve rendimentos isentos acima de R$ 200 mil em 2024</li>
                    </ul>
                </div>
                
                <p>A Contabilidade Estrela está preparada para ajudá-lo a cumprir esta obrigação fiscal com tranquilidade e segurança. Nossa equipe especializada pode auxiliar em todo o processo, desde a organização dos documentos até a transmissão final da declaração.</p>
                
                <div style="text-align: center;">
                    <a href="https://contabilidadeestrela.com.br/agendamento" class="cta-button">AGENDE SEU ATENDIMENTO AGORA</a>
                </div>
                
                <h3 style="color: #0a4b78; margin-top: 30px;">Cronograma do IR 2025</h3>
                
                <div class="timeline">
                    <div class="timeline-item">
                        <p class="timeline-date">01/03/2025</p>
                        <p>Início do prazo para entrega da declaração</p>
                    </div>
                    
                    <div class="timeline-item">
                        <p class="timeline-date">15/04/2025</p>
                        <p>Data recomendada para iniciar o processo de declaração</p>
                    </div>
                    
                    <div class="timeline-item">
                        <p class="timeline-date">15/05/2025</p>
                        <p>Último dia para informar contas bancárias e investimentos no exterior</p>
                    </div>
                    
                    <div class="timeline-item">
                        <p class="timeline-date">31/05/2025</p>
                        <p>Prazo final para entrega da declaração</p>
                    </div>
                </div>
                
                <h3 style="color: #0a4b78; margin-top: 30px;">Documentos Necessários</h3>
                
                <p>Para facilitar o processo, já separe os seguintes documentos:</p>
                
                <ul>
                    <li>Informes de rendimentos (salários, aposentadorias, pensões)</li>
                    <li>Informes de rendimentos financeiros (bancos, corretoras)</li>
                    <li>Comprovantes de gastos com saúde e educação</li>
                    <li>Documentos de bens e direitos (imóveis, veículos, investimentos)</li>
                    <li>Comprovantes de dívidas e ônus</li>
                    <li>Recibos de doações efetuadas</li>
                    <li>Declaração do ano anterior (para comparação)</li>
                </ul>
                
                <div class="info-box" style="background-color: #e9f7fe; border-color: #0dcaf0;">
                    <h3 style="color: #0dcaf0;">Novidades para 2025:</h3>
                    <p>A Receita Federal anunciou mudanças importantes para a declaração deste ano, incluindo:</p>
                    <ul>
                        <li>Novos limites de dedução para gastos com educação</li>
                        <li>Declaração simplificada para MEI com faturamento até R$ 81 mil</li>
                        <li>Obrigatoriedade de informar criptomoedas a partir de R$ 5.000</li>
                    </ul>
                </div>
                
                <p style="margin-top: 30px;">Nossa equipe está à disposição para esclarecer qualquer dúvida e ajudá-lo a aproveitar todos os benefícios fiscais disponíveis, garantindo uma declaração segura e otimizada.</p>
                
                <div style="text-align: center; margin-top: 30px;">
                    <a href="tel:+551112345678" class="cta-button" style="background-color: #28a745;">FALE CONOSCO: (11) 2124-7070</a>
                </div>
            </div>
            
            <!-- Rodapé -->
            <div class="footer">
                <p class="footer-info"><strong>CONTABILIDADE ESTRELA</strong></p>
                <p class="footer-info">Avenida Julio Buono, n 2525 - Vila Gustavo, São Paulo/SP - CEP 02201-001</p>
                <p class="footer-info">Tel: (11) 2124-7070 | E-mail: cestrela@terra.com.br</p>
                
                <p class="footer-info" style="margin-top: 15px;">© ' . date('Y') . ' Contabilidade Estrela - Todos os direitos reservados.</p>
            </div>
        </div>
    </body>
    </html>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envio de Email - Imposto de Renda</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', Arial, sans-serif;
        }
        
        .email-sender-container {
            max-width: 800px;
            margin: 50px auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .email-header {
            background-color: #0a4b78;
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .email-form {
            padding: 30px;
        }
        
        .preview-container {
            margin-top: 30px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            height: 500px;
            overflow: auto;
        }
        
        .preview-iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .btn-primary {
            background-color: #0a4b78;
            border-color: #0a4b78;
        }
        
        .btn-primary:hover {
            background-color: #083a5e;
            border-color: #083a5e;
        }
    </style>
</head>
<body>
    <div class="email-sender-container">
        <div class="email-header">
            <h2><i class="fas fa-envelope-open-text me-2"></i> Envio de Email - Imposto de Renda</h2>
            <p class="mb-0">Contabilidade Estrela</p>
        </div>
        
        <div class="email-form">
            <?php echo $mensagem; ?>
            
            <form method="post" action="">
                <div class="mb-4">
                    <label for="destinatario" class="form-label">Email do Destinatário:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="email" class="form-control" id="destinatario" name="destinatario" placeholder="email@exemplo.com" required>
                    </div>
                    <div class="form-text">O email será enviado com cópia oculta para <?php echo EmailConfig::EMAIL_COPIA; ?></div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" name="enviar" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane me-2"></i> Enviar Email
                    </button>
                </div>
            </form>
            
            <div class="mt-4">
                <h5>Prévia do Email:</h5>
                <div class="preview-container">
                    <iframe id="previewFrame" class="preview-iframe"></iframe>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle com Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Carrega a prévia do email no iframe
        document.addEventListener('DOMContentLoaded', function() {
            const iframe = document.getElementById('previewFrame');
            const content = `<?php echo str_replace('`', '\`', str_replace('$', '\$', str_replace('"', '\"', str_replace('\\', '\\\\', getEmailTemplate())))); ?>`;
            
            iframe.contentWindow.document.open();
            iframe.contentWindow.document.write(content);
            iframe.contentWindow.document.close();
        });
    </script>
</body>
</html>