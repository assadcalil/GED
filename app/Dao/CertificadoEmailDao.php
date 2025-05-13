<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Classe para gerenciar o envio de emails com certificados digitais
 * Acessa o banco de dados para obter informações da empresa e configura o envio do email
 */
date_default_timezone_set('America/Sao_Paulo');
header("Content-type: text/html; charset=utf-8");

require_once(__DIR__ . '/../../...../app/Config/Database.php');
require_once(__DIR__ . '/../../...../app/Config/Logger.php');

// Importações do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../phpmailer/src/Exception.php';
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';

class EnviaEmailCertificadoDao {
    private $conn;
    
    /**
     * Construtor - inicializa a conexão com o banco de dados
     */
    public function __construct() {
        $this->conn = Database::getConnection();
    }
    
    /**
     * Busca informações da empresa pelo código
     * @param string $empCode Código da empresa
     * @return array|false Dados da empresa ou false se não encontrada
     */
    public function buscarEmpresa($empCode) {
        try {
            // Usando o método estático selectOne da classe Database
            $empresa = Database::selectOne(
                "SELECT * FROM empresas WHERE emp_code = ?", 
                [$empCode]
            );
            
            // Registrar a consulta no log
            if ($empresa) {
                Logger::database('select', 'empresas', $empCode, 'Busca de empresa para certificado');
                return $empresa;
            } else {
                Logger::warning("Empresa não encontrada para certificado: {$empCode}");
                return false;
            }
        } catch (PDOException $e) {
            Logger::error("Erro ao buscar empresa: " . $e->getMessage(), ['emp_code' => $empCode]);
            return false;
        }
    }
    
    /**
     * Envia email com certificado digital para os destinatários
     */
    public function enviarEmailCertificado($dados) {
        try {
            // Compatibilidade de nomes de campos
            if (isset($dados['certificado_tipo']) && !isset($dados['tipo_certificado'])) {
                $dados['tipo_certificado'] = $dados['certificado_tipo'];
            }
            
            // Verifica se recebeu todos os dados necessários
            if (!isset($dados['emp_code']) || 
                !isset($dados['tipo_certificado']) || 
                !isset($dados['data_renovacao']) || 
                !isset($dados['certificado_vencimento']) || 
                !isset($dados['emails_destinatario']) || 
                !isset($dados['arquivo_certificado'])) {
                    
                Logger::warning("Tentativa de envio de certificado com dados incompletos", $dados);
                return array('sucesso' => false, 'mensagem' => 'Dados incompletos para envio');
            }
            
            // Busca dados da empresa no banco
            $empresa = $this->buscarEmpresa($dados['emp_code']);
            if (!$empresa) {
                return array('sucesso' => false, 'mensagem' => 'Empresa não encontrada');
            }
            
            // Instância do objeto PHPMailer
            $mail = new PHPMailer(true); // Habilita exceções
            $mail->CharSet = 'UTF-8';
            
            // Configura para envio de e-mails usando SMTP
            $mail->isSMTP();
            
            // Configurações do SMTP
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'recuperacaoestrela@gmail.com';
            $mail->Password   = 'sgyrmsgdaxiqvupb';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            
            // Configurações do email
            $mail->setFrom(
                $dados['email_remetente'] ?? 'recuperacaoestrela@gmail.com', 
                $dados['nome_remetente'] ?? 'CONTABILIDADE ESTRELA'
            );
            
            // Destinatários principais
            $destinatarios = explode(',', $dados['emails_destinatario']);
            foreach ($destinatarios as $email) {
                $email = trim($email);
                if (!empty($email)) {
                    $mail->addAddress($email);
                }
            }
            
            // Destinatário em cópia (CC)
            $mail->addCC($dados['email_copia'] ?? 'cestrela.cancelar@terra.com.br');
            
            // Assunto do email
            $assunto = $dados['emp_code'] . ' - ' . $empresa['emp_name'] . ' - Certificado Digital';
            $mail->Subject = $assunto;
            
            // Corpo do email em HTML
            $mail->isHTML(true);
            $mail->Body = $this->gerarCorpoEmail($empresa, $dados);
            
            // Anexo (arquivo do certificado)
            if (isset($dados['arquivo_certificado']) && $dados['arquivo_certificado']['error'] === UPLOAD_ERR_OK) {
                $mail->addAttachment(
                    $dados['arquivo_certificado']['tmp_name'],
                    $dados['arquivo_certificado']['name']
                );
                
                // Registrar upload do certificado
                Logger::upload(
                    $dados['arquivo_certificado']['name'],
                    'sucesso',
                    'Certificado digital para anexo em email',
                    ['empresa' => $empresa['emp_name'], 'tipo' => $dados['tipo_certificado']]
                );
            }
            
            // Enviar email
            try {
                $mail->send();
                
                // Registrar log do envio
                $logData = [
                    'emp_code' => $dados['emp_code'],
                    'emp_name' => $empresa['emp_name'],
                    'tipo_certificado' => $dados['tipo_certificado'],
                    'data_renovacao' => $dados['data_renovacao'],
                    'certificado_vencimento' => $dados['certificado_vencimento'],
                    'emails_destinatario' => $dados['emails_destinatario'],
                    'nome_arquivo' => $dados['arquivo_certificado']['name'] ?? '',
                    'sucesso' => 1,
                    'mensagem_erro' => ''
                ];
                
                // Registrar usando o método especializado para certificados
                Logger::certificado('envio', "Certificado enviado com sucesso para {$empresa['emp_name']}", $logData);
                
                // Também registrar como email
                Logger::email(
                    $dados['emails_destinatario'],
                    $assunto,
                    'sucesso',
                    "Envio de certificado digital para {$empresa['emp_name']}",
                    ['tipo' => $dados['tipo_certificado']]
                );
                
                return array(
                    'sucesso' => true, 
                    'mensagem' => 'Email enviado com sucesso!'
                );
                
            } catch (Exception $e) {
                // Registrar log do erro
                $logData = [
                    'emp_code' => $dados['emp_code'],
                    'emp_name' => $empresa['emp_name'],
                    'tipo_certificado' => $dados['tipo_certificado'],
                    'data_renovacao' => $dados['data_renovacao'],
                    'certificado_vencimento' => $dados['certificado_vencimento'],
                    'emails_destinatario' => $dados['emails_destinatario'],
                    'nome_arquivo' => $dados['arquivo_certificado']['name'] ?? '',
                    'sucesso' => 0,
                    'mensagem_erro' => $e->getMessage()
                ];
                
                // Registrar usando o método especializado para certificados
                Logger::certificado('erro', "Falha ao enviar certificado para {$empresa['emp_name']}: {$e->getMessage()}", $logData);
                
                // Também registrar como erro de email
                Logger::email(
                    $dados['emails_destinatario'],
                    $assunto,
                    'falha',
                    "Falha no envio de certificado digital: {$e->getMessage()}",
                    ['empresa' => $empresa['emp_name'], 'tipo' => $dados['tipo_certificado']]
                );
                
                return array(
                    'sucesso' => false, 
                    'mensagem' => 'Erro ao enviar email: ' . $e->getMessage()
                );
            }
            
        } catch (Exception $e) {
            // Registrar erro no log
            Logger::error("Erro no processo de envio de certificado: " . $e->getMessage(), $dados ?? []);
            
            return array(
                'sucesso' => false, 
                'mensagem' => 'Erro ao enviar email: ' . $e->getMessage()
            );
        }
    }

    /**
     * Atualiza os dados do certificado de uma empresa
     * @param string $empCode Código da empresa
     * @param array $dadosCertificado Dados do certificado a serem atualizados
     * @return bool Indica se a atualização foi bem-sucedida
     */
    public function atualizarDadosCertificado($empCode, $dadosCertificado) {
        try {
            // Busca informações da empresa se o nome não estiver nos dados
            if (empty($dadosCertificado['emp_name'])) {
                $empresa = $this->buscarEmpresa($empCode);
                $empName = $empresa ? $empresa['emp_name'] : '';
            } else {
                $empName = $dadosCertificado['emp_name'];
            }
            
            // Determinar o tipo correto de certificado (A1 ou A3)
            if (strpos(strtoupper($dadosCertificado['tipo']), 'A1') !== false) {
                $tipo = 'A1';
            } elseif (strpos(strtoupper($dadosCertificado['tipo']), 'A3') !== false) {
                $tipo = 'A3';
            } else {
                // Para outros tipos como NF-e, CT-e, assumir A1 como padrão ou definir conforme regra de negócio
                $tipo = 'A1';
            }
            
            // Determinar a categoria correta
            if (strpos(strtoupper($dadosCertificado['tipo']), 'CNPJ') !== false) {
                $categoria = 'E-CNPJ';
            } elseif (strpos(strtoupper($dadosCertificado['tipo']), 'CPF') !== false) {
                $categoria = 'E-CPF';
            } else if (strpos(strtoupper($dadosCertificado['tipo']), 'NF-E') !== false) {
                $categoria = 'E-CNPJ'; // Certificados NF-e geralmente são do tipo E-CNPJ
            } else if (strpos(strtoupper($dadosCertificado['tipo']), 'CT-E') !== false) {
                $categoria = 'E-CNPJ'; // Certificados CT-e geralmente são do tipo E-CNPJ
            } else {
                $categoria = 'E-CNPJ'; // Valor padrão se não conseguir determinar
            }
            
            // Mapear situação para os valores corretos do enum
            if (strtoupper($dadosCertificado['situacao']) === 'PRÓXIMO DO VENCIMENTO') {
                $situacao = 'PRESTES_A_VENCER';
            } else {
                $situacao = strtoupper($dadosCertificado['situacao']);
            }
            
            // Certifique-se de que situacao seja um dos valores permitidos pelo enum
            $situacoesPermitidas = ['VIGENTE', 'VENCIDO', 'PRESTES_A_VENCER', 'RENOVADO'];
            if (!in_array($situacao, $situacoesPermitidas)) {
                $situacao = 'VIGENTE'; // Valor padrão em caso de situação não reconhecida
            }
            
            // Preparar a consulta SQL para atualizar a tabela certificado_digital
            $sql = "INSERT INTO certificado_digital 
                    (emp_code, emp_name, certificado_tipo, certificado_categoria, 
                     certificado_emissao, certificado_validade, certificado_situacao, 
                     certificado_titular, updated_at)
                    VALUES 
                    (:emp_code, :emp_name, :tipo, :categoria, 
                     :emissao, :validade, :situacao, 
                     :titular, NOW())
                    ON DUPLICATE KEY UPDATE 
                    certificado_tipo = :tipo,
                    certificado_categoria = :categoria,
                    certificado_emissao = :emissao,
                    certificado_validade = :validade,
                    certificado_situacao = :situacao,
                    updated_at = NOW()";
            
            // Determinar o titular do certificado
            $titular = $dadosCertificado['titular'] ?? $empName ?? '';
            
            // Preparar os parâmetros como um array para evitar problemas de vinculação
            $params = [
                ':emp_code' => $empCode,
                ':emp_name' => $empName,
                ':tipo' => $tipo,
                ':categoria' => $categoria,
                ':emissao' => $dadosCertificado['emissao'],
                ':validade' => $dadosCertificado['validade'],
                ':situacao' => $situacao,
                ':titular' => $titular
            ];
            
            // Registrar detalhes antes da execução para depuração
            Logger::debug("Parâmetros preparados para atualização do certificado", [
                'sql' => $sql,
                'params' => $params,
                'dadosOriginal' => $dadosCertificado
            ]);
            
            // Preparar e executar a declaração com array de parâmetros
            $stmt = $this->conn->prepare($sql);
            $resultado = $stmt->execute($params);
            
            // Verificar o número de linhas afetadas
            $linhasAfetadas = $stmt->rowCount();
            
            // Registrar log da atualização
            Logger::database('update', 'certificado_digital', $empCode, 'Atualização de dados do certificado', [
                'linhasAfetadas' => $linhasAfetadas,
                'dadosCertificado' => $dadosCertificado,
                'dadosProcessados' => [
                    'tipo' => $tipo,
                    'categoria' => $categoria,
                    'situacao' => $situacao,
                    'titular' => $titular,
                    'emp_name' => $empName
                ]
            ]);
            
            // Retornar true se pelo menos uma linha foi afetada
            return $linhasAfetadas > 0;
            
        } catch (PDOException $e) {
            // Registrar erro no log com mais detalhes
            Logger::error("Erro ao atualizar dados do certificado", [
                'empresa' => $empCode,
                'dados' => $dadosCertificado,
                'mensagemErro' => $e->getMessage(),
                'sqlError' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return false;
        }
    }
    
    
    /**
     * Gera o corpo HTML do email
     */
    private function gerarCorpoEmail($empresa, $dados) {
        // Formatar datas para exibição
        $dataRenovacao = date('d/m/Y', strtotime($dados['data_renovacao']));
        $certificadoVencimento = date('d/m/Y', strtotime($dados['certificado_vencimento']));
        $senhaCertificado = isset($dados['senha_certificado']) ? $dados['senha_certificado'] : '';
        
        // HTML do email
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body {
                    font-family: "Segoe UI", Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 650px;
                    margin: 0 auto;
                }
                .header {
                    background-color: #0078D4;
                    color: white;
                    padding: 25px;
                    border-radius: 8px 8px 0 0;
                    text-align: center;
                }
                .content {
                    padding: 25px;
                    background-color: #f9f9f9;
                    border-left: 1px solid #ddd;
                    border-right: 1px solid #ddd;
                }
                .section {
                    background-color: white;
                    padding: 20px;
                    margin-bottom: 20px;
                    border-radius: 6px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                }
                .info-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                }
                .info-item {
                    background-color: #f0f7ff;
                    padding: 12px;
                    border-radius: 5px;
                    border-left: 3px solid #0078D4;
                }
                .info-item.date {
                    border-left: 3px solid #e81123;
                }
                .info-label {
                    font-weight: bold;
                    color: #0078D4;
                    font-size: 14px;
                    margin-bottom: 3px;
                }
                .info-label.date {
                    color: #e81123;
                }
                .info-value {
                    color: #333;
                    font-size: 15px;
                }
                .info-value.date {
                    color: #e81123;
                    font-weight: bold;
                }
                .footer {
                    background-color: #f2f2f2;
                    padding: 15px 25px;
                    border-radius: 0 0 8px 8px;
                    font-size: 14px;
                    color: #555;
                    border-top: 3px solid #0078D4;
                }
                .signature {
                    margin-top: 30px;
                    padding-top: 15px;
                    border-top: 1px solid #eee;
                    font-size: 14px;
                }
                .senha-container {
                    text-align: center;
                    margin: 25px 0;
                }
                .senha-field {
                    background-color: #fff;
                    padding: 15px;
                    border-radius: 5px;
                    border: 2px dashed #e81123;
                    display: inline-block;
                    font-weight: bold;
                    color: #e81123;
                    font-size: 18px;
                }
                .vencimento-container {
                    text-align: center;
                    margin: 20px 0;
                }
                .vencimento-label {
                    font-weight: bold;
                    color: #e81123;
                    font-size: 16px;
                    margin-bottom: 5px;
                }
                .vencimento-value {
                    color: #e81123;
                    font-weight: bold;
                    font-size: 22px;
                }
                h2 {
                    color: #0078D4;
                    margin-top: 0;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Gerenciador Certificado Digital</h1>
                <p>CONTABILIDADE ESTRELA</p>
            </div>
            
            <div class="content">
                <p>Prezada Equipe da Contabilidade Estrela,</p>
                
                <p>Espero que estejam todos bem.</p>
                
                <p>Conforme combinado, estou enviando em anexo o certificado digital da empresa <b>' . $empresa['emp_name'] . '</b> para os procedimentos contábeis e fiscais necessários.</p>
                
                <div class="section">
                    <h2><center>Informações do Certificado</center></h2>
                    <div class="info-grid">
                        <!-- CNPJ e Razão Social primeiro -->
                        <div class="info-item">
                            <div class="info-label">CNPJ</div>
                            <div class="info-value">' . $empresa['emp_cnpj'] . '</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Razão Social</div>
                            <div class="info-value">' . $empresa['emp_code'] . ' - ' . $empresa['emp_name'] . '</div>
                        </div>
                        <!-- Tipo de Certificado -->
                        <div class="info-item">
                            <div class="info-label">Tipo de Certificado</div>
                            <div class="info-value">' . $dados['tipo_certificado'] . '</div>
                        </div>
                        <div class="info-item date">
                            <div class="info-label date">Data de Emissão</div>
                            <div class="info-value date">' . $dataRenovacao . '</div>
                        </div>
                    </div>
                    
                    <!-- Data de vencimento destacada no centro -->
                    <div class="vencimento-container">
                        <div class="vencimento-label">DATA DE VENCIMENTO</div>
                        <div class="vencimento-value">' . $certificadoVencimento . '</div>
                    </div>';
        
        // Adicionar a senha se estiver definida
        if (!empty($senhaCertificado)) {
            $html .= '
                    <!-- Senha do certificado destacada no centro -->
                    <div class="senha-container">
                        <div class="senha-field">SENHA: ' . $senhaCertificado . '</div>
                    </div>';
        }
        
        $html .= '
                </div>
                
                <p>Solicito, por gentileza, a confirmação do recebimento deste email e do arquivo anexo.</p>
                
                <p>Em caso de dúvidas ou necessidade de informações adicionais, estou à disposição através deste email ou pelo telefone (11) 2124-7070.</p>
                
                <div class="signature">
                    <strong>CONTABILIDADE ESTRELA</strong><br>
                    Setor Certificado Digital<br>
                    (11) 2124-7070<br>
                    cestrela.cancelar@terra.com.br
                </div>
            </div>
            
            <div class="footer">
                <p><center>Este email contém informações confidenciais. Por favor, trate com a devida segurança.</center></p>
            </div>
        </body>
        </html>
        ';
        
        return $html;
    }
}
?>