<?php
// Salve como smtp_diagnostico.php na raiz do projeto

// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Carregar configuração de email
require_once __DIR__ . 'app/Config/Email.php';

echo "<html><head><title>Diagnóstico SMTP</title>";
echo "<style>
    body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
    .success { color: green; background: #e6ffe6; padding: 10px; border: 1px solid green; }
    .error { color: red; background: #ffe6e6; padding: 10px; border: 1px solid red; }
    .warning { color: orange; background: #fff3e6; padding: 10px; border: 1px solid orange; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow: auto; }
</style></head><body>";

echo "<h1>Diagnóstico SMTP - Contabilidade Estrela</h1>";

// Obter configurações
$config = EmailConfig::getConfig();
$smtp_host = $config['smtp']['host'];
$smtp_port = $config['smtp']['port'];
$smtp_user = $config['smtp']['user'];
$smtp_pass = $config['smtp']['pass'];
$smtp_secure = $config['smtp']['secure'];
$remetente_email = $config['remetente']['email'];
$remetente_nome = $config['remetente']['nome'];

// Exibir configurações (sem mostrar a senha completa)
echo "<h2>Configurações atuais</h2>";
echo "<pre>";
echo "Host SMTP: $smtp_host\n";
echo "Porta SMTP: $smtp_port\n";
echo "Usuário SMTP: $smtp_user\n";
echo "Senha SMTP: " . substr($smtp_pass, 0, 3) . "..." . substr($smtp_pass, -3) . "\n";
echo "Segurança SMTP: $smtp_secure\n";
echo "Email Remetente: $remetente_email\n";
echo "Nome Remetente: $remetente_nome\n";
echo "</pre>";

// Testar conectividade básica
echo "<h2>Teste 1: Conectividade básica ao servidor SMTP</h2>";
try {
    // Tentar estabelecer uma conexão básica primeiro
    echo "Tentando conectar a $smtp_host:$smtp_port...<br>";
    
    $errno = 0;
    $errstr = '';
    $timeout = 5;
    
    if ($smtp_secure == 'ssl') {
        $socket = @fsockopen("ssl://$smtp_host", $smtp_port, $errno, $errstr, $timeout);
    } else {
        $socket = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, $timeout);
    }
    
    if (!$socket) {
        echo "<div class='error'>FALHA: Não foi possível conectar ao servidor SMTP. Erro: $errstr ($errno)</div>";
        echo "<p>Possíveis causas:</p>";
        echo "<ul>";
        echo "<li>O servidor está bloqueando conexões externas</li>";
        echo "<li>A porta $smtp_port está bloqueada por um firewall</li>";
        echo "<li>O servidor SMTP está temporariamente indisponível</li>";
        echo "<li>Configuração incorreta da porta ou host SMTP</li>";
        echo "</ul>";
    } else {
        echo "<div class='success'>SUCESSO: Conexão básica ao servidor SMTP estabelecida!</div>";
        fclose($socket);
    }
} catch (Exception $e) {
    echo "<div class='error'>EXCEÇÃO: " . $e->getMessage() . "</div>";
}

// Verificar disponibilidade do PHPMailer
echo "<h2>Teste 2: Verificação do PHPMailer</h2>";
$phpmailer_paths = [
    __DIR__ . '/phpmailer/PHPMailerAutoload.php',
    __DIR__ . '/lib/PHPMailer/PHPMailerAutoload.php',
    __DIR__ . '/vendor/phpmailer/phpmailer/PHPMailerAutoload.php',
    // Adicione mais caminhos se necessário
];

$phpmailer_encontrado = false;
$phpmailer_path = '';

foreach ($phpmailer_paths as $path) {
    if (file_exists($path)) {
        $phpmailer_encontrado = true;
        $phpmailer_path = $path;
        break;
    }
}

if (!$phpmailer_encontrado) {
    echo "<div class='error'>FALHA: Biblioteca PHPMailer não encontrada nos caminhos verificados</div>";
    echo "<p>Procurei em:</p>";
    echo "<ul>";
    foreach ($phpmailer_paths as $path) {
        echo "<li>" . htmlspecialchars($path) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<div class='success'>SUCESSO: PHPMailer encontrado em: " . htmlspecialchars($phpmailer_path) . "</div>";
    
    // Carregar PHPMailer
    require_once $phpmailer_path;
    
    // Verificar se a classe PHPMailer existe
    if (!class_exists('PHPMailer')) {
        echo "<div class='error'>FALHA: A classe PHPMailer não foi carregada corretamente</div>";
    } else {
        echo "<div class='success'>SUCESSO: A classe PHPMailer foi carregada corretamente</div>";
    }
}

// Testar envio real de email se PHPMailer estiver disponível
if ($phpmailer_encontrado && class_exists('PHPMailer')) {
    echo "<h2>Teste 3: Tentativa de autenticação e envio de email</h2>";
    
    echo "<form method='post' action=''>";
    echo "<p><label>Email para teste: <input type='email' name='test_email' value='" . 
         htmlspecialchars($_POST['test_email'] ?? '') . "' required></label></p>";
    echo "<p><input type='submit' name='send_test' value='Enviar email de teste'></p>";
    echo "</form>";
    
    if (isset($_POST['send_test']) && !empty($_POST['test_email'])) {
        $test_email = filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL);
        
        if (!$test_email) {
            echo "<div class='error'>Email inválido. Por favor, forneça um endereço de email válido.</div>";
        } else {
            try {
                $mail = new PHPMailer(true);
                
                // Definir modo de debug para capturar todas as mensagens
                $mail->SMTPDebug = 2;
                $mail->Debugoutput = function($str, $level) {
                    echo "<pre>" . htmlspecialchars($str) . "</pre>";
                };
                
                // Configurações SMTP
                $mail->isSMTP();
                $mail->Host = $smtp_host;
                $mail->SMTPAuth = true;
                $mail->Username = $smtp_user;
                $mail->Password = $smtp_pass;
                $mail->SMTPSecure = $smtp_secure;
                $mail->Port = $smtp_port;
                $mail->CharSet = 'UTF-8';
                
                // Configurações opcionais que podem ajudar a resolver problemas
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
                $mail->Timeout = 30; // Aumentar timeout
                
                // Remetente e destinatário
                $mail->setFrom($remetente_email, $remetente_nome);
                $mail->addAddress($test_email);
                
                // Conteúdo do email
                $mail->isHTML(true);
                $mail->Subject = 'Teste SMTP da Contabilidade Estrela - ' . date('d/m/Y H:i:s');
                $mail->Body = '
                    <html>
                    <body>
                        <h2>Teste de Email</h2>
                        <p>Este é um email de teste enviado em ' . date('d/m/Y H:i:s') . '</p>
                        <p>Se você recebeu este email, a configuração SMTP está funcionando corretamente.</p>
                    </body>
                    </html>
                ';
                
                // Enviar o email
                if ($mail->send()) {
                    echo "<div class='success'>SUCESSO: Email de teste enviado para $test_email</div>";
                } else {
                    echo "<div class='error'>FALHA: Não foi possível enviar o email. Erro: " . $mail->ErrorInfo . "</div>";
                }
            } catch (Exception $e) {
                echo "<div class='error'>EXCEÇÃO: " . $e->getMessage() . "</div>";
            }
        }
    }
}

// Teste alternativo com mail() do PHP
echo "<h2>Teste 4: Função mail() nativa do PHP</h2>";

if (function_exists('mail')) {
    echo "<p>A função mail() está disponível no PHP.</p>";
    
    if (isset($_POST['send_test']) && !empty($_POST['test_email'])) {
        $test_email = filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL);
        
        if ($test_email) {
            $subject = 'Teste Alternativo - Contabilidade Estrela - ' . date('d/m/Y H:i:s');
            $message = "Este é um email de teste alternativo enviado em " . date('d/m/Y H:i:s');
            $headers = "From: $remetente_email\r\n";
            $headers .= "Reply-To: $remetente_email\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            if (mail($test_email, $subject, $message, $headers)) {
                echo "<div class='success'>SUCESSO: Email de teste alternativo enviado para $test_email via função mail()</div>";
            } else {
                echo "<div class='error'>FALHA: Não foi possível enviar o email alternativo via função mail()</div>";
            }
        }
    } else {
        echo "<p>Use o formulário acima para testar também a função mail() nativa.</p>";
    }
} else {
    echo "<div class='error'>A função mail() não está disponível nesta instalação do PHP.</div>";
}

// Informações do ambiente
echo "<h2>Informações do ambiente</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? 'Disponível (' . OPENSSL_VERSION_TEXT . ')' : 'Não disponível') . "\n";
echo "Socket Functions: " . (function_exists('fsockopen') ? 'Disponíveis' : 'Não disponíveis') . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido') . "\n";
echo "Server Name: " . ($_SERVER['SERVER_NAME'] ?? 'Desconhecido') . "\n";
echo "Server IP: " . ($_SERVER['SERVER_ADDR'] ?? 'Desconhecido') . "\n";
echo "</pre>";

// Recomendações baseadas em serviços alternativos
echo "<h2>Recomendações Alternativas</h2>";
echo "<p>Se os testes acima continuarem falhando, considere estas alternativas:</p>";
echo "<ol>";
echo "<li><strong>Atualizar a senha do Gmail:</strong> A senha de aplicativo pode ter expirado. Gere uma nova em <a href='https://myaccount.google.com/apppasswords' target='_blank'>https://myaccount.google.com/apppasswords</a></li>";
echo "<li><strong>Usar TLS em vez de SSL:</strong> Tente mudar de 'ssl' para 'tls' e a porta de 465 para 587</li>";
echo "<li><strong>Usar um serviço de email transacional:</strong> Serviços como SendGrid, Mailgun ou Amazon SES são mais confiáveis para envio automático de emails</li>";
echo "</ol>";

echo "</body></html>";
?>