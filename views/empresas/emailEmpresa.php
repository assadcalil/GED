<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Script para envio de e-mail após cadastro de empresa
 * Arquivo: emailEmpresa.php
 */
class EmailEmpresa {
    // Variáveis para configuração de e-mail
    private $smtpHost = 'smtp.gmail.com';
    private $smtpPort = 465;
    private $smtpUser = 'recuperacaoestrela@gmail.com';
    private $smtpPass = 'sgyrmsgdaxiqvupb';
    private $smtpSecure = 'ssl';
    private $emailRemetente = 'recuperacaoestrela@gmail.com';
    private $nomeRemetente = 'CONTABILIDADE ESTRELA';
    private $emailCopia = 'cestrela.cancelar@terra.com.br';
    
    // Conexão com o banco de dados
    private $conn;
    
    /**
     * Construtor da classe
     * 
     * @param PDO $conn Conexão com o banco de dados
     */
    public function __construct($conn = null) {
        $this->conn = $conn;
    }
    
    /**
     * Busca dados da empresa no banco de dados
     * 
     * @param string $empCode Código da empresa
     * @return array|false Dados da empresa ou false se não encontrada
     */
    public function buscarEmpresa($empCode) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM empresas WHERE emp_code = :emp_code");
            $stmt->execute(array(':emp_code' => $empCode));
            
            // Verificando se encontrou a empresa
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                return false;
            }
        } catch (PDOException $e) {
            throw new Exception("Erro ao buscar empresa: " . $e->getMessage());
        }
    }
    
    /**
     * Envia e-mail de notificação após cadastro de empresa
     * 
     * @param array $empresaData Dados da empresa cadastrada
     * @return array Resultado da operação (sucesso/erro e mensagem)
     * @throws Exception Se ocorrer um erro no envio do e-mail
     */
    public function enviarEmailCadastro($empresaData) {
        // Verifica se o PHPMailer está disponível
        if (!file_exists(__DIR__ . '/../../phpmailer/PHPMailerAutoload.php')) {
            throw new Exception('Biblioteca PHPMailer não encontrada em: ' . __DIR__ . '/../../phpmailer/PHPMailerAutoload.php');
        }
        
        // Carrega a biblioteca PHPMailer
        require_once __DIR__ . '/../../phpmailer/PHPMailerAutoload.php';
        require_once __DIR__ . '/../../phpmailer/class.smtp.php';
        
        // Instância do objeto PHPMailer
        $mail = new PHPMailer(true); // true habilita exceções
        $mail->CharSet = 'UTF-8';
        
        try {
            // Configura para envio de e-mails usando SMTP
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUser;
            $mail->Password = $this->smtpPass;
            $mail->SMTPSecure = $this->smtpSecure;
            $mail->Port = $this->smtpPort;
            $mail->SMTPDebug = 0; // 0 = sem debug, 1 = mensagens do cliente, 2 = mensagens do cliente e servidor
            $mail->IsHTML(true);
            
            // Remetente
            $mail->From = $this->emailRemetente;
            $mail->FromName = $this->nomeRemetente;
            
            // Destinatário principal
            // Comentado para adicionar mais e-mails posteriormente
            $mail->addAddress($this->emailCopia);
            
            // Assunto do e-mail
            $assunto = $empresaData['emp_code'] . ' - ' . $empresaData['emp_name'] . ' - Cadastro de Empresa';
            $mail->Subject = $assunto;
            
            // Conteúdo do e-mail
            $mail->Body = $this->gerarConteudoEmailCadastro($empresaData);
            
            // Enviar e-mail
            $mail->send();
            return array('sucesso' => true, 'mensagem' => 'E-mail de cadastro enviado com sucesso');
        } catch (phpmailerException $e) {
            throw new Exception('Erro PHPMailer: ' . $mail->ErrorInfo . ' - ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Erro ao enviar e-mail: ' . $e->getMessage());
        }
    }
    
    /**
     * Envia e-mail de notificação após alteração de empresa
     * 
     * @param array $empresaData Dados da empresa alterada
     * @param array $alteracoes Lista de alterações realizadas
     * @return array Resultado da operação (sucesso/erro e mensagem)
     * @throws Exception Se ocorrer um erro no envio do e-mail
     */
    public function enviarEmailAlteracao($empresaData, $alteracoes = []) {
        // Verifica se o PHPMailer está disponível
        if (!file_exists(__DIR__ . '/../phpmailer/PHPMailerAutoload.php')) {
            throw new Exception('Biblioteca PHPMailer não encontrada em: ' . __DIR__ . '/../phpmailer/PHPMailerAutoload.php');
        }
        
        // Carrega a biblioteca PHPMailer
        require_once __DIR__ . '/../phpmailer/PHPMailerAutoload.php';
        require_once __DIR__ . '/../phpmailer/class.smtp.php';
        
        // Instância do objeto PHPMailer
        $mail = new PHPMailer(true); // true habilita exceções
        $mail->CharSet = 'UTF-8';
        
        try {
            // Configura para envio de e-mails usando SMTP
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUser;
            $mail->Password = $this->smtpPass;
            $mail->SMTPSecure = $this->smtpSecure;
            $mail->Port = $this->smtpPort;
            $mail->SMTPDebug = 0; // 0 = sem debug, 1 = mensagens do cliente, 2 = mensagens do cliente e servidor
            $mail->IsHTML(true);
            
            // Remetente
            $mail->From = $this->emailRemetente;
            $mail->FromName = $this->nomeRemetente;
            
            // Destinatário principal
            // Comentado para adicionar mais e-mails posteriormente
            $mail->addAddress($this->emailCopia);
            
            // Assunto do e-mail
            $assunto = $empresaData['emp_code'] . ' - ' . $empresaData['emp_name'] . ' - Alteração de Empresa';
            $mail->Subject = $assunto;
            
            // Conteúdo do e-mail
            $mail->Body = $this->gerarConteudoEmailAlteracao($empresaData, $alteracoes);
            
            // Enviar e-mail
            $mail->send();
            return array('sucesso' => true, 'mensagem' => 'E-mail de alteração enviado com sucesso');
        } catch (phpmailerException $e) {
            throw new Exception('Erro PHPMailer: ' . $mail->ErrorInfo . ' - ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Erro ao enviar e-mail: ' . $e->getMessage());
        }
    }
    
    /**
     * Gera o conteúdo HTML do e-mail de cadastro
     * 
     * @param array $empresaData Dados da empresa cadastrada
     * @return string Conteúdo HTML do e-mail
     */
    private function gerarConteudoEmailCadastro($empresaData) {
        // Tabela de dados da empresa
        $tabela = $this->gerarTabelaDadosEmpresa($empresaData);
        
        // Montar corpo do e-mail
        $html = '
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>Cadastro de Empresa</title>
        </head>
        <body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 0;">
            <div style="max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <div style="background-color: #004a85; padding: 15px; color: white; text-align: center; border-radius: 5px 5px 0 0;">
                    <h1 style="margin: 0; font-size: 24px;">Cadastro de Empresa - Sistema Contabilidade Estrela</h1>
                </div>
                
                <div style="padding: 20px; background-color: #f9f9f9;">
                    <p>Prezado(a),</p>
                    <p>Uma nova empresa foi cadastrada no sistema Contabilidade Estrela 2.0 com os seguintes dados:</p>
                    
                    ' . $tabela . '
                    
                    <p style="margin-top: 20px;">Este e-mail é enviado automaticamente pelo sistema. Em caso de dúvidas, entre em contato com a administração.</p>
                    
                    <p style="margin-top: 30px; text-align: center;">
                        <a href="https://contabilidadeestrela.com.br/ged2.0/" style="background-color: #004a85; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Acessar o Sistema</a>
                    </p>
                </div>
                
                <div style="background-color: #eeeeee; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 5px 5px;">
                    <p>© ' . date('Y') . ' Contabilidade Estrela. Todos os direitos reservados.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Gera o conteúdo HTML do e-mail de alteração
     * 
     * @param array $empresaData Dados da empresa alterada
     * @param array $alteracoes Lista de alterações realizadas
     * @return string Conteúdo HTML do e-mail
     */
    private function gerarConteudoEmailAlteracao($empresaData, $alteracoes = []) {
        // Tabela de dados da empresa
        $tabela = $this->gerarTabelaDadosEmpresa($empresaData);
        
        // Lista de alterações
        $listaAlteracoes = '';
        if (!empty($alteracoes)) {
            $listaAlteracoes = '<div style="margin: 20px 0; padding: 15px; background-color: #fff8e1; border-left: 5px solid #ffc107; border-radius: 3px;">
                <h3 style="margin-top: 0; color: #ff9800;">Alterações Realizadas</h3>
                <ul style="padding-left: 20px;">';
            
            foreach ($alteracoes as $alteracao) {
                $listaAlteracoes .= '<li>' . htmlspecialchars($alteracao) . '</li>';
            }
            
            $listaAlteracoes .= '</ul></div>';
        }
        
        // Montar corpo do e-mail
        $html = '
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>Alteração de Empresa</title>
        </head>
        <body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 0;">
            <div style="max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <div style="background-color: #ff9800; padding: 15px; color: white; text-align: center; border-radius: 5px 5px 0 0;">
                    <h1 style="margin: 0; font-size: 24px;">Alteração de Empresa - Sistema Contabilidade Estrela</h1>
                </div>
                
                <div style="padding: 20px; background-color: #f9f9f9;">
                    <p>Prezado(a),</p>
                    <p>Uma empresa foi alterada no sistema Contabilidade Estrela 2.0 com os seguintes dados:</p>
                    
                    ' . $tabela . '
                    
                    ' . $listaAlteracoes . '
                    
                    <p style="margin-top: 20px;">Este e-mail é enviado automaticamente pelo sistema. Em caso de dúvidas, entre em contato com a administração.</p>
                    
                    <p style="margin-top: 30px; text-align: center;">
                        <a href="https://contabilidadeestrela.com.br/ged2.0/" style="background-color: #ff9800; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Acessar o Sistema</a>
                    </p>
                </div>
                
                <div style="background-color: #eeeeee; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 5px 5px;">
                    <p>© ' . date('Y') . ' Contabilidade Estrela. Todos os direitos reservados.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Gera a tabela HTML com os dados da empresa
     * 
     * @param array $empresaData Dados da empresa
     * @return string HTML da tabela
     */
    private function gerarTabelaDadosEmpresa($empresaData) {
        $html = '
        <table cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse; margin: 20px 0; border: 1px solid #ddd;">
            <tr style="background-color: #f2f2f2;">
                <th colspan="2" style="padding: 10px; text-align: left; border: 1px solid #ddd;">Informações da Empresa</th>
            </tr>
            <tr>
                <td style="width: 30%; padding: 8px; border: 1px solid #ddd; font-weight: bold;">Código</td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($empresaData['emp_code']) . '</td>
            </tr>
            <tr>
                <td style="width: 30%; padding: 8px; border: 1px solid #ddd; font-weight: bold;">Razão Social</td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($empresaData['emp_name']) . '</td>
            </tr>
            <tr>
                <td style="width: 30%; padding: 8px; border: 1px solid #ddd; font-weight: bold;">CNPJ</td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($empresaData['emp_cnpj']) . '</td>
            </tr>
            <tr>
                <td style="width: 30%; padding: 8px; border: 1px solid #ddd; font-weight: bold;">Telefone</td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($empresaData['emp_tel']) . '</td>
            </tr>
            <tr>
                <td style="width: 30%; padding: 8px; border: 1px solid #ddd; font-weight: bold;">E-mail</td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($empresaData['email_empresa']) . '</td>
            </tr>
            <tr>
                <td style="width: 30%; padding: 8px; border: 1px solid #ddd; font-weight: bold;">Situação</td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($empresaData['emp_sit_cad']) . '</td>
            </tr>
            <tr>
                <td style="width: 30%; padding: 8px; border: 1px solid #ddd; font-weight: bold;">Tipo Jurídico</td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($empresaData['emp_tipo_jur']) . '</td>
            </tr>
            
            <tr style="background-color: #f2f2f2;">
                <th colspan="2" style="padding: 10px; text-align: left; border: 1px solid #ddd;">Sócio Principal</th>
            </tr>
            <tr>
                <td style="width: 30%; padding: 8px; border: 1px solid #ddd; font-weight: bold;">Nome</td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($empresaData['soc1_name']) . '</td>
            </tr>
            <tr>
                <td style="width: 30%; padding: 8px; border: 1px solid #ddd; font-weight: bold;">CPF</td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($empresaData['soc1_cpf']) . '</td>
            </tr>
            <tr>
                <td style="width: 30%; padding: 8px; border: 1px solid #ddd; font-weight: bold;">E-mail</td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($empresaData['soc1_email']) . '</td>
            </tr>
            <tr>
                <td style="width: 30%; padding: 8px; border: 1px solid #ddd; font-weight: bold;">Telefone</td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($empresaData['soc1_tel']) . '</td>
            </tr>
            
            <tr style="background-color: #f2f2f2;">
                <th colspan="2" style="padding: 10px; text-align: left; border: 1px solid #ddd;">Endereço</th>
            </tr>
            <tr>
                <td style="width: 30%; padding: 8px; border: 1px solid #ddd; font-weight: bold;">CEP</td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($empresaData['emp_cep']) . '</td>
            </tr>
            <tr>
                <td style="width: 30%; padding: 8px; border: 1px solid #ddd; font-weight: bold;">Endereço</td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($empresaData['emp_ende']) . ', ' . htmlspecialchars($empresaData['emp_nume']) . '</td>
            </tr>
            <tr>
                <td style="width: 30%; padding: 8px; border: 1px solid #ddd; font-weight: bold;">Complemento</td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($empresaData['emp_comp']) . '</td>
            </tr>
            <tr>
                <td style="width: 30%; padding: 8px; border: 1px solid #ddd; font-weight: bold;">Bairro</td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($empresaData['emp_bair']) . '</td>
            </tr>
            <tr>
                <td style="width: 30%; padding: 8px; border: 1px solid #ddd; font-weight: bold;">Cidade/UF</td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($empresaData['emp_cid']) . '/' . htmlspecialchars($empresaData['emp_uf']) . '</td>
            </tr>
        </table>';
        
        return $html;
    }
}
?>