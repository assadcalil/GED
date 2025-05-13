<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * DAO para Documentos
 */

class DocumentoDAO {
    private $db;

    /**
     * Construtor
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Lista documentos recentes de uma empresa
     * @param int $empresaId ID da empresa
     * @param int $limit Limite de registros
     * @return array Lista de documentos
     */
    public function listarDocumentosPorEmpresa($empresaId, $limit = 5) {
        try {
            $sql = "SELECT id, doc_nome, doc_tipo, data_upload, doc_caminho, 
                           tamanho, usuario_upload
                    FROM documentos 
                    WHERE empresa_id = ? 
                    ORDER BY data_upload DESC 
                    LIMIT ?";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $empresaId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao listar documentos da empresa: " . $e->getMessage());
            return [];
        }
    }
}