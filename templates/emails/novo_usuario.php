<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Template HTML para Email de Novo Usuário
 * Arquivo: templates/email_novo_usuario.php
 */

class EmailTemplateNovoUsuario {
    
    /**
     * Gera o HTML do email de boas-vindas para novo usuário
     * 
     * @param array $userData Dados do usuário
     * @param string $password Senha gerada para o usuário
     * @return string HTML do email
     */
    public static function gerarHTML($userData, $password) {
        $html = '
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>Bem-vindo à Contabilidade Estrela</title>
        </head>
        <body style="font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 0; background-color: #f4f4f4;">
            <table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse; background-color: #ffffff; margin: 20px auto; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px;">
                <!-- Cabeçalho -->
                <tr>
                    <td style="background-color: #004a85; padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                        <h1 style="margin: 0; color: white; font-size: 28px;">Bem-vindo à<br>Contabilidade Estrela</h1>
                    </td>
                </tr>
                
                <!-- Conteúdo Principal -->
                <tr>
                    <td style="padding: 40px;">
                        <p style="font-size: 18px; margin-bottom: 25px;">Olá <strong>' . htmlspecialchars($userData['name']) . '</strong>,</p>
                        
                        <p style="font-size: 16px; margin-bottom: 25px;">Sua conta foi criada com sucesso em nosso sistema. Estamos felizes em tê-lo(a) conosco!</p>
                        
                        <!-- Box de Credenciais -->
                        <div style="background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 25px; margin: 30px 0;">
                            <h3 style="margin: 0 0 20px 0; color: #004a85; font-size: 18px;">Suas Credenciais de Acesso</h3>
                            <p style="margin: 10px 0;"><strong style="color: #495057;">Usuário:</strong> ' . htmlspecialchars($userData['username']) . '</p>
                            <p style="margin: 10px 0;"><strong style="color: #495057;">Senha:</strong> ' . htmlspecialchars($password) . '</p>
                            <p style="margin: 10px 0;"><strong style="color: #495057;">Tipo de Usuário:</strong> ' . htmlspecialchars(Auth::$userTypes[$userData['type']] ?? 'Funcionário') . '</p>
                        </div>
                        
                        <!-- Botão de Acesso -->
                        <div style="text-align: center; margin: 35px 0;">
                            <a href="' . EmailConfig::SISTEMA_URL . '" 
                               style="display: inline-block; background-color: #28a745; color: white; padding: 15px 35px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 16px;">
                                Acessar o Sistema
                            </a>
                        </div>
                        
                        <p style="font-size: 16px; margin-bottom: 25px;">Para sua segurança, recomendamos que você altere sua senha no primeiro acesso.</p>
                        
                        <!-- Instruções -->
                        <div style="background-color: #e8f5e9; border-left: 4px solid #28a745; padding: 15px; margin: 30px 0;">
                            <h4 style="margin: 0 0 10px 0; color: #2e7d32;">Primeiros Passos:</h4>
                            <ol style="margin: 0; padding-left: 20px; color: #2e7d32;">
                                <li>Acesse o sistema usando suas credenciais</li>
                                <li>Altere sua senha temporária</li>
                                <li>Complete seu perfil</li>
                                <li>Explore as funcionalidades disponíveis</li>
                            </ol>
                        </div>
                        
                        <hr style="border: none; border-top: 1px solid #e9ecef; margin: 30px 0;">
                        
                        <p style="font-size: 14px; color: #666; margin: 0;">Em caso de dúvidas, entre em contato com o administrador do sistema.</p>
                    </td>
                </tr>
                
                <!-- Rodapé -->
                <tr>
                    <td style="background-color: #333; color: #fff; padding: 20px; text-align: center; border-radius: 0 0 8px 8px;">
                        <p style="margin: 0 0 10px 0; font-size: 18px; font-weight: bold;">Contabilidade Estrela</p>
                        <p style="margin: 0; font-size: 12px;">© ' . date('Y') . ' Todos os direitos reservados</p>
                        <p style="margin: 10px 0 0 0; font-size: 11px;">Este é um email automático. Por favor, não responda.</p>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
        
        return $html;
    }
}
?>