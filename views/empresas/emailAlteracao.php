<?php
/**
 * Classe para gerenciar o envio de e-mails relacionados a empresas
 */
class EmailAlteracao {
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
     * Construtor
     * 
     * @param PDO $conn Conexão com o banco de dados
     */
    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Envia e-mail de notificação de cadastro de empresa
     * 
     * @param array $empresaData Dados da empresa cadastrada
     * @return bool
     */
    public function enviarEmailCadastro($empresaData) {
        $assunto = "Nova Empresa Cadastrada: {$empresaData['emp_name']}";
        
        $mensagem = $this->criarTemplateEmailCadastro($empresaData);
        
        return $this->enviarEmail($assunto, $mensagem, $empresaData);
    }

    /**
     * Envia e-mail de notificação de atualização de empresa
     * 
     * @param array $empresaData Dados atualizados da empresa
     * @param int $id ID da empresa
     * @return bool
     */
    public function enviarEmailAtualizacao($empresaData, $id) {
        $assunto = "Empresa Atualizada: {$empresaData['emp_name']}";
        
        // Obter dados antigos da empresa para comparação
        $dadosAntigos = $this->obterDadosAntigos($id);
        
        $mensagem = $this->criarTemplateEmailAtualizacao($empresaData, $dadosAntigos);
        
        return $this->enviarEmail($assunto, $mensagem, $empresaData);
    }

    /**
     * Obtém os dados antigos da empresa antes da atualização
     * 
     * @param int $id ID da empresa
     * @return array
     */
    private function obterDadosAntigos($id) {
        $query = "SELECT * FROM empresas WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cria o template HTML do e-mail de cadastro
     * 
     * @param array $empresaData Dados da empresa
     * @return string
     */
    private function criarTemplateEmailCadastro($empresaData) {
        // Template HTML para e-mail de cadastro
        $html = '
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Nova Empresa Cadastrada</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 10px; text-align: center; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
                table { width: 100%; border-collapse: collapse; }
                table, th, td { border: 1px solid #ddd; }
                th, td { padding: 10px; text-align: left; }
                th { background-color: #f2f2f2; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Nova Empresa Cadastrada</h1>
                </div>
                <div class="content">
                    <p>Uma nova empresa foi cadastrada no sistema:</p>
                    
                    <table>
                        <tr>
                            <th>Código</th>
                            <td>' . htmlspecialchars($empresaData['emp_code']) . '</td>
                        </tr>
                        <tr>
                            <th>Razão Social</th>
                            <td>' . htmlspecialchars($empresaData['emp_name']) . '</td>
                        </tr>
                        <tr>
                            <th>CNPJ</th>
                            <td>' . htmlspecialchars($empresaData['emp_cnpj']) . '</td>
                        </tr>
                        <tr>
                            <th>Situação</th>
                            <td>' . htmlspecialchars($empresaData['emp_sit_cad']) . '</td>
                        </tr>
                        <tr>
                            <th>Responsável</th>
                            <td>' . htmlspecialchars($empresaData['name']) . '</td>
                        </tr>
                        <tr>
                            <th>E-mail</th>
                            <td>' . htmlspecialchars($empresaData['email_empresa']) . '</td>
                        </tr>
                    </table>
                    
                    <p>Para mais detalhes, acesse o sistema.</p>
                </div>
                <div class="footer">
                    <p>Este é um e-mail automático do Sistema Contabilidade Estrela.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }

    /**
     * Cria o template HTML do e-mail de atualização
     * 
     * @param array $empresaData Dados atualizados da empresa
     * @param array $dadosAntigos Dados antigos da empresa
     * @return string
     */
    private function criarTemplateEmailAtualizacao($empresaData, $dadosAntigos) {
        // Verificar campos alterados
        $camposAlterados = [];
        
        foreach ($empresaData as $campo => $valor) {
            if (isset($dadosAntigos[$campo]) && $dadosAntigos[$campo] != $valor) {
                $camposAlterados[$campo] = [
                    'antigo' => $dadosAntigos[$campo],
                    'novo' => $valor
                ];
            }
        }
        
        // Template HTML para e-mail de atualização
        $html = '
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Empresa Atualizada</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2196F3; color: white; padding: 10px; text-align: center; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                table, th, td { border: 1px solid #ddd; }
                th, td { padding: 10px; text-align: left; }
                th { background-color: #f2f2f2; }
                .alterado { background-color: #fff9c4; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Empresa Atualizada</h1>
                </div>
                <div class="content">
                    <p>A empresa <strong>' . htmlspecialchars($empresaData['emp_name']) . '</strong> (Código: ' . htmlspecialchars($empresaData['emp_code']) . ') foi atualizada no sistema.</p>
                    
                    <h3>Dados da Empresa:</h3>
                    <table>
                        <tr>
                            <th>Código</th>
                            <td>' . htmlspecialchars($empresaData['emp_code']) . '</td>
                        </tr>
                        <tr>
                            <th>Razão Social</th>
                            <td>' . htmlspecialchars($empresaData['emp_name']) . '</td>
                        </tr>
                        <tr>
                            <th>CNPJ</th>
                            <td>' . htmlspecialchars($empresaData['emp_cnpj']) . '</td>
                        </tr>
                        <tr>
                            <th>Situação</th>
                            <td>' . htmlspecialchars($empresaData['emp_sit_cad']) . '</td>
                        </tr>
                        <tr>
                            <th>Responsável</th>
                            <td>' . htmlspecialchars($empresaData['name']) . '</td>
                        </tr>
                        <tr>
                            <th>E-mail</th>
                            <td>' . htmlspecialchars($empresaData['email_empresa']) . '</td>
                        </tr>
                    </table>';
        
        // Adicionar tabela de campos alterados se houver alterações
        if (!empty($camposAlterados)) {
            $html .= '
                    <h3>Campos Alterados:</h3>
                    <table>
                        <tr>
                            <th>Campo</th>
                            <th>Valor Anterior</th>
                            <th>Novo Valor</th>
                        </tr>';
            
            foreach ($camposAlterados as $campo => $valores) {
                // Ignorar alguns campos internos ou técnicos
                if (in_array($campo, ['id', 'data', 'usuario', 'pasta'])) {
                    continue;
                }
                
                $html .= '
                        <tr>
                            <td>' . $this->getNomeCampo($campo) . '</td>
                            <td>' . htmlspecialchars($valores['antigo'] ?? '') . '</td>
                            <td>' . htmlspecialchars($valores['novo'] ?? '') . '</td>
                        </tr>';
            }
            
            $html .= '
                    </table>';
        }
        
        $html .= '
                    <p>Atualizado por: ' . htmlspecialchars($empresaData['usuario']) . '</p>
                    <p>Para mais detalhes, acesse o sistema.</p>
                </div>
                <div class="footer">
                    <p>Este é um e-mail automático do Sistema Contabilidade Estrela.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }

    /**
     * Obtém o nome amigável do campo
     * 
     * @param string $campo Nome do campo no banco de dados
     * @return string
     */
    private function getNomeCampo($campo) {
        $nomesCampos = [
            'emp_code' => 'Código',
            'emp_name' => 'Razão Social',
            'emp_cnpj' => 'CNPJ',
            'emp_sit_cad' => 'Situação',
            'emp_tel' => 'Telefone',
            'emp_iest' => 'Inscrição Estadual',
            'emp_imun' => 'Inscrição Municipal',
            'emp_reg_apu' => 'Regime de Apuração',
            'emp_porte' => 'Porte',
            'emp_tipo_jur' => 'Tipo Jurídico',
            'emp_nat_jur' => 'Natureza Jurídica',
            'emp_cep' => 'CEP',
            'emp_ende' => 'Endereço',
            'emp_nume' => 'Número',
            'emp_comp' => 'Complemento',
            'emp_bair' => 'Bairro',
            'emp_cid' => 'Cidade',
            'emp_uf' => 'UF',
            'emp_org_reg' => 'Órgão de Registro',
            'emp_reg_nire' => 'NIRE',
            'emp_ult_reg' => 'Data Última Alteração',
            'emp_cod_ace' => 'Código de Acesso',
            'emp_cod_pre' => 'Código de Prova',
            'senha_pfe' => 'Senha PFE',
            'emp_cer_dig_data' => 'Validade Certificado',
            'name' => 'Responsável',
            'email_empresa' => 'E-mail da Empresa',
            // Sócio 1
            'soc1_name' => 'Nome do Sócio 1',
            'soc1_cpf' => 'CPF do Sócio 1',
            'soc1_entrada' => 'Data Entrada Sócio 1',
            'soc1_email' => 'E-mail do Sócio 1',
            'soc1_tel' => 'Telefone do Sócio 1',
            'soc1_cel' => 'Celular do Sócio 1',
            // Adicione os demais campos conforme necessário
        ];
        
        return $nomesCampos[$campo] ?? $campo;
    }

    /**
     * Envia o e-mail usando a função mail do PHP
     * 
     * @param string $assunto Assunto do e-mail
     * @param string $mensagem Conteúdo HTML do e-mail
     * @param array $empresaData Dados da empresa
     * @return bool
     * @throws Exception
     */
    private function enviarEmail($assunto, $mensagem, $empresaData) {
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
            // Configurações do servidor
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUser;
            $mail->Password = $this->smtpPass;
            $mail->SMTPSecure = $this->smtpSecure;
            $mail->Port = $this->smtpPort;
            $mail->CharSet = 'UTF-8';
            
            // Remetente
            $mail->setFrom($this->emailRemetente, $this->nomeRemetente);
            
            // Destinatários
            $mail->addAddress($this->emailCopia); // Sempre enviar para o administrador
            
            // Se a empresa tiver e-mail, adicionar como cópia
            if (!empty($empresaData['email_empresa'])) {
                $mail->addCC($empresaData['email_empresa']);
            }
            
            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body = $mensagem;
            $mail->AltBody = strip_tags($mensagem);
            
            // Enviar
            $mail->send();
            return true;
        } catch (Exception $e) {
            throw new Exception("Não foi possível enviar o e-mail: " . $mail->ErrorInfo);
        }
    }
}