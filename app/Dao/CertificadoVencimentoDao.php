<?php
/**
 * Script for Sending Digital Certificate Expiration Notifications
 * 
 * @package ContabilidadeEstrela
 * @subpackage CertificateManagement
 * @version 3.0
 */

// Set the default timezone
date_default_timezone_set('America/Sao_Paulo');

// Require necessary files
require_once(__DIR__ . '/../../...../app/Config/Database.php');
require_once(__DIR__ . '/../../...../app/Config/Logger.php');

// Importações do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../phpmailer/src/Exception.php';
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';

/**
 * Class for managing and sending digital certificate expiration notifications
 */
class EnviaVencimentoCertificadoDAO
{
    /**
     * Database connection
     * @var PDO
     */
    private $conn;

    /**
     * PHPMailer instance
     * @var PHPMailer
     */
    private $mail;

    /**
     * Constructor to initialize database connection and email configuration
     */
    public function __construct()
    {
        // Initialize database connection
        $database = new Database();
        $db = $database->dbConnection();
        $this->conn = $db;

        // Configure PHPMailer
        $this->mail = new PHPMailer;
        $this->mail->CharSet = 'UTF-8';
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'recuperacaoestrela@gmail.com'; // Replace with your email
        $this->mail->Password = 'kaznjaozkximdmww'; // Replace with your app password
        $this->mail->SMTPSecure = 'ssl';
        $this->mail->Port = 465;
        $this->mail->IsHTML(true);
        $this->mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        $this->mail->From = 'recuperacaoestrela@gmail.com';
        $this->mail->FromName = 'CONTABILIDADE ESTRELA';
    }

    /**
     * Execute a prepared SQL query
     * 
     * @param string $sql SQL query to prepare
     * @return PDOStatement Prepared statement
     */
    public function runQuery($sql)
    {
        $stmt = $this->conn->prepare($sql);
        return $stmt;
    }

    /**
     * Calculate days remaining until certificate expiration
     * 
     * @param string $dataVencimento Expiration date in 'd/m/Y' format
     * @return string Days remaining or 'VENCIDO' status
     */
    private function calcularDiasRestantes($dataVencimento) 
    {
        try {
            // Convert expiration date to DateTime object
            $dataVencimento = DateTime::createFromFormat('d/m/Y', $dataVencimento);
            $dataAtual = new DateTime();

            // Calculate days remaining
            $intervalo = $dataAtual->diff($dataVencimento);
            
            // Check if the certificate is expired or about to expire
            if ($intervalo->invert) {
                return "VENCIDO";
            } else {
                return $intervalo->days . " dias restantes";
            }
        } catch (Exception $e) {
            error_log('Erro ao calcular dias restantes: ' . $e->getMessage());
            return "Data inválida";
        }
    }

    /**
     * Verify and process certificates nearing expiration
     * 
     * @throws PDOException If database query fails
     */
    public function taskVerifyCertificados()
    {
        try {
            // SQL to fetch certificates expiring within 30 days or already expired
            $stmt = $this->conn->prepare("
                SELECT * FROM certificado_digital
                WHERE (STR_TO_DATE(certificado_validade, '%d/%m/%Y') <= CURDATE() + INTERVAL 30 DAY 
                AND STR_TO_DATE(certificado_validade, '%d/%m/%Y') >= CURDATE()) 
                OR STR_TO_DATE(certificado_validade, '%d/%m/%Y') < CURDATE()
            ");
            $stmt->execute();
            $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Prevent script timeout
            set_time_limit(0);

            // Process certificates in batches
            $tamanhoLote = 10;
            $lotes = array_chunk($empresas, $tamanhoLote);

            foreach ($lotes as $lote) {
                echo "Processando lote de certificados" . PHP_EOL;
                var_dump($lote);
                $this->enviarLoteCertificados($lote);
                
                // Small delay between batches to prevent overwhelming the server
                sleep(20);
            }
        } catch (PDOException $e) {
            error_log('Erro ao verificar certificados: ' . $e->getMessage());
            echo 'Erro ao verificar certificados: ' . $e->getMessage();
        }
    }

    /**
     * Send emails for a batch of certificates
     * 
     * @param array $empresas Batch of companies with expiring certificates
     * @throws PDOException If email sending fails
     */
    public function enviarLoteCertificados($empresas)
    {
        try {
            foreach ($empresas as $empresa) {
                $result = $this->EnviaVencimentoCertificado($empresa);
                if (!$result) {
                    echo 'Erro no envio do e-mail para ' . $empresa['email_empresa'] . PHP_EOL;
                }
            }
        } catch (PDOException $e) {
            error_log('Erro ao enviar lote de certificados: ' . $e->getMessage());
            echo 'Erro ao enviar lote de certificados: ' . $e->getMessage();
        }
    }
    
    /**
     * Send individual certificate expiration email
     * 
     * @param array $dadosEmpresa Company certificate details
     * @return bool Success status of email sending
     */
    public function EnviaVencimentoCertificado($dadosEmpresa)
    {
        try {
            // Log email sending
            $file = fopen("alertasCertificados.txt", "a+");
            fwrite($file, "Email enviado para: " . $dadosEmpresa['email_empresa'] . " - " . date("d/m/Y H:i:s") . "\n");
            fclose($file);

            // Internal email recipients
            $emails = [
                'cestrela.cancelar@terra.com.br', 
                'cestrela.visitadores@terra.com.br'
            ];

            // Clear previous recipients
            $this->mail->clearAddresses();

            // Add recipients
            foreach ($emails as $email) {
                $this->mail->addAddress($email);
            }

            // Set email subject
            $this->mail->Subject = "Certificado Digital {$dadosEmpresa['emp_name']} - Contabilidade Estrela";

            // Email body (elegant template)
            $this->mail->Body = $this->gerarCorpoEmail($dadosEmpresa);

            // Send email
            if ($this->mail->Send()) {
                $this->mail->SmtpClose();
                echo 'Email enviado com sucesso para ' . $dadosEmpresa['email_empresa'] . PHP_EOL;
                return true;
            } else {
                echo 'Erro no envio do email: ' . $this->mail->ErrorInfo . PHP_EOL;
                return false;
            }
        } catch (Exception $e) {
            error_log('Erro fatal no envio de certificado: ' . $e->getMessage());
            echo 'Erro fatal no envio de certificado' . PHP_EOL;
            return false;
        }
    }

    /**
     * Generate modern HTML email body
     * 
     * @param array $dadosEmpresa Company certificate details
     * @return string HTML email body
     */
    private function gerarCorpoEmail($dadosEmpresa)
    {
        // Calculate days remaining
        $diasRestantes = $this->calcularDiasRestantes($dadosEmpresa['certificado_vencimento']);

        return '
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta de Certificado Digital</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 12px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 30px 20px;
        }
        .header img {
            max-width: 200px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .alert {
            background-color: #ff6b6b;
            color: white;
            text-align: center;
            padding: 15px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .certificate-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .certificate-info table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .certificate-info td {
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        .certificate-info td:first-child {
            font-weight: 600;
            color: #6c757d;
            width: 40%;
        }
        .certificate-info td:last-child {
            color: #2c3e50;
        }
        .expiry-warning {
            color: #ff6b6b;
            font-weight: 700;
            text-align: center;
            margin-bottom: 20px;
        }
        .footer {
            background-color: #2c3e50;
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 12px;
        }
        .footer-note {
            background-color: #ffdddd;
            color: #ff0000;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <img src="https://contabilidadeestrela.com.br/wp-content/uploads/2020/12/ContabilidadeEstrela-960w.png" alt="Contabilidade Estrela Logo">
            <h1>Alerta de Certificado Digital</h1>
        </div>

        <div class="alert">
            ATENÇÃO: Seu Certificado Digital Está Próximo do Vencimento
        </div>

        <div class="content">
            <div class="expiry-warning">
                Certificado Vencimento: ' . htmlspecialchars($dadosEmpresa['certificado_vencimento']) . '
                <br>
                ' . htmlspecialchars($diasRestantes) . '
            </div>

            <div class="certificate-info">
                <table>
                    <tr>
                        <td>Empresa</td>
                        <td>' . htmlspecialchars($dadosEmpresa['emp_code']) . '</td>
                    </tr>
                    <tr>
                        <td>Empresa</td>
                        <td>' . htmlspecialchars($dadosEmpresa['emp_name']) . '</td>
                    </tr>
                    <tr>
                        <td>CNPJ</td>
                        <td>' . htmlspecialchars($dadosEmpresa['emp_cnpj']) . '</td>
                    </tr>
                    <tr>
                        <td>Tipo de Certificado</td>
                        <td>' . htmlspecialchars($dadosEmpresa['tipo_certificado']) . '</td>
                    </tr>
                    <tr>
                        <td>Situação</td>
                        <td>' . htmlspecialchars($dadosEmpresa['situacao']) . '</td>
                    </tr>
                </table>
            </div>

            <div class="certificate-info" style="background-color: #e9ecef;">
                <h3 style="margin-top: 0; color: #2c3e50; text-align: center;">Empresa para Renovação</h3>
                <p style="text-align: center; color: #2c3e50;">
                    <strong>Certificadora VALID</strong><br>
                    Telefone: (11) 2983-2173 / 2478-4812<br>
                    Celular: (11) 97050-5682<br>
                    Email: tulio.lopes@conectividadedigital.com.br
                </p>
            </div>
        </div>

        <div class="footer">
            <p>Contabilidade Estrela</p>
            <p>Av. Julio Buono, 2525 - 2º Andar | CEP 02201-001</p>
            <p>Tel: (11) 2124-7070 | Email: cestrela@terra.com.br</p>
        </div>

        <div class="footer-note">
            Nota: A Contabilidade Estrela não é responsável pela emissão ou renovação de certificados digitais.
        </div>
    </div>
</body>
</html>';
    }
}

// Main execution block
try {
    // Create an instance of the certificate expiration notification class
    $task = new EnviaVencimentoCertificadoDAO();
    
    // Run the task to verify and send certificate expiration notifications
    $task->taskVerifyCertificados();
} catch (PDOException $e) {
    // Log any critical errors during execution
    error_log('Erro crítico: ' . $e->getMessage());
    echo 'Erro crítico: ' . $e->getMessage();
}
?>