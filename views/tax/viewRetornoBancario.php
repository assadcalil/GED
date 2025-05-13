<?php

// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Sistema Contabilidade Estrela 2.0
 * Processamento de Arquivos de Retorno Bancário - Caixa Econômica Federal
 */

// Verificar se as configurações já foram incluídas
if (!defined('ROOT_DIR')) {
    require_once __DIR__ . '/../../../...../app/Config/App.php';
    require_once __DIR__ . '/../../../...../app/Config/Database.php';
    require_once __DIR__ . '/../../../...../app/Config/Auth.php';
    require_once __DIR__ . '/../../../...../app/Config/Logger.php';
    require_once __DIR__ . '/../../../...../app/Dao/ImpostoDao.php';
}

// Verificar autenticação e permissão
Auth::requireLogin();

// Apenas administradores podem acessar a listagem geral de impostos
if (!Auth::isAdmin()) {
    header('Location: /Ged2.0/views/errors/access-denied.php');
    exit;
}

// Inicializar variáveis
$impostoDAO = new ImpostoDAO();
$message = '';
$messageType = '';
$uploadedFile = null;
$processedPayments = [];
$totalProcessed = 0;

// Configurações de paths
$retornoPath = ROOT_PATH . '/RetornoCaixa/';
$processedLogFile = $retornoPath . 'processed_files.log';
$processedDataFile = $retornoPath . 'documentos_processados.json';

// Garantir que os diretórios existem
if (!is_dir($retornoPath)) {
    mkdir($retornoPath, 0777, true);
}

// Adicionar informações de depuração
$debug_info = [
    'diretorio_retorno' => [
        'caminho' => $retornoPath,
        'existe' => is_dir($retornoPath) ? 'Sim' : 'Não',
        'permissao_escrita' => is_writable($retornoPath) ? 'Sim' : 'Não'
    ],
    'arquivo_log' => [
        'caminho' => $processedLogFile,
        'existe' => file_exists($processedLogFile) ? 'Sim' : 'Não',
        'tamanho' => file_exists($processedLogFile) ? filesize($processedLogFile) . ' bytes' : 'N/A'
    ]
];

// Funções auxiliares
function php_fnumber($var1) {
    return number_format($var1, 2, ',', '.');
}

function datasql($data1) {
    $data1 = substr($data1, 0, 2) . '/' . substr($data1, 2, 2) . '/' . substr($data1, 4, 4);
    if (!empty($data1)) {
        $p_dt = explode('/', $data1);
        $data_sql = $p_dt[2] . '-' . $p_dt[1] . '-' . $p_dt[0];
        return $data_sql;
    }
}

function datacx_databr($var1) {
    // Converter uma string data brasileira em uma data brasileira com as barras
    // Entrada: DDMMAAAA / Saida: DD/MM/AAAA
    $j_dia = substr($var1, 0, 2);
    $j_mes = substr($var1, 2, 2);
    $j_ano = substr($var1, 4, 4);
    $j_dtf = $j_dia . "/" . $j_mes . "/" . $j_ano;
    return $j_dtf;
}

function remove_zero_esq($var1) {
    $tam = strlen($var1);
    for ($i = 0; $i < $tam; $i++) {
        if (substr($var1, $i, 1) == "0") {
            $y = substr($var1, ($i + 1), ($tam));
        } else {
            return substr($var1, $i);
        }
    }
    return "0";
}

function numero_usa($var1) {
    $tam  = strlen($var1);
    $ped1 = substr($var1, 0, ($tam - 2));
    $ped2 = substr($var1, -2);
    $num2 = $ped1 . "." . $ped2;
    if ($num2 == ".") {
        $num2 = "0.00";
    }
    return $num2;
}

function motivo_liquidacao($var1) {
    $xfra = "";
    switch ($var1) {
        case "01": $xfra = " "; break;
        case "02": $xfra = "PG CASA LOTERICA"; break;
        case "03": $xfra = "PG AGENCIA CAIXA"; break;
        case "04": $xfra = "COMPENSACAO ELETRONICA"; break;
        case "05": $xfra = "COMPENSACAO CONVENCIONAL"; break;
        case "06": $xfra = "INTERNET BANKING"; break;
        case "07": $xfra = "CORRESPONDENTE BANCARIO"; break;
        case "08": $xfra = "EM CARTORIO"; break;
        case "61": $xfra = "PIX CAIXA"; break;
        case "62": $xfra = "PIX OUTROS BANCOS"; break;
        default: $xfra = "MOTIVO PG: " . $var1 . " (CONSULTAR MANUAL)"; break;
    }
    return ($xfra);
}

function motivo_rejeicao($var1) {
    $xfra = "";
    switch ($var1) {
        case "08": $xfra = "NOSSO NUMERO INVALIDO"; break;
        case "09": $xfra = "NOSSO NUMERO DUPLICADO"; break;
        case "48": $xfra = "CEP INVALIDO"; break;
        case "49": $xfra = "CEP SEM PRACA DE COBRANCA (NAO LOCALIZADO)"; break;
        case "50": $xfra = "CEP REFERENTE A UM BANCO CORRESPONDENTE"; break;
        case "51": $xfra = "CEP INCOMPATIVEL COM A UNIDADE DA FEDERACAO"; break;
        case "52": $xfra = "UNIDADE DA FEDERACAO INVALIDA"; break;
        case "87": $xfra = "NUMERO DA REMESSA INVALIDO"; break;
        case "63": $xfra = "ENTRADA PARA TITULO JA CADASTRADO"; break;
        case "16": $xfra = "DATA DE VENCIMENTO INVALIDA"; break;
        case "10": $xfra = "CARTEIRA INVALIDA"; break;
        case "06": $xfra = "NUMERO INSCRICAO DO BENEFICIARIO INVALIDO"; break;
        case "07": $xfra = "AG/CONTA/DV INVALIDOS"; break;
        default: $xfra = "ERRO: " . $var1 . " "; break;
    }
    return ($xfra);
}

/**
 * Function to get user information by imposto record
 * @param object $impostoDAO Database access object
 * @param int $id_imposto ID of the imposto record
 * @return array|null User information or null if not found
 */
function getUserByImposto($impostoDAO, $id_imposto) {
    // First get the user ID from the imposto record
    $stmt = $impostoDAO->runQuery("SELECT usuario FROM impostos WHERE id = :id_imposto");
    $stmt->execute(array(":id_imposto" => $id_imposto));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row || empty($row['usuario'])) {
        return null;
    }
    
    $usuario_id = $row['usuario'];
    
    // Now get the user details
    $stmt = $impostoDAO->runQuery("SELECT name, email FROM users WHERE id = :usuario_id");
    $stmt->execute(array(":usuario_id" => $usuario_id));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $user ?: null;
}

/**
 * Function to send payment notification email
 * @param array $cliente Client information (code, name, CPF)
 * @param array $payment Payment information (date, amount)
 * @param array $user User to notify (name, email)
 * @return bool Success status
 */
function sendPaymentNotificationEmail($cliente, $payment, $user) {
    try {
        // Verificar se o template existe
        $template_path = ROOT_PATH . '/templates/email_pagamento_notificacao.php';
        if (!file_exists($template_path)) {
            // Se o template não existir, criá-lo
            $template_content = '<?php

// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}

/**
 * Sistema Contabilidade Estrela 2.0
 * Template de Email para Notificação de Pagamento
 * Arquivo: templates/email_pagamento_notificacao.php
 */
class EmailTemplatePagamentoNotificacao {
    
    /**
     * Gera o HTML para o email de notificação de pagamento
     * 
     * @param array $cliente Dados do cliente (código, nome, CPF)
     * @param array $payment Dados do pagamento (data, valor, motivo)
     * @param array $user Dados do usuário destinatário
     * @return string HTML do email
     */
    public static function gerarHTML($cliente, $payment, $user) {
        // Format values for email
        $valor_formatado = "R$ " . number_format($payment["valor"], 2, ",", ".");
        
        $html = "<!DOCTYPE html>
        <html>
        <head>
            <title>Notificação de Pagamento</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #3498db; color: white; padding: 15px; text-align: center; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .footer { font-size: 12px; color: #777; margin-top: 20px; text-align: center; }
                table { width: 100%; border-collapse: collapse; }
                table, th, td { border: 1px solid #ddd; }
                th, td { padding: 10px; text-align: left; }
                th { background-color: #f2f2f2; }
            </style>
        </head>
        <body>
            <div class=\"container\">
                <div class=\"header\">
                    <h2>Notificação de Pagamento</h2>
                </div>
                <div class=\"content\">
                    <p>Olá " . htmlspecialchars($user["name"]) . ",</p>
                    <p>Informamos que o seguinte cliente efetuou o pagamento do boleto:</p>
                    
                    <table>
                        <tr>
                            <th>Código</th>
                            <td>" . htmlspecialchars($cliente["codigo"]) . "</td>
                        </tr>
                        <tr>
                            <th>Nome</th>
                            <td>" . htmlspecialchars($cliente["nome"]) . "</td>
                        </tr>
                        <tr>
                            <th>CPF</th>
                            <td>" . htmlspecialchars($cliente["cpf"]) . "</td>
                        </tr>
                        <tr>
                            <th>Data do Pagamento</th>
                            <td>" . htmlspecialchars($payment["data_pagamento"]) . "</td>
                        </tr>
                        <tr>
                            <th>Valor Pago</th>
                            <td>" . htmlspecialchars($valor_formatado) . "</td>
                        </tr>
                        <tr>
                            <th>Forma de Pagamento</th>
                            <td>" . htmlspecialchars($payment["motivo"]) . "</td>
                        </tr>
                    </table>
                    
                    <p>Este email é uma notificação automática. O status do boleto já foi atualizado no sistema.</p>
                </div>
                <div class=\"footer\">
                    <p>Sistema Contabilidade Estrela 2.0 &copy; " . date("Y") . "</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }
}
?>';
            
            // Criar diretório templates se não existir
            $template_dir = dirname($template_path);
            if (!is_dir($template_dir)) {
                mkdir($template_dir, 0777, true);
            }
            
            // Salvar o template
            file_put_contents($template_path, $template_content);
        }
        
        // Criar diretório logs para rastreamento do erro se não existir
        $logs_dir = ROOT_PATH . '/logs';
        if (!is_dir($logs_dir)) {
            mkdir($logs_dir, 0777, true);
        }
        
        // Arquivo de log para rastrear problemas
        $log_file = $logs_dir . '/email_debug.log';
        
        // Log de início
        $log_message = date('Y-m-d H:i:s') . " - Iniciando envio de email para {$user['name']} ({$user['email']}) - Cliente: {$cliente['codigo']}\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
        
        // Incluir o template
        require_once $template_path;
        
        // Verificar se temos PHPMailer disponível
        $phpmailer_path = ROOT_PATH . '/phpmailer/class.phpmailer.php';
        $phpmailer_smtp_path = ROOT_PATH . '/phpmailer/class.smtp.php';
        
        if (file_exists($phpmailer_path) && file_exists($phpmailer_smtp_path)) {
            // Log de sucesso PHPMailer
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - PHPMailer encontrado: $phpmailer_path\n", FILE_APPEND);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - SMTP encontrado: $phpmailer_smtp_path\n", FILE_APPEND);
            
            // Incluir PHPMailer
            require_once $phpmailer_path;
            require_once $phpmailer_smtp_path;
            
            try {
                // Inicializar PHPMailer
                $mail = new PHPMailer(true); // true para habilitar exceções
                
                // Configurar SMTP
                $mail->IsSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'recuperacaoestrela@gmail.com';
                $mail->Password = 'sgyrmsgdaxiqvupb';
                $mail->SMTPSecure = 'ssl';
                $mail->Port = 465;
                $mail->CharSet = 'UTF-8';
                
                // Configurar timeout maior
                $mail->Timeout = 60;
                
                // Para depuração
                $mail->SMTPDebug = 1;
                $mail->Debugoutput = function($str, $level) use ($log_file) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - DEBUG[$level]: $str\n", FILE_APPEND);
                };
                
                // Opções SSL para evitar erros de certificado
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
                
                // Configurar remetente/destinatário
                $mail->setFrom('recuperacaoestrela@gmail.com', 'Contabilidade Estrela');
                $mail->addAddress($user['email'], $user['name']);
                
                // Assunto e conteúdo
                $mail->Subject = "Notificação de Pagamento - Cliente {$cliente['codigo']}";
                $mail->isHTML(true);
                $mail->Body = EmailTemplatePagamentoNotificacao::gerarHTML($cliente, $payment, $user);
                
                // Enviar email
                $mail->Send();
                
                // Log de sucesso
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Email enviado com sucesso!\n", FILE_APPEND);
                
                if (class_exists('Logger')) {
                    Logger::activity('email', "Email enviado com sucesso para {$user['name']} ({$user['email']}) - Cliente: {$cliente['codigo']}");
                }
                
                return true;
            } catch (Exception $e) {
                // Log de erro detalhado
                $error_message = date('Y-m-d H:i:s') . " - ERRO PHPMailer: " . $e->getMessage() . "\n";
                file_put_contents($log_file, $error_message, FILE_APPEND);
                
                if (class_exists('Logger')) {
                    Logger::activity('erro', "Falha ao enviar email via PHPMailer: " . $e->getMessage());
                }
                
                // Tentar método alternativo (mail nativo)
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Tentando método alternativo (mail nativo)\n", FILE_APPEND);
                
                // Usar abordagem alternativa com mail() do PHP
                return sendEmailAlternative($cliente, $payment, $user, $log_file);
            }
        } else {
            // Log de falha PHPMailer
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - PHPMailer não encontrado em: $phpmailer_path\n", FILE_APPEND);
            
            if (class_exists('Logger')) {
                Logger::activity('erro', "PHPMailer não encontrado em: $phpmailer_path");
            }
            
            // Usar abordagem alternativa com mail() do PHP
            return sendEmailAlternative($cliente, $payment, $user, $log_file);
        }
    }
    catch (Exception $e) {
        // Capturar qualquer exceção e registrar
        if (class_exists('Logger')) {
            Logger::activity('erro', "Exceção ao enviar notificação: " . $e->getMessage());
        }
        
        // Tentativa de log, mesmo se falhar
        $logs_dir = ROOT_PATH . '/logs';
        $log_file = $logs_dir . '/email_debug.log';
        if (is_dir($logs_dir)) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - EXCEÇÃO: " . $e->getMessage() . "\n", FILE_APPEND);
        }
        
        return false;
    }
}

/**
 * Função alternativa para envio de email usando mail() nativo
 */
function sendEmailAlternative($cliente, $payment, $user, $log_file) {
    try {
        // Gerar o corpo do email
        require_once ROOT_PATH . '/templates/email_pagamento_notificacao.php';
        $mensagem = EmailTemplatePagamentoNotificacao::gerarHTML($cliente, $payment, $user);
        
        // Define o assunto
        $assunto = "Notificação de Pagamento - Cliente {$cliente['codigo']}";
        
        // Define os cabeçalhos
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Contabilidade Estrela <recuperacaoestrela@gmail.com>" . "\r\n";
        $headers .= "Reply-To: recuperacaoestrela@gmail.com" . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        // Log da tentativa
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Tentando enviar para: {$user['email']}\n", FILE_APPEND);
        
        // Tentativa de envio
        $emailSent = mail($user['email'], $assunto, $mensagem, $headers);
        
        // Log do resultado
        if ($emailSent) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Email enviado com sucesso via mail()!\n", FILE_APPEND);
            
            if (class_exists('Logger')) {
                Logger::activity('email', "Email enviado com sucesso via mail() para {$user['name']} ({$user['email']}) - Cliente: {$cliente['codigo']}");
            }
        } else {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Falha ao enviar email via mail()!\n", FILE_APPEND);
            
            if (class_exists('Logger')) {
                Logger::activity('erro', "Falha ao enviar email via mail() para {$user['name']} ({$user['email']}) - Cliente: {$cliente['codigo']}");
            }
        }
        
        return $emailSent;
    } catch (Exception $e) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - EXCEÇÃO no método alternativo: " . $e->getMessage() . "\n", FILE_APPEND);
        
        if (class_exists('Logger')) {
            Logger::activity('erro', "Exceção no método alternativo: " . $e->getMessage());
        }
        
        return false;
    }
}

// Controle de arquivos processados
function loadProcessedFiles($logFile) {
    if (file_exists($logFile)) {
        $content = file_get_contents($logFile);
        return json_decode($content, true) ?: [];
    }
    return [];
}

function saveProcessedFile($logFile, $filename, $fileContent, $totalProcessado, $totalValor) {
    $processed = loadProcessedFiles($logFile);
    
    // Criar hash do conteúdo para verificação única
    $fileHash = md5($fileContent);
    
    $processed[$filename] = [
        'processed_at' => date('Y-m-d H:i:s'),
        'file_hash' => $fileHash,
        'status' => 'processed',
        'user' => $_SESSION['user_type'] . ': ' . ($_SESSION['user'] ?? $_SESSION['username'] ?? 'Sistema'),
        'total_registros' => $totalProcessado,
        'total_valor' => $totalValor
    ];
    
    // Adicionar também pelo hash para verificação cruzada
    $processed[$fileHash] = $processed[$filename];
    
    file_put_contents($logFile, json_encode($processed, JSON_PRETTY_PRINT));
    
    return $processed[$filename];
}

function isFileProcessed($logFile, $filename, $fileContent = null) {
    $processed = loadProcessedFiles($logFile);
    
    // Verifica pelo nome do arquivo
    if (isset($processed[$filename])) {
        return $processed[$filename];
    }
    
    // Verifica pelo hash do conteúdo (se fornecido)
    if ($fileContent) {
        $fileHash = md5($fileContent);
        if (isset($processed[$fileHash])) {
            return $processed[$fileHash];
        }
    }
    
    return false;
}

// Processar arquivo de retorno
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo'])) {
    $arquivo = $_FILES['arquivo'];
    
    // Verificar erros no upload
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $messageType = 'danger';
        switch ($arquivo['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $message = 'O arquivo é muito grande.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = 'O upload do arquivo foi feito parcialmente.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = 'Nenhum arquivo foi enviado.';
                break;
            default:
                $message = 'Erro desconhecido no upload.';
        }
    } 
    // Verificar extensão
    elseif (pathinfo($arquivo['name'], PATHINFO_EXTENSION) != 'ret') {
        $messageType = 'danger';
        $message = 'O arquivo deve ter a extensão .ret';
    } 
    // Verificar se já foi processado
    else {
        $fileContent = file_get_contents($arquivo['tmp_name']);
        $processingInfo = isFileProcessed($processedLogFile, $arquivo['name'], $fileContent);
        
        if ($processingInfo) {
            $messageType = 'warning';
            $processedTime = date('d/m/Y H:i:s', strtotime($processingInfo['processed_at']));
            $message = "ATENÇÃO: Este arquivo já foi processado anteriormente em {$processedTime}.<br>"
                     . "Nome do arquivo: <strong>" . htmlspecialchars($arquivo['name']) . "</strong>";
            
            if (isset($processingInfo['total_registros']) && isset($processingInfo['total_valor'])) {
                $message .= "<br>Total processado: " . $processingInfo['total_registros'] 
                         . " registros | R$ " . number_format($processingInfo['total_valor'], 2, ',', '.');
            }
        } 
        else {
            // Salvar arquivo com nome único para processamento
            $nameRetorno = md5(date('Y-m-dH:i:s') . rand(10000, 99999)) . '.ret';
            $uploadPath = $retornoPath . $nameRetorno;
            
            if (move_uploaded_file($arquivo['tmp_name'], $uploadPath)) {
                // Processar o arquivo
                $processResult = processarArquivoRetorno($uploadPath, $arquivo['name']);
                
                if ($processResult['success']) {
                    $messageType = 'success';
                    $message = "Arquivo processado com sucesso! Foram processados " . $processResult['totalProcessed'] 
                             . " pagamentos, totalizando R$ " . number_format($processResult['totalAmount'], 2, ',', '.');
                    
                    // Registrar o arquivo como processado
                    saveProcessedFile($processedLogFile, $arquivo['name'], $fileContent, $processResult['totalProcessed'], $processResult['totalAmount']);
                    
                    // Atribuir resultados para exibir na tela
                    $processedPayments = $processResult['payments'];
                    $totalProcessed = $processResult['totalAmount'];
                    $uploadedFile = $arquivo['name'];

                    // Armazenar na sessão para uso posterior na impressão
                    $_SESSION['last_processed_payments'] = $processResult['payments'];
                    $_SESSION['last_processed_total'] = $processResult['totalAmount'];

                    // Opcional: Armazenar também em um arquivo temporário para redundância
                    $jsonData = json_encode([
                        'payments' => $processResult['payments'],
                        'total' => $processResult['totalAmount']
                    ]);
                    file_put_contents($retornoPath . 'documentos_processados.json', $jsonData);
                    
                    // Registrar no log de atividades
                    Logger::activity('financeiro', "Processou arquivo de retorno: {$arquivo['name']} - Total: R$ " . number_format($processResult['totalAmount'], 2, ',', '.'));
                } else {
                    $messageType = 'danger';
                    $message = "Erro ao processar o arquivo: " . $processResult['error'];
                    
                    // Remover arquivo em caso de erro
                    @unlink($uploadPath);
                    
                    // Registrar no log de erros
                    Logger::activity('erro', "Erro ao processar arquivo de retorno {$arquivo['name']}: " . $processResult['error']);
                }
            } else {
                $messageType = 'danger';
                $message = "Erro ao salvar o arquivo.";
            }
        }
    }
}

// Função para processar o arquivo de retorno
function processarArquivoRetorno($filePath, $originalFilename) {
    global $impostoDAO;
    
    $result = [
        'success' => false,
        'error' => '',
        'totalProcessed' => 0,
        'totalAmount' => 0,
        'payments' => []
    ];
    
    // Abrir arquivo
    $lendo = @fopen($filePath, "r");
    if (!$lendo) {
        $result['error'] = "Erro ao abrir o arquivo.";
        return $result;
    }
    
    $i = 1;
    $total_itens_processados = 0;
    $total_recebido = 0;
    $pagamentos = [];
    $b = 4; // Offset para leitura
    
    // Ler arquivo linha por linha
    while (!feof($lendo)) {
        $linha = fgets($lendo, 241);
        $rr = "<pre>" . $linha . "</pre>";
        $xtamanho_linha = strlen($linha);
        
        if ($xtamanho_linha == 240) {
            // Variáveis para segmentos T e U
            static $nosso_numero_alex = null;
            static $nosso_numero_caixa = null;
            static $nosso_num = null;
            static $vencimento = null;
            static $valor_nominal = null;
            static $cod_movimento = null;
            static $xfrase_movimento = null;
            static $bg_color = null;
            static $frase_motivo = null;
            
            // Processa linha segmento T
            if ($i > 2 && substr($rr, $b + 14, 1) == "T" && substr($rr, $b + 16, 2) != 28) {
                $num_sequencial_t       = substr($rr, $b + 9, 5);
                $modalidade_nosso_numero = substr($rr, $b + 40, 2);
                $nosso_numero_caixa     = substr($rr, $b + 42, 15);
                $nosso_num              = substr($rr, $b + 43, 14);
                $nosso_numero_alex      = remove_zero_esq($nosso_num);
                $vencimento             = substr($rr, $b + 74, 8);
                $vm                     = substr($rr, $b + 82, 15);
                $valor_nominal          = numero_usa(remove_zero_esq($vm));
                $cod_movimento          = substr($rr, $b + 16, 2);
                
                switch ($cod_movimento) {
                    case "06":
                        $xfrase_movimento = "TITULO LIQUIDADO";
                        $bg_color = "#98FB98"; // verde
                        $cod_motivo_liquidacao = substr($rr, $b + 214, 10);
                        $frase_motivo = motivo_liquidacao(substr(trim($cod_motivo_liquidacao), -2));
                        break;
                    case "02":
                        $xfrase_movimento = "REMESSA ENTRADA CONFIRMADA";
                        $bg_color = "#FFF"; // branco
                        break;
                    case "03":
                        $xfrase_movimento = "REMESSA ENTRADA REJEITADA";
                        $bg_color = "#FFC4C4"; // vermelho
                        $cod_motivo_rejeicao = substr($rr, $b + 214, 10);
                        $frase_motivo = motivo_rejeicao(substr(trim($cod_motivo_rejeicao), -2));
                        break;
                    case "28":
                        $xfrase_movimento = "DEBITO DE TARIFAS/CUSTAS";
                        break;
                    case "27":
                        $xfrase_movimento = "CONFIRMACAO DO PEDIDO DE ALTERACAO OUTROS DADOS";
                        break;
                    case "30":
                        $xfrase_movimento = "ALTERACAO DE DADOS REJEITADA";
                        break;
                    case "45":
                        $xfrase_movimento = "ALTERACAO DE DADOS";
                        break;
                }
            }
            
            // Processa linha segmento U
            if ($i > 3 && substr($rr, $b + 14, 1) == "U" && substr($rr, $b + 16, 2) != 28) {
                $total_itens_processados++;
                
                $cod_movimento_u         = $cod_movimento;
                $num_sequencial_u        = substr($rr, $b + 9, 5);
                $jumu                    = substr($rr, $b + 18, 15);
                $juros_multa             = numero_usa(remove_zero_esq($jumu));
                $desco                   = substr($rr, $b + 33, 15);
                $desconto                = numero_usa(remove_zero_esq($desco));
                $vp                      = substr($rr, $b + 78, 15);
                $valor_pago              = numero_usa(remove_zero_esq($vp));
                $vl                      = substr($rr, $b + 93, 15);
                $valor_liquido           = numero_usa(remove_zero_esq($vl));
                $outdes                  = substr($rr, $b + 108, 15);
                $outras_despesas         = numero_usa(remove_zero_esq($outdes));
                $data_ocorrencia         = substr($rr, $b + 138, 8);
                $data_credito            = substr($rr, $b + 146, 8);
                $data_deb_tarifa         = substr($rr, $b + 158, 8);
                
                if ($cod_movimento_u == "06") { // título liquidado (pago)
                    // Pegando o ID do imposto
                    $id_imposto = remove_zero_esq(substr($nosso_numero_alex, 4, 10));
                    
                    // Consultando o banco de dados para pegar dados do imposto
                    $stmt = $impostoDAO->runQuery("SELECT id, codigo, nome, cpf, usuario, valor2025 FROM impostos WHERE id=:id_imposto");
                    $stmt->execute(array(":id_imposto" => $id_imposto));
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($row) {
                        $id_imposto = $row['id'];
                        
                        // Processar e formatar valores
                        $valor_banco1 = str_replace('.', '', php_fnumber($valor_pago));
                        $valor_banco = str_replace(',', '.', $valor_banco1);
                        
                        // Acumular o valor para o total
                        $total_recebido += $valor_banco;
                        
                        // Formatar data
                        $explode_data = explode('/', datacx_databr($data_ocorrencia));
                        $data_banco = $explode_data[2] . '-' . $explode_data[1] . '-' . $explode_data[0];
                        
                        // Verificar se o valor pago está correto (com uma margem de tolerância)
                        $valor_esperado = (float)$row['valor2025'];
                        $valor_pago_float = (float)$valor_banco;
                        $diferenca = abs($valor_esperado - $valor_pago_float);
                        $tolerancia = 1.00; // Tolerância de R$ 1,00 para mais ou para menos
                        
                        // Se a diferença for maior que a tolerância, registrar um alerta
                        $observacao = "";
                        if ($diferenca > $tolerancia) {
                            $observacao = "ALERTA: Valor pago (R$ " . number_format($valor_pago_float, 2, ',', '.') . 
                                         ") diferente do valor esperado (R$ " . number_format($valor_esperado, 2, ',', '.') . ")";
                        }
                        
                        // 1. Atualizar a tabela impostos
                        try {
                            $stmt = $impostoDAO->runQuery("
                                UPDATE impostos 
                                SET status_boleto_2025 = '1', 
                                    data_pagamento_2025 = :data_banco, 
                                    valor_pagamento_2025 = :valor_banco 
                                WHERE id = :id_imposto");
                            $stmt->execute(array(
                                ":data_banco" => $data_banco,
                                ":valor_banco" => $valor_banco,
                                ":id_imposto" => $id_imposto
                            ));
                            
                            // Obter informações do usuário para notificação por email
                            $user = getUserByImposto($impostoDAO, $id_imposto);
                            $emailSent = false;
                            $emailInfo = '';
                            
                            // Se encontrou usuário, enviar e-mail de notificação
                            if ($user && !empty($user['email'])) {
                                $cliente = [
                                    'codigo' => $row['codigo'],
                                    'nome' => $row['nome'],
                                    'cpf' => $row['cpf']
                                ];
                                
                                $paymentInfo = [
                                    'data_pagamento' => datacx_databr($data_ocorrencia),
                                    'valor' => $valor_banco,
                                    'motivo' => $frase_motivo
                                ];
                                
                                $emailSent = sendPaymentNotificationEmail($cliente, $paymentInfo, $user);
                                
                                // Adicionar informações sobre a notificação por email
                                $emailInfo = $emailSent 
                                    ? " | Notificação enviada para {$user['name']} ({$user['email']})" 
                                    : " | Falha ao enviar notificação para {$user['name']} ({$user['email']})";
                                
                                // Logar a tentativa de envio de email
                                Logger::activity(
                                    'email', 
                                    "Notificação de pagamento: Cliente #{$row['codigo']} - {$row['nome']} - R$ " . 
                                    number_format($valor_banco, 2, ',', '.') . 
                                    " - Email para: {$user['name']} ({$user['email']}) - " . 
                                    ($emailSent ? "Enviado" : "Falha")
                                );
                            }
                            
                            // 2. Atualizar a tabela impostos_boletos
                            // Primeiro buscar o ID do boleto correspondente
                            $stmt = $impostoDAO->runQuery("
                                SELECT id, status, linha_digitavel, observacoes  
                                FROM impostos_boletos 
                                WHERE imposto_id = :id_imposto AND status = 5
                                ORDER BY created_at DESC 
                                LIMIT 1");
                            $stmt->execute(array(":id_imposto" => $id_imposto));
                            $boleto = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            $boleto_atualizado = false;
                            if ($boleto) {
                                // Atualizar o boleto correspondente
                                $novaObservacao = trim(($boleto['observacoes'] ? $boleto['observacoes'] . ' | ' : '') . $observacao . 
                                                   ' | Pagamento via retorno bancário. Arquivo: ' . $originalFilename .
                                                   ' | Processado em: ' . date('d/m/Y H:i:s') . 
                                                   ' | Tipo: ' . $frase_motivo . $emailInfo);
                                
                                $stmt = $impostoDAO->runQuery("
                                    UPDATE impostos_boletos 
                                    SET status = 1, 
                                        observacoes = :observacoes,
                                        updated_at = NOW() 
                                    WHERE id = :id_boleto");
                                $stmt->execute(array(
                                    ":observacoes" => $novaObservacao,
                                    ":id_boleto" => $boleto['id']
                                ));
                                
                                $boleto_atualizado = true;
                            }
                            
                            // Adicionar ao array de pagamentos processados
                            $pagamentos[] = [
                                'id' => $row['id'],
                                'codigo' => $row['codigo'],
                                'nome' => $row['nome'],
                                'cpf' => $row['cpf'],
                                'valor' => $valor_banco,
                                'valor_formatado' => 'R$ ' . number_format($valor_banco, 2, ',', '.'),
                                'data_pagamento' => datacx_databr($data_ocorrencia),
                                'data_credito' => datacx_databr($data_credito),
                                'motivo' => $frase_motivo,
                                'boleto_atualizado' => $boleto_atualizado,
                                'observacao' => $observacao,
                                'status' => 'success',
                                'email_sent' => $emailSent ?? false,
                                'email_recipient' => isset($user) && !empty($user['email']) ? $user['email'] : 'N/A'
                            ];
                            
                        } catch (Exception $e) {
                            // Adicionar ao array de pagamentos com erro
                            $pagamentos[] = [
                                'id' => $row['id'],
                                'codigo' => $row['codigo'],
                                'nome' => $row['nome'],
                                'cpf' => $row['cpf'],
                                'valor' => $valor_banco,
                                'valor_formatado' => 'R$ ' . number_format($valor_banco, 2, ',', '.'),
                                'data_pagamento' => datacx_databr($data_ocorrencia),
                                'data_credito' => datacx_databr($data_credito),
                                'motivo' => $frase_motivo,
                                'boleto_atualizado' => false,
                                'observacao' => 'ERRO: ' . $e->getMessage(),
                                'status' => 'danger',
                                'email_sent' => false,
                                'email_recipient' => 'N/A'
                            ];
                        }
                    } else {
                        // Imposto não encontrado na base
                        $pagamentos[] = [
                            'id' => 'N/A',
                            'codigo' => 'N/A',
                            'nome' => 'ID NÃO ENCONTRADO: ' . $id_imposto,
                            'cpf' => 'N/A',
                            'valor' => $valor_pago,
                            'valor_formatado' => 'R$ ' . number_format($valor_pago, 2, ',', '.'),
                            'data_pagamento' => datacx_databr($data_ocorrencia),
                            'data_credito' => datacx_databr($data_credito),
                            'motivo' => $frase_motivo,
                            'boleto_atualizado' => false,
                            'observacao' => 'Imposto não encontrado na base de dados',
                            'status' => 'warning',
                            'email_sent' => false,
                            'email_recipient' => 'N/A'
                        ];
                    }
                }
            }
            $i++;
        }
    }
    
    // Fechar o arquivo
    fclose($lendo);
    
    // Atualizar resultado
    $result['success'] = true;
    $result['totalProcessed'] = count($pagamentos);
    $result['totalAmount'] = $total_recebido;
    $result['payments'] = $pagamentos;
    
    return $result;
}

// Obter histórico de arquivos processados
$historico = loadProcessedFiles($processedLogFile);

// Ordenar histórico por data de processamento (mais recente primeiro)
if (!empty($historico)) {
    uasort($historico, function($a, $b) {
        if (isset($a['processed_at']) && isset($b['processed_at'])) {
            return strtotime($b['processed_at']) - strtotime($a['processed_at']);
        }
        return 0;
    });
    
    // Limitar aos últimos 30 registros para exibição
    $historico = array_slice($historico, 0, 30, true);
}

// Filtrar histórico para remover registros de hash
$historico_filtrado = [];
foreach ($historico as $chave => $valor) {
    if (strlen($chave) !== 32 || !ctype_xdigit($chave)) {
        $historico_filtrado[$chave] = $valor;
    }
}
$historico = $historico_filtrado;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retorno Bancário - Caixa Econômica Federal - <?php echo SITE_NAME; ?></title>
    
    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
    
    <!-- Estilo personalizado -->
    <link rel="stylesheet" href="/GED2.0/assets/css/dashboard.css">
    
    <style>
        .upload-area {
            background-color: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            margin-bottom: 25px;
            transition: all 0.3s;
        }
        
        .upload-area:hover {
            border-color: #3498db;
            background-color: #e9f7fe;
        }
        
        .upload-icon {
            font-size: 48px;
            color: #3498db;
            margin-bottom: 15px;
        }
        
        .custom-file-upload {
            display: inline-block;
            cursor: pointer;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .custom-file-upload:hover {
            background: #2980b9;
        }
        
        #fileUploadForm {
            margin-bottom: 30px;
        }
        
        .nav-tabs .nav-link.active {
            font-weight: 600;
            border-bottom: 3px solid #3498db;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 600;
        }
        
        .history-card {
            transition: all 0.3s;
            border-left: 4px solid #3498db;
        }
        
        .history-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .file-info {
            font-size: 0.9em;
            color: #666;
        }
        
        .result-table th {
            background-color: #f5f5f5;
        }
        
        .result-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .result-header {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: white;
            padding: 15px 20px;
        }
        
        .result-body {
            padding: 20px;
        }
        
        .tag {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: 600;
            margin-right: 5px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            color: #3498db;
            font-weight: bold;
        }
        
        .payment-row-success {
            background-color: rgba(40, 167, 69, 0.1);
        }
        
        .payment-row-danger {
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .payment-row-warning {
            background-color: rgba(255, 193, 7, 0.1);
        }

        @media print {
        /* Esconder elementos não desejados na impressão */
        .dashboard-container .sidebar,
        .dashboard-container .header,
        .dashboard-footer,
        .btn,
        .nav-tabs,
        .page-header {
            display: none !important;
        }
        
        /* Ajustar o conteúdo para impressão */
        .main-content {
            margin-left: 0 !important;
            width: 100% !important;
        }
        
        /* Layout específico para impressão */
        .print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .print-footer {
            display: block !important;
            text-align: right;
            margin-top: 20px;
            font-size: 11px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        /* Ajustar a tabela para impressão */
        #paymentsTable th, 
        #paymentsTable td {
            font-size: 11px !important;
            padding: 5px !important;
        }
        
        /* Esconder colunas desnecessárias para impressão */
        #paymentsTable th:nth-child(6), 
        #paymentsTable td:nth-child(6),
        #paymentsTable th:nth-child(7), 
        #paymentsTable td:nth-child(7) {
            display: none;
        }
    }
    
    /* Elementos ocultos normalmente, mostrados apenas na impressão */
    .print-only {
        display: none;
    }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .container-fluid {
                width: 100%;
                max-width: 100%;
            }
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
                                <h1 class="page-title">Processamento de Retorno Bancário</h1>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="viewListagemImpostos.php">Imposto de Renda</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Retorno Bancário</li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informações de depuração 
                    <div class="alert alert-info mb-4">
                        <h5><i class="fas fa-info-circle"></i> Informações de sistema</h5>
                        <p><strong>Diretório RetornoCaixa:</strong> <?php echo $debug_info['diretorio_retorno']['caminho']; ?> (<?php echo $debug_info['diretorio_retorno']['existe']; ?> | Permissão de escrita: <?php echo $debug_info['diretorio_retorno']['permissao_escrita']; ?>)</p>
                        <p><strong>Arquivo de log:</strong> <?php echo $debug_info['arquivo_log']['caminho']; ?> (<?php echo $debug_info['arquivo_log']['existe']; ?> | <?php echo $debug_info['arquivo_log']['tamanho']; ?>)</p>
                        <p><strong>Histórico:</strong> <?php echo count($historico); ?> arquivo(s) processado(s)</p>
                    </div>-->
                    
                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Botões para navegar entre as abas manualmente -->
                    <div class="mb-3">
                        <button class="btn btn-outline-primary" onclick="mostrarAba('upload')">Upload</button>
                        <button class="btn btn-outline-primary" onclick="mostrarAba('history')">Histórico</button>
                        <?php if (!empty($processedPayments)): ?>
                        <button class="btn btn-outline-primary" onclick="mostrarAba('result')">Resultados</button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Conteúdo das Abas -->
                    <div class="tab-content">
                        <!-- Aba de Upload -->
                        <div class="tab-pane" id="upload">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-file-invoice-dollar me-2"></i>
                                        Upload de Arquivo de Retorno
                                    </h5>
                                    <p class="card-text text-muted">
                                        Faça o upload do arquivo de retorno bancário da Caixa Econômica Federal (formato .ret) para processar os pagamentos automaticamente.
                                    </p>
                                    
                                    <form id="fileUploadForm" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" class="mt-4">
                                        <div class="card mb-4">
                                            <div class="card-body text-center">
                                                <div class="upload-icon mb-3">
                                                    <i class="fas fa-file-upload fa-3x text-primary"></i>
                                                </div>
                                                <h5>Selecione o arquivo de retorno bancário</h5>
                                                <p class="text-muted mb-4">Formato aceito: arquivo .ret da Caixa Econômica Federal</p>
                                                
                                                <div class="input-group mb-3 w-75 mx-auto">
                                                    <input type="file" class="form-control" id="arquivo" name="arquivo" accept=".ret">
                                                    <button class="btn btn-primary" type="submit">
                                                        <i class="fas fa-upload me-2"></i> Processar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    
                                    <div class="alert alert-info mt-4">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Importante:</strong> O sistema irá atualizar automaticamente o status dos boletos para "Pago" 
                                        com base nos registros do arquivo de retorno. Certifique-se de que o arquivo é válido e gerado pelo 
                                        sistema bancário da Caixa Econômica Federal.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Aba de Histórico -->
                        <div class="tab-pane" id="history">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-history me-2"></i>
                                        Histórico de Processamentos
                                    </h5>
                                    <p class="card-text text-muted">
                                        Veja os últimos arquivos de retorno processados no sistema.
                                    </p>
                                    
                                    <?php if (empty($historico)): ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Nenhum arquivo de retorno foi processado ainda.
                                        </div>
                                    <?php else: ?>
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Arquivo</th>
                                                    <th>Data de Processamento</th>
                                                    <th>Usuário</th>
                                                    <th>Pagamentos</th>
                                                    <th>Valor Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($historico as $filename => $info): 
                                                    // Pular registros de hash MD5
                                                    if (strlen($filename) === 32 && ctype_xdigit($filename)) continue;
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($filename); ?></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($info['processed_at'] ?? date('Y-m-d H:i:s'))); ?></td>
                                                    <td><?php echo htmlspecialchars($info['user'] ?? 'Sistema'); ?></td>
                                                    <td>
                                                        <span class="badge bg-success">
                                                            <?php echo number_format($info['total_registros'] ?? 0, 0, ',', '.'); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary">
                                                            R$ <?php echo number_format($info['total_valor'] ?? 0, 2, ',', '.'); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Aba de Resultados (aparece apenas após o processamento) -->
                        <?php if (!empty($processedPayments)): ?>
                        <div class="tab-pane" id="result">
                            <div class="result-card">
                                <div class="result-header">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h5 class="mb-1">
                                                <i class="fas fa-file-invoice me-2"></i>
                                                <?php echo htmlspecialchars($uploadedFile); ?>
                                            </h5>
                                            <p class="mb-0">
                                                <i class="fas fa-calendar-check me-2"></i>
                                                Processado em <?php echo date('d/m/Y H:i'); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-4 text-md-end">
                                            <h4>R$ <?php echo number_format($totalProcessed, 2, ',', '.'); ?></h4>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>
                                                <?php echo count($processedPayments); ?> pagamento(s)
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="result-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover" id="paymentsTable">
                                            <thead>
                                                <tr>
                                                    <th>CODIGO</th>
                                                    <th>Cliente</th>
                                                    <th>CPF</th>
                                                    <th>Valor</th>
                                                    <th>Data Pgto</th>
                                                    <th>Status</th>
                                                    <th>Local</th>
                                                    <th>Notificação</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($processedPayments as $payment): ?>
                                                <tr class="payment-row-<?php echo $payment['status']; ?>">
                                                    <td><?php echo htmlspecialchars($payment['codigo']); ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($payment['nome']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($payment['cpf']); ?></td>
                                                    <td>R$ <?php echo number_format((float)$payment['valor'], 2, ',', '.'); ?></td>
                                                    <td><?php echo htmlspecialchars($payment['data_pagamento']); ?></td>
                                                    <td>
                                                        <?php if ($payment['status'] === 'success'): ?>
                                                            <span class="badge bg-success">Pago</span>
                                                        <?php elseif ($payment['status'] === 'danger'): ?>
                                                            <span class="badge bg-danger">Erro</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning text-dark">Atenção</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($payment['motivo']); ?>
                                                        <?php if (!empty($payment['observacao'])): ?>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($payment['observacao']); ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (isset($payment['email_sent'])): ?>
                                                            <?php if ($payment['email_sent']): ?>
                                                                <span class="badge bg-success">
                                                                    <i class="fas fa-envelope me-1"></i> Enviado
                                                                </span>
                                                                <div class="small text-muted">
                                                                    <?php echo htmlspecialchars($payment['email_recipient']); ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger">
                                                                    <i class="fas fa-exclamation-triangle me-1"></i> Falha
                                                                </span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">
                                                                <i class="fas fa-minus me-1"></i> N/A
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mt-4">
                                    <button class="btn btn-secondary" onclick="generatePDF()">
                                        <i class="fas fa-print me-2"></i> Imprimir Relatório
                                    </button>
                                        <a href="viewListagemImpostos.php" class="btn btn-primary">
                                            <i class="fas fa-arrow-left me-2"></i> Voltar para Listagem
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
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
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap Bundle com Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    
    <!-- Script personalizado -->
    <script src="/GED2.0/assets/js/dashboard.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar DataTables
        if (document.getElementById('paymentsTable')) {
            $('#paymentsTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
                },
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel me-2"></i>Excel',
                        className: 'btn btn-success'
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf me-2"></i>PDF',
                        className: 'btn btn-danger'
                    }
                ],
                pageLength: 25
            });
        }
        
        // Mostrar a aba de upload por padrão
        mostrarAba('upload');
        
        // Se houver resultados, mostrar a aba de resultados
        <?php if (!empty($processedPayments)): ?>
            mostrarAba('result');
        <?php endif; ?>
    });
    
    // Função para mostrar aba específica
    function mostrarAba(id) {
        // Esconder todas as abas
        document.querySelectorAll('.tab-pane').forEach(function(tab) {
            tab.style.display = 'none';
        });
        
        // Mostrar a aba selecionada
        document.getElementById(id).style.display = 'block';
        
        // Destacar o botão ativo
        document.querySelectorAll('.btn-outline-primary').forEach(function(btn) {
            btn.classList.remove('active');
        });
        
        // Encontrar e ativar o botão correspondente
        document.querySelectorAll('.btn-outline-primary').forEach(function(btn) {
            if (btn.getAttribute('onclick').includes(id)) {
                btn.classList.add('active');
            }
        });
    }
    </script>
    <!-- Adicionar bibliotecas jsPDF -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

</body>
<script>
    function generatePDF() {
        // Importar jsPDF
        const { jsPDF } = window.jspdf;
        
        // Criar novo documento PDF
        const doc = new jsPDF();
        
        // Adicionar cabeçalho
        doc.setFontSize(16);
        doc.setFont('helvetica', 'bold');
        doc.text('CONTABILIDADE ESTRELA', doc.internal.pageSize.width / 2, 15, { align: 'center' });
        
        doc.setFontSize(12);
        doc.setFont('helvetica', 'normal');
        doc.text('Imposto de Renda 2025', doc.internal.pageSize.width / 2, 22, { align: 'center' });
        
        // Preparar os dados da tabela
        const tableColumn = ["ID", "Código", "Nome", "CPF", "Valor Pago", "Data Pagamento", "Data Ocorrência", "Data Crédito", "Usuário", "Email"];
        const tableRows = [];
        
        // Obter os dados da tabela na página
        const table = document.getElementById('paymentsTable');
        const rows = table.querySelectorAll('tbody tr');
        
        let totalValue = 0;
        
        // Extrair dados da tabela
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            
            // Extrair dados básicos
            const id = cells[0].textContent.trim();
            
            // Extrair código e nome do cliente
            let codigo = 'AVULSO'; // Valor padrão
            let nome = cells[1].textContent.trim();
            
            // Verificar se há tags span para código
            const codeTag = cells[1].querySelector('.tag');
            if (codeTag) {
                codigo = codeTag.textContent.replace('#', '').trim();
                nome = cells[1].textContent.replace(codeTag.textContent, '').trim();
            }
            
            const cpf = cells[2].textContent.trim();
            
            // Extrair valor e converter para número
            const valorText = cells[3].textContent.replace('R$', '').trim();
            const valor = parseFloat(valorText.replace('.', '').replace(',', '.'));
            totalValue += valor;
            
            // Extrair data de pagamento
            const dataPagamento = cells[4].textContent.trim();
            
            // Extrair informações de notificação por email
            let emailStatus = '';
            if (cells[7]) {
                const emailBadge = cells[7].querySelector('.badge');
                if (emailBadge) {
                    emailStatus = emailBadge.textContent.trim();
                }
                const emailAddress = cells[7].querySelector('.small');
                if (emailAddress) {
                    emailStatus += ' - ' + emailAddress.textContent.trim();
                }
            }
            
            // Obter usuário atual
            const userName = '<?php echo $_SESSION["user"] ?? $_SESSION["username"] ?? "SISTEMA"; ?>';
            
            // Adicionar linha à tabela
            tableRows.push([
                id,
                codigo,
                nome,
                cpf,
                "R$ " + valor.toFixed(2).replace('.', ','),
                dataPagamento,
                dataPagamento, // Data ocorrência igual à data de pagamento
                dataPagamento, // Data crédito (poderia ser diferente, mas sem essa informação usamos a mesma)
                userName,
                emailStatus
            ]);
        });
        
        // Adicionar a tabela ao PDF
        doc.autoTable({
            head: [tableColumn],
            body: tableRows,
            startY: 30,
            theme: 'grid',
            styles: {
                fontSize: 8,
                cellPadding: 3
            },
            headStyles: {
                fillColor: [220, 220, 220],
                textColor: [0, 0, 0],
                fontStyle: 'bold'
            }
        });
        
        // Adicionar linha de total
        const finalY = doc.lastAutoTable.finalY;
        
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9);
        
        // Adicionar rodapé com total
        doc.text('TOTAL GERAL RECEBIDO:', 120, finalY + 8, { align: 'right' });
        doc.text('R$ ' + totalValue.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}), 130, finalY + 8);
        
        // Adicionar informações de processamento
        doc.setFontSize(8);
        doc.setFont('helvetica', 'normal');
        const currentDate = new Date().toLocaleString('pt-BR');
        doc.text('Processado por: Contabilidade Estrela - <?php echo $_SESSION["user"] ?? $_SESSION["username"] ?? "SISTEMA"; ?> ' + currentDate, 190, finalY + 20, { align: 'right' });
        
        // Adicionar informações sobre notificações por e-mail
        doc.text('Notificações por e-mail enviadas automaticamente para os responsáveis pelos clientes.', 10, finalY + 30);
        
        // Salvar o PDF
        doc.save('Retorno_Bancario_<?php echo date('Y-m-d'); ?>.pdf');
    }
</script>
</html>