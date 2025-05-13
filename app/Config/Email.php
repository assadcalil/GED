<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Configurações de Email
 * Arquivo: ../...../app/Config/Email.php
 */

class EmailConfig {
    // Configurações SMTP
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 465;
    const SMTP_USER = 'recuperacaoestrela@gmail.com';
    const SMTP_PASS = 'sgyrmsgdaxiqvupb';
    const SMTP_SECURE = 'ssl';
    
    // Informações do remetente
    const EMAIL_REMETENTE = 'recuperacaoestrela@gmail.com';
    const NOME_REMETENTE = 'CONTABILIDADE ESTRELA';
    
    // Destinatários padrão
    const EMAIL_COPIA = 'cestrela.cancelar@terra.com.br';
    
    // URLs do sistema
    const SISTEMA_URL = 'https://contabilidadeestrela.com.br/ged2.0/';
    
    // Configurações gerais
    const DEBUG_SMTP = 0; // 0 = sem debug, 1 = mensagens do cliente, 2 = mensagens do cliente e servidor
    const CHARSET = 'UTF-8';
    const IS_HTML = true;
    
    /**
     * Retorna array com todas as configurações
     * 
     * @return array
     */
    public static function getConfig() {
        return [
            'smtp' => [
                'host' => self::SMTP_HOST,
                'port' => self::SMTP_PORT,
                'user' => self::SMTP_USER,
                'pass' => self::SMTP_PASS,
                'secure' => self::SMTP_SECURE,
                'debug' => self::DEBUG_SMTP
            ],
            'remetente' => [
                'email' => self::EMAIL_REMETENTE,
                'nome' => self::NOME_REMETENTE
            ],
            'destinatarios' => [
                'copia' => self::EMAIL_COPIA
            ],
            'sistema' => [
                'url' => self::SISTEMA_URL
            ],
            'geral' => [
                'charset' => self::CHARSET,
                'is_html' => self::IS_HTML
            ]
        ];
    }
}
?>