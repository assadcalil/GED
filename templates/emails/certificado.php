<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Template de Email para Certificado Digital
 * Arquivo: templates/email_certificado.php
 */

class EmailTemplateCertificado {
    /**
     * Gera o HTML do email para certificado digital
     * 
     * @param array $empresaData Dados da empresa
     * @param array $certificadoData Dados do certificado
     * @return string HTML formatado do email
     */
    public static function gerarHTML($empresaData, $certificadoData) {
        $dataEmissao = date('d/m/Y', strtotime($certificadoData['certificado_emissao']));
        $dataValidade = date('d/m/Y', strtotime($certificadoData['certificado_validade']));
        
        $html = '
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>Certificado Digital - ' . htmlspecialchars($empresaData['emp_name']) . '</title>
        </head>
        <body style="font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 0; background-color: #f5f5f5; color: #333333;">
            <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px; border: 1px solid #dddddd; border-radius: 5px;">
                <div style="text-align: center; padding-bottom: 20px; border-bottom: 2px solid #0078D4;">
                    <h1 style="color: #0078D4; margin: 0;">Certificado Digital</h1>
                    <p style="color: #666666;">Contabilidade Estrela</p>
                </div>
                
                <div style="padding: 20px 0;">
                    <p>Prezado(a) Cliente,</p>
                    
                    <p>Segue as informações do certificado digital da sua empresa. Por favor, verifique os dados e mantenha este documento em local seguro.</p>
                    
                    <div style="background-color: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 4px; padding: 15px; margin: 20px 0;">
                        <h2 style="color: #0078D4; margin-top: 0; font-size: 18px;">Informações do Certificado</h2>
                        
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="padding: 5px 10px; font-weight: bold; width: 40%;">Empresa:</td>
                                <td style="padding: 5px 10px;">' . htmlspecialchars($empresaData['emp_name']) . '</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 10px; font-weight: bold;">Código:</td>
                                <td style="padding: 5px 10px;">' . htmlspecialchars($empresaData['emp_code']) . '</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 10px; font-weight: bold;">Tipo:</td>
                                <td style="padding: 5px 10px;">' . htmlspecialchars($certificadoData['certificado_tipo']) . ' - ' . htmlspecialchars($certificadoData['certificado_categoria']) . '</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 10px; font-weight: bold;">Data de Emissão:</td>
                                <td style="padding: 5px 10px;">' . $dataEmissao . '</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 10px; font-weight: bold;">Data de Validade:</td>
                                <td style="padding: 5px 10px;">' . $dataValidade . '</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 10px; font-weight: bold;">Situação:</td>
                                <td style="padding: 5px 10px;">' . htmlspecialchars($certificadoData['certificado_situacao']) . '</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 10px; font-weight: bold;">Titular:</td>
                                <td style="padding: 5px 10px;">' . htmlspecialchars($certificadoData['certificado_titular']) . '</td>
                            </tr>
                        </table>
                    </div>
                    
                    <p>Caso seu certificado esteja próximo do vencimento, recomendamos que entre em contato conosco para providenciar a renovação.</p>
                    
                    <p>Em caso de dúvidas ou para mais informações, não hesite em nos contatar.</p>
                    
                    <p>Atenciosamente,<br>
                    Equipe de Certificados Digitais<br>
                    Contabilidade Estrela<br>
                    Tel: (11) 2124-7070<br>
                    Email: cestrela@terra.com.br</p>
                </div>
                
                <div style="text-align: center; padding-top: 20px; border-top: 1px solid #e0e0e0; color: #777777; font-size: 12px;">
                    &copy; ' . date('Y') . ' Contabilidade Estrela - Todos os direitos reservados
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Gera o assunto do email para certificado digital
     * 
     * @param array $empresaData Dados da empresa
     * @return string Assunto formatado
     */
    public static function gerarAssunto($empresaData) {
        return 'Certificado Digital - ' . $empresaData['emp_code'] . ' - ' . $empresaData['emp_name'];
    }
}