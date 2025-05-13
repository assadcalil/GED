<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Controller para gerenciamento de envio de emails com certificados digitais
 * Faz a intermediação entre a view e o DAO
 */
date_default_timezone_set('America/Sao_Paulo');
header("Content-type: text/html; charset=utf-8");

// Incluir classes necessárias
require_once(__DIR__ . '/../../...../app/Models/Certificado.php');
require_once(__DIR__ . '/../../...../app/Dao/CertificadoEmailDao.php');
require_once(__DIR__ . '/../../...../app/Config/Logger.php');

class ControllerEnviaEmailCertificado {
    private $certificadoDao;
    
    /**
     * Construtor - inicializa o DAO
     */
    public function __construct() {
        $this->certificadoDao = new EnviaEmailCertificadoDao();
    }
    
    /**
     * Busca informações da empresa pelo código
     * @param string $empCode Código da empresa
     * @return array|false Dados da empresa
     */
    public function buscarEmpresa($empCode) {
        return $this->certificadoDao->buscarEmpresa($empCode);
    }
    
    /**
     * Processa a prévia do email
     * @param array $dadosFormulario Dados do formulário
     * @return array Resultado da validação
     */
    public function processarPrevia($dadosFormulario) {
        // Criar modelo com os dados do formulário
        $modelo = new EnviaEmailCertificado($dadosFormulario);
        
        // Validar dados para prévia
        $erros = $modelo->validarPrevia();
        
        if (!empty($erros)) {
            return [
                'sucesso' => false,
                'mensagem' => implode('. ', $erros)
            ];
        }
        
        return [
            'sucesso' => true,
            'mensagem' => '',
            'dados' => $modelo->getDados()
        ];
    }
    
    /**
     * Processa o envio do email com certificado digital
     * @param array $dadosFormulario Dados do formulário
     * @param array $arquivoCertificado Arquivo do certificado
     * @return array Resultado do processamento
     */
    public function processarEnvio($dadosFormulario, $arquivoCertificado) {
        // Adicionar arquivo ao dados
        $dadosFormulario['arquivo_certificado'] = $arquivoCertificado;
        
        try {
            // Validar todos os dados
            $erros = $this->validarDadosCertificado($dadosFormulario, $arquivoCertificado);
            
            if (!empty($erros)) {
                return [
                    'sucesso' => false,
                    'mensagem' => implode('. ', $erros)
                ];
            }
            
            // Ajustar nomes dos campos para compatibilidade com o DAO
            $dadosParaEnvio = $dadosFormulario;
            
            // Verificar se precisamos renomear campos para compatibilidade
            if (isset($dadosFormulario['certificado_tipo']) && !isset($dadosFormulario['tipo_certificado'])) {
                $dadosParaEnvio['tipo_certificado'] = $dadosFormulario['certificado_tipo'];
            }
            
            // Preparar dados para atualização no banco
            $dadosCertificado = [
                'tipo' => $dadosFormulario['certificado_tipo'],
                'categoria' => $this->determinarCategoriaCertificado($dadosFormulario['certificado_tipo']),
                'emissao' => $dadosFormulario['data_renovacao'],
                'validade' => $dadosFormulario['certificado_vencimento'],
                'situacao' => $this->determinarSituacaoCertificado($dadosFormulario['certificado_vencimento'])
            ];
            
            // Adicionar informação para debug
            Logger::debug("Dados para envio de email", [
                'dadosFormulario' => $dadosFormulario,
                'dadosParaEnvio' => $dadosParaEnvio
            ]);
            
            // Tentar enviar o email
            $resultadoEmail = $this->certificadoDao->enviarEmailCertificado($dadosParaEnvio);
            
            // Se o email for enviado com sucesso, atualizar dados no banco
            if ($resultadoEmail['sucesso']) {
                // Atualizar dados do certificado no banco
                $atualizacaoBanco = $this->certificadoDao->atualizarDadosCertificado(
                    $dadosFormulario['emp_code'], 
                    $dadosCertificado
                );
                
                // Verificar se a atualização do banco foi bem-sucedida
                if (!$atualizacaoBanco) {
                    // Log de falha na atualização do banco, mas não impedir o envio do email
                    Logger::warning("Falha ao atualizar dados do certificado no banco", [
                        'empresa' => $dadosFormulario['emp_code'],
                        'dados' => $dadosCertificado
                    ]);
                }
                
                return $resultadoEmail;
            }
            
            // Retornar resultado do envio de email
            return $resultadoEmail;
            
        } catch (Exception $e) {
            // Registrar erro
            Logger::error("Erro no processamento do envio de certificado: " . $e->getMessage(), $dadosFormulario);
            
            return [
                'sucesso' => false,
                'mensagem' => 'Erro no processamento: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Método para visualizar detalhes do certificado
     * @param int $certificadoId ID do certificado
     * @return array Resultado da visualização
     */
    public function visualizarCertificado($certificadoId) {
        try {
            // Buscar dados do certificado
            $sql = "SELECT cd.*, e.emp_name, e.emp_code, e.emp_cnpj 
                    FROM certificado_digital cd 
                    INNER JOIN empresas e ON cd.empresa_id = e.id 
                    WHERE cd.certificado_id = ?";
            
            $certificado = Database::selectOne($sql, [$certificadoId]);
            
            if (!$certificado) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Certificado não encontrado'
                ];
            }
            
            // Formatar datas para exibição
            $certificado['certificado_emissao_formatada'] = date('d/m/Y', strtotime($certificado['certificado_emissao']));
            $certificado['certificado_validade_formatada'] = date('d/m/Y', strtotime($certificado['certificado_validade']));
            
            // Adicionar texto para situação
            $situacoesTexto = [
                'VIGENTE' => 'Vigente',
                'VENCIDO' => 'Vencido',
                'PRESTES_A_VENCER' => 'Prestes a Vencer',
                'RENOVACAO_PENDENTE' => 'Renovação Pendente'
            ];
            
            $certificado['certificado_situacao_texto'] = $situacoesTexto[$certificado['certificado_situacao']] ?? $certificado['certificado_situacao'];
            
            // Adicionar cor para situação
            $situacoesCor = [
                'VIGENTE' => 'success',
                'VENCIDO' => 'danger',
                'PRESTES_A_VENCER' => 'warning',
                'RENOVACAO_PENDENTE' => 'info'
            ];
            
            $certificado['situacao_cor'] = $situacoesCor[$certificado['certificado_situacao']] ?? 'secondary';
            
            // Registrar a visualização no log
            Logger::activity('visualizar', "Visualização dos detalhes do certificado #$certificadoId");
            
            // Retornar dados
            return [
                'sucesso' => true,
                'dados' => $certificado
            ];
            
        } catch (Exception $e) {
            // Registrar erro
            Logger::error("Erro ao visualizar detalhes do certificado: " . $e->getMessage(), [
                'certificado_id' => $certificadoId,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Retornar erro
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao buscar detalhes do certificado: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validar dados do certificado
     * @param array $dadosFormulario Dados do formulário
     * @param array $arquivoCertificado Arquivo do certificado
     * @return array Erros encontrados
     */
    private function validarDadosCertificado($dadosFormulario, $arquivoCertificado) {
        $erros = [];
        
        // Validar formato de arquivo
        $extensoesPermitidas = ['pfx', 'p12', 'cer', 'pem'];
        $extensao = strtolower(pathinfo($arquivoCertificado['name'], PATHINFO_EXTENSION));
        if (!in_array($extensao, $extensoesPermitidas)) {
            $erros[] = "Formato de arquivo inválido. Formatos permitidos: .pfx, .p12, .cer, .pem";
        }
        
        // Verificar tamanho do arquivo (limite de 10MB)
        $tamanhoMaximo = 10 * 1024 * 1024; // 10MB em bytes
        if ($arquivoCertificado['size'] > $tamanhoMaximo) {
            $erros[] = "O arquivo é muito grande. Tamanho máximo permitido: 10MB.";
        }
        
        // Validações adicionais dos dados do formulário
        if (empty($dadosFormulario['emp_code'])) {
            $erros[] = "Código da empresa não informado";
        }
        
        if (empty($dadosFormulario['certificado_tipo'])) {
            $erros[] = "Tipo de certificado não selecionado";
        }
        
        return $erros;
    }
    
    /**
     * Determinar categoria do certificado
     * @param string $tipoCertificado Tipo de certificado
     * @return string Categoria do certificado
     */
    private function determinarCategoriaCertificado($tipoCertificado) {
        $categorias = [
            'e-CNPJ A1' => 'A1',
            'e-CNPJ A3' => 'A3',
            'e-CPF A1' => 'A1',
            'e-CPF A3' => 'A3',
            'NF-e' => 'NF-e',
            'CT-e' => 'CT-e'
        ];
        
        return $categorias[$tipoCertificado] ?? 'Outro';
    }
    
    /**
     * Determinar situação do certificado baseado na data de validade
     * @param string $dataValidade Data de validade do certificado
     * @return string Situação do certificado
     */
    private function determinarSituacaoCertificado($dataValidade) {
        try {
            $dataAtual = new DateTime();
            $dataVencimento = new DateTime($dataValidade);
            
            if ($dataVencimento < $dataAtual) {
                return 'Vencido';
            }
            
            // Verificar se está próximo do vencimento (30 dias)
            $dataProximoVencimento = clone $dataVencimento;
            $dataProximoVencimento->modify('-30 days');
            
            if ($dataAtual >= $dataProximoVencimento && $dataAtual < $dataVencimento) {
                return 'Próximo do Vencimento';
            }
            
            return 'Vigente';
        } catch (Exception $e) {
            // Em caso de erro na conversão de data, retornar situação padrão
            Logger::warning("Erro ao determinar situação do certificado: " . $e->getMessage());
            return 'Situação Indeterminada';
        }
    }
    
    /**
     * Função para formatar data para o formato brasileiro
     * @param string $data Data a ser formatada
     * @return string Data formatada
     */
    public function formatarData($data) {
        if (empty($data)) return '';
        return date('d/m/Y', strtotime($data));
    }
    
    /**
     * Função para retornar lista de tipos de certificados
     * @return array Lista de tipos de certificado
     */
    public function getTiposCertificado() {
        return [
            'e-CNPJ A1' => 'e-CNPJ A1',
            'e-CNPJ A3' => 'e-CNPJ A3',
            'e-CPF A1' => 'e-CPF A1',
            'e-CPF A3' => 'e-CPF A3',
            'NF-e' => 'NF-e',
            'CT-e' => 'CT-e',
            'Outro' => 'Outro'
        ];
    }
}