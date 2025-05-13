<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Funções para envio de e-mails usando PHPMailer
 */

// Incluir bibliotecas necessárias
require_once __DIR__ . '/.././../app/Config/App.php';
require_once __DIR__ . '/.././../app/Config/Database.php';
require_once __DIR__ . '/.././../app/Config/Logger.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailSender {
    private $mailer;
    private $trackingEnabled = true;
    
    /**
     * Inicializa o objeto PHPMailer com configurações do sistema
     */
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        
        // Configurações de servidor
        $this->mailer->isSMTP();
        $this->mailer->Host       = SMTP_HOST;
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = SMTP_USERNAME;
        $this->mailer->Password   = SMTP_PASSWORD;
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port       = SMTP_PORT;
        $this->mailer->CharSet    = 'UTF-8';
        
        // Configurações de remetente
        $this->mailer->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $this->mailer->addReplyTo(SMTP_REPLY_TO, SMTP_FROM_NAME);
        
        // Configurar o tempo máximo de timeout para o envio
        $this->mailer->Timeout = 60; // 60 segundos
    }
    
    /**
     * Define se o rastreamento de abertura e cliques deve ser habilitado
     */
    public function setTracking($enabled = true) {
        $this->trackingEnabled = $enabled;
        return $this;
    }
    
    /**
     * Adiciona um destinatário
     */
    public function addRecipient($email, $name = '') {
        $this->mailer->addAddress($email, $name);
        return $this;
    }
    
    /**
     * Adiciona múltiplos destinatários
     */
    public function addRecipients($recipients) {
        foreach ($recipients as $recipient) {
            $email = isset($recipient['email']) ? $recipient['email'] : '';
            $name = isset($recipient['name']) ? $recipient['name'] : '';
            
            if (!empty($email)) {
                $this->mailer->addAddress($email, $name);
            }
        }
        return $this;
    }
    
    /**
     * Adiciona anexo ao e-mail
     */
    public function addAttachment($path, $name = '') {
        try {
            $this->mailer->addAttachment($path, $name);
        } catch (Exception $e) {
            Logger::error('email', 'Erro ao adicionar anexo: ' . $e->getMessage());
        }
        return $this;
    }
    
    /**
     * Envia uma newsletter para um destinatário específico
     */
    public function sendNewsletter($newsletter, $subscriberId, $recipientEmail, $recipientName = '') {
        try {
            // Limpar destinatários anteriores
            $this->mailer->clearAddresses();
            
            // Adicionar o destinatário atual
            $this->mailer->addAddress($recipientEmail, $recipientName);
            
            // Configurar assunto e conteúdo
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $newsletter['subject'];
            
            // Adicionar pixel de rastreamento se habilitado
            $trackingPixel = '';
            $trackingLinks = $newsletter['content'];
            
            if ($this->trackingEnabled) {
                $trackingPixel = '<img src="' . SITE_URL . '/track.php?n=' . $newsletter['id'] . '&s=' . $subscriberId . '&t=open" width="1" height="1" alt="" style="display:none;">';
                
                // Adicionar rastreamento aos links
                $trackingLinks = $this->addTrackingToLinks($trackingLinks, $newsletter['id'], $subscriberId);
            }
            
            // Montar o corpo do e-mail
            $emailBody = $this->getNewsletterTemplate($trackingLinks, $newsletter, $recipientName);
            $emailBody .= $trackingPixel;
            
            $this->mailer->Body = $emailBody;
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $newsletter['content']));
            
            // Enviar o e-mail
            $result = $this->mailer->send();
            
            // Registrar envio bem-sucedido
            if ($result) {
                $this->logNewsletterStatus($newsletter['id'], $subscriberId, 'sent');
                return true;
            }
            
        } catch (Exception $e) {
            // Registrar erro
            Logger::error('newsletter', 'Erro ao enviar newsletter #' . $newsletter['id'] . ' para ' . $recipientEmail . ': ' . $e->getMessage());
            $this->logNewsletterStatus($newsletter['id'], $subscriberId, 'failed');
            return false;
        }
    }
    
    /**
     * Adiciona rastreamento aos links no conteúdo da newsletter
     */
    private function addTrackingToLinks($content, $newsletterId, $subscriberId) {
        $pattern = '/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1/i';
        
        return preg_replace_callback($pattern, function($matches) use ($newsletterId, $subscriberId) {
            $originalUrl = $matches[2];
            $trackingUrl = SITE_URL . '/redirect.php?n=' . $newsletterId . '&s=' . $subscriberId . '&url=' . urlencode($originalUrl);
            return '<a href="' . $trackingUrl . '"';
        }, $content);
    }
    
    /**
     * Registra o status de envio de newsletter no banco de dados
     */
    private function logNewsletterStatus($newsletterId, $subscriberId, $status) {
        $sql = "INSERT INTO newsletter_logs (newsletter_id, subscriber_id, status) VALUES (?, ?, ?)";
        Database::execute($sql, [$newsletterId, $subscriberId, $status]);
    }
    
    /**
     * Retorna o template HTML da newsletter
     */
    private function getNewsletterTemplate($content, $newsletter, $recipientName) {
        // Data atual formatada
        $currentDate = date('d/m/Y');
        
        // Template HTML com a estrutura visual do site
        $template = '
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($newsletter['subject']) . '</title>
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
            padding: 20px;
            text-align: center;
        }
        
        .logo {
            max-width: 200px;
            margin: 0 auto;
        }
        
        /* Alerta tributário */
        .tax-alert {
            background-color: #ff7e00;
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: 600;
        }
        
        /* Conteúdo principal */
        .content {
            padding: 25px;
        }
        
        .title {
            font-size: 24px;
            color: #0a4b78;
            margin-bottom: 20px;
        }
        
        /* Rodapé */
        .footer {
            background-color: #222;
            color: #f1f1f1;
            padding: 25px;
            text-align: center;
            font-size: 13px;
        }
        
        .social-icons {
            margin: 15px 0;
        }
        
        .social-icons a {
            color: white;
            margin: 0 10px;
            text-decoration: none;
        }
        
        .unsubscribe-link {
            color: #ccc;
            font-size: 12px;
            text-decoration: none;
        }
        
        /* Elementos responsivos */
        img {
            max-width: 100%;
            height: auto;
        }
        
        @media screen and (max-width: 600px) {
            .container {
                width: 100%;
            }
            
            .content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Cabeçalho -->
        <div class="header">
            <img src="' . SITE_URL . '/assets/img/logo-white.png" alt="Contabilidade Estrela" class="logo">
        </div>
        
        <!-- Alerta tributário IR -->
        <div class="tax-alert">
            <p>📢 ÚLTIMA CHANCE: Imposto de Renda 2025 - O prazo termina em 31 de maio! Entre em contato conosco para garantir sua declaração sem complicações.</p>
        </div>
        
        <!-- Conteúdo principal -->
        <div class="content">
            <h1 class="title">' . htmlspecialchars($newsletter['title']) . '</h1>
            
            <p>Olá ' . htmlspecialchars($recipientName) . ',</p>
            
            <div class="newsletter-content">
                ' . $content . '
            </div>
            
            <p style="margin-top: 25px;">
                Atenciosamente,<br>
                <strong>Equipe Contabilidade Estrela</strong>
            </p>
        </div>
        
        <!-- Rodapé -->
        <div class="footer">
            <p><strong>Contabilidade Estrela</strong><br>
            Rua das Estrelas, 123 - Centro<br>
            São Paulo/SP - CEP 01234-567<br>
            Tel: (11) 1234-5678</p>
            
            <div class="social-icons">
                <a href="https://facebook.com/contabilidadeestrela"><img src="' . SITE_URL . '/assets/img/newsletter/facebook.png" alt="Facebook" width="32"></a>
                <a href="https://instagram.com/contabilidadeestrela"><img src="' . SITE_URL . '/assets/img/newsletter/instagram.png" alt="Instagram" width="32"></a>
                <a href="https://linkedin.com/company/contabilidadeestrela"><img src="' . SITE_URL . '/assets/img/newsletter/linkedin.png" alt="LinkedIn" width="32"></a>
            </div>
            
            <p>Newsletter enviada em ' . $currentDate . '</p>
            
            <p>
                <a href="' . SITE_URL . '/unsubscribe.php?email=' . urlencode($recipientEmail) . '&hash=' . md5($recipientEmail . SECURITY_SALT) . '" class="unsubscribe-link">Cancelar inscrição</a>
            </p>
        </div>
    </div>
</body>
</html>';

        return $template;
    }
    
    /**
     * Envia um e-mail de teste
     */
    public function sendTestEmail($to, $subject, $content) {
        try {
            // Limpar destinatários anteriores
            $this->mailer->clearAddresses();
            
            // Adicionar o destinatário de teste
            $this->mailer->addAddress($to);
            
            // Configurar assunto e conteúdo
            $this->mailer->isHTML(true);
            $this->mailer->Subject = '[TESTE] ' . $subject;
            
            // Configurar corpo do e-mail
            $newsletter = [
                'id' => 0,
                'subject' => $subject,
                'title' => 'E-mail de Teste',
                'content' => $content
            ];
            
            $emailBody = $this->getNewsletterTemplate($content, $newsletter, 'Usuário de Teste');
            
            $this->mailer->Body = $emailBody;
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $content));
            
            // Enviar o e-mail
            return $this->mailer->send();
            
        } catch (Exception $e) {
            // Registrar erro
            Logger::error('newsletter', 'Erro ao enviar e-mail de teste para ' . $to . ': ' . $e->getMessage());
            return false;
        }
    }
}