<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Sistema Contabilidade Estrela 2.0
 * Serviço de Email
 */

// Definir ROOT_DIR se não estiver definido
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(dirname(__FILE__))));
}

// Incluir arquivos necessários com caminhos absolutos
require_once ROOT_DIR . '/app/Config/Email.php';
require_once ROOT_DIR . '/templates/email_nova_empresa.php';

class EmailService {
    
    private $mail;
    private $config;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->config = EmailConfig::getConfig();
        
        // Verifica se o PHPMailer está disponível
        if (!file_exists(ROOT_DIR . '/phpmailer/PHPMailerAutoload.php')) {
            throw new Exception('Biblioteca PHPMailer não encontrada');
        }
        
        require_once ROOT_DIR . '/phpmailer/PHPMailerAutoload.php';
        require_once ROOT_DIR . '/phpmailer/class.smtp.php';
        
        $this->mail = new PHPMailer(true);
        $this->configurarSMTP();
    }
    
    /**
     * Configura as definições SMTP
     */
    private function configurarSMTP() {
        $this->mail->isSMTP();
        $this->mail->Host = $this->config['smtp']['host'];
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $this->config['smtp']['user'];
        $this->mail->Password = $this->config['smtp']['pass'];
        $this->mail->SMTPSecure = $this->config['smtp']['secure'];
        $this->mail->Port = $this->config['smtp']['port'];
        $this->mail->SMTPDebug = $this->config['smtp']['debug'];
        $this->mail->CharSet = $this->config['geral']['charset'];
        $this->mail->IsHTML($this->config['geral']['is_html']);
        
        // Configurar remetente
        $this->mail->From = $this->config['remetente']['email'];
        $this->mail->FromName = $this->config['remetente']['nome'];
    }
    
    /**
     * Envia email de cadastro de nova empresa
     * 
     * @param array $empresaData Dados da empresa
     * @param array $destinatarios Lista de destinatários adicionais
     * @return array Resultado do envio
     */
    public function enviarEmailNovaEmpresa($empresaData, $destinatarios = []) {
        try {
            // Limpa destinatários anteriores
            $this->mail->clearAddresses();
            
            // Adiciona destinatário principal
            $this->mail->addAddress($this->config['destinatarios']['copia']);
            
            // Adiciona destinatários adicionais
            foreach ($destinatarios as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->mail->addAddress($email);
                }
            }
            
            // Define o assunto
            $assunto = "🚨 NOVA EMPRESA: {$empresaData['emp_code']} - {$empresaData['emp_name']}";
            $this->mail->Subject = $assunto;
            
            // Gera o corpo do email
            $this->mail->Body = EmailTemplateNovaEmpresa::gerarHTML($empresaData);
            
            // Envia o email
            if ($this->mail->send()) {
                return [
                    'sucesso' => true,
                    'mensagem' => 'Email enviado com sucesso'
                ];
            } else {
                throw new Exception('Falha ao enviar email: ' . $this->mail->ErrorInfo);
            }
            
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ];
        }
    }

        /**
         * Envia email de certificado digital
         * 
         * @param array $empresaData Dados da empresa
         * @param array $certificadoData Dados do certificado
         * @param array $destinatarios Lista de destinatários
         * @return array Resultado do envio
         */
        public function enviarEmailCertificado($empresaData, $certificadoData, $destinatarios = []) {
            try {
                // Incluir template de email
                require_once ROOT_DIR . '/templates/email_certificado.php';
                
                // Limpa destinatários anteriores
                $this->mail->clearAddresses();
                
                // Adiciona destinatário principal
                $this->mail->addAddress($this->config['destinatarios']['copia']);
                
                // Adiciona destinatários adicionais
                foreach ($destinatarios as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $this->mail->addAddress($email);
                    }
                }
                
                // Define o assunto
                $this->mail->Subject = EmailTemplateCertificado::gerarAssunto($empresaData);
                
                // Gera o corpo do email
                $this->mail->Body = EmailTemplateCertificado::gerarHTML($empresaData, $certificadoData);
                
                // Envia o email
                if ($this->mail->send()) {
                    return [
                        'sucesso' => true,
                        'mensagem' => 'Email de certificado digital enviado com sucesso'
                    ];
                } else {
                    throw new Exception('Falha ao enviar email: ' . $this->mail->ErrorInfo);
                }
                
            } catch (Exception $e) {
                return [
                    'sucesso' => false,
                    'mensagem' => $e->getMessage()
                ];
            }
        }
}
?>