<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Sistema Contabilidade Estrela 2.0
 * DAO para Empresas
 */

class EmpresaDAO {
    private $db;

    /**
     * Construtor
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Adiciona uma nova empresa ao banco de dados
     * 
     * @param Empresa $empresa Objeto com dados da empresa
     * @return bool True se a operação foi bem-sucedida
     */
    public function adicionarEmpresa(Empresa $empresa) {
        try {
            $sql = "INSERT INTO empresas (
                emp_sit_cad, emp_code, emp_name, emp_tel, emp_cnpj, emp_iest, emp_imun, 
                emp_reg_apu, emp_porte, emp_tipo_jur, emp_nat_jur, emp_cep, emp_ende, 
                emp_nume, emp_comp, emp_bair, emp_cid, emp_uf, emp_org_reg, emp_reg_nire, 
                emp_ult_reg, emp_cod_ace, emp_cod_pre, senha_pfe, emp_cer_dig_data, name, 
                email_empresa, soc1_name, soc1_cpf, soc1_entrada, soc1_email, soc1_tel, 
                soc1_cel, soc1_cep, soc1_ende, soc1_nume, soc1_comp, soc1_bair, soc1_cid, 
                soc1_uf, soc1_quali, soc1_ass, soc1_capit, soc1_govbr, soc1_qualif_govbr, 
                soc2_name, soc2_cpf, soc2_entrada, soc2_email, soc2_tel, soc2_cel, soc2_cep, 
                soc2_ende, soc2_nume, soc2_comp, soc2_bair, soc2_cid, soc2_uf, soc2_quali, 
                soc2_ass, soc2_capit, soc2_govbr, soc2_qualif_govbr, soc3_name, soc3_cpf, 
                soc3_entrada, soc3_email, soc3_tel, soc3_cel, soc3_cep, soc3_ende, soc3_nume, 
                soc3_comp, soc3_bair, soc3_cid, soc3_uf, soc3_quali, soc3_ass, soc3_capit, 
                soc3_govbr, soc3_qualif_govbr, email, usuario, pasta, created_at
            ) VALUES (
                :emp_sit_cad, :emp_code, :emp_name, :emp_tel, :emp_cnpj, :emp_iest, :emp_imun, 
                :emp_reg_apu, :emp_porte, :emp_tipo_jur, :emp_nat_jur, :emp_cep, :emp_ende, 
                :emp_nume, :emp_comp, :emp_bair, :emp_cid, :emp_uf, :emp_org_reg, :emp_reg_nire, 
                :emp_ult_reg, :emp_cod_ace, :emp_cod_pre, :senha_pfe, :emp_cer_dig_data, :name, 
                :email_empresa, :soc1_name, :soc1_cpf, :soc1_entrada, :soc1_email, :soc1_tel, 
                :soc1_cel, :soc1_cep, :soc1_ende, :soc1_nume, :soc1_comp, :soc1_bair, :soc1_cid, 
                :soc1_uf, :soc1_quali, :soc1_ass, :soc1_capit, :soc1_govbr, :soc1_qualif_govbr, 
                :soc2_name, :soc2_cpf, :soc2_entrada, :soc2_email, :soc2_tel, :soc2_cel, :soc2_cep, 
                :soc2_ende, :soc2_nume, :soc2_comp, :soc2_bair, :soc2_cid, :soc2_uf, :soc2_quali, 
                :soc2_ass, :soc2_capit, :soc2_govbr, :soc2_qualif_govbr, :soc3_name, :soc3_cpf, 
                :soc3_entrada, :soc3_email, :soc3_tel, :soc3_cel, :soc3_cep, :soc3_ende, :soc3_nume, 
                :soc3_comp, :soc3_bair, :soc3_cid, :soc3_uf, :soc3_quali, :soc3_ass, :soc3_capit, 
                :soc3_govbr, :soc3_qualif_govbr, :email, :usuario, :pasta, NOW()
            )";
            
            $stmt = $this->db->prepare($sql);
            
            // Vincular parâmetros
            $this->bindEmpresaParams($stmt, $empresa);
            
            $stmt->execute();
            
            // Criar diretório para empresa
            $diretorio = "../documentos/empresas/" . $empresa->getPasta();
            if (!file_exists($diretorio)) {
                mkdir($diretorio, 0755, true);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Erro ao adicionar empresa: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtém os dados de uma empresa em formato adequado para JSON
     * 
     * @param int $id ID da empresa
     * @return array|null Array com dados da empresa ou null se não encontrada
     */
    public function obterEmpresaParaJSON($id) {
        try {
            $sql = "SELECT id, emp_code, emp_name, emp_cnpj, emp_sit_cad, name, 
                    emp_porte, emp_tipo_jur, emp_cid, emp_uf, emp_tel, 
                    email_empresa, created_at as data 
                    FROM empresas WHERE id = :id";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                return null;
            }
            
            return $row;
        } catch (PDOException $e) {
            error_log("Erro ao obter empresa para JSON: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Altera os dados de uma empresa existente
     * 
     * @param Empresa $empresa Objeto com dados da empresa
     * @return bool True se a operação foi bem-sucedida
     */
    public function alterarEmpresa(Empresa $empresa) {
        try {
            $sql = "UPDATE empresas SET 
                emp_sit_cad = :emp_sit_cad, 
                emp_code = :emp_code, 
                emp_name = :emp_name, 
                emp_tel = :emp_tel, 
                emp_cnpj = :emp_cnpj, 
                emp_iest = :emp_iest, 
                emp_imun = :emp_imun, 
                emp_reg_apu = :emp_reg_apu, 
                emp_porte = :emp_porte, 
                emp_tipo_jur = :emp_tipo_jur, 
                emp_nat_jur = :emp_nat_jur, 
                emp_cep = :emp_cep, 
                emp_ende = :emp_ende, 
                emp_nume = :emp_nume, 
                emp_comp = :emp_comp, 
                emp_bair = :emp_bair, 
                emp_cid = :emp_cid, 
                emp_uf = :emp_uf, 
                emp_org_reg = :emp_org_reg, 
                emp_reg_nire = :emp_reg_nire, 
                emp_ult_reg = :emp_ult_reg, 
                emp_cod_ace = :emp_cod_ace, 
                emp_cod_pre = :emp_cod_pre, 
                senha_pfe = :senha_pfe, 
                emp_cer_dig_data = :emp_cer_dig_data, 
                name = :name, 
                email_empresa = :email_empresa, 
                soc1_name = :soc1_name, 
                soc1_cpf = :soc1_cpf, 
                soc1_entrada = :soc1_entrada, 
                soc1_email = :soc1_email, 
                soc1_tel = :soc1_tel, 
                soc1_cel = :soc1_cel, 
                soc1_cep = :soc1_cep, 
                soc1_ende = :soc1_ende, 
                soc1_nume = :soc1_nume, 
                soc1_comp = :soc1_comp, 
                soc1_bair = :soc1_bair, 
                soc1_cid = :soc1_cid, 
                soc1_uf = :soc1_uf, 
                soc1_quali = :soc1_quali, 
                soc1_ass = :soc1_ass, 
                soc1_capit = :soc1_capit, 
                soc1_govbr = :soc1_govbr, 
                soc1_qualif_govbr = :soc1_qualif_govbr, 
                soc2_name = :soc2_name, 
                soc2_cpf = :soc2_cpf, 
                soc2_entrada = :soc2_entrada, 
                soc2_email = :soc2_email, 
                soc2_tel = :soc2_tel, 
                soc2_cel = :soc2_cel, 
                soc2_cep = :soc2_cep, 
                soc2_ende = :soc2_ende, 
                soc2_nume = :soc2_nume, 
                soc2_comp = :soc2_comp, 
                soc2_bair = :soc2_bair, 
                soc2_cid = :soc2_cid, 
                soc2_uf = :soc2_uf, 
                soc2_quali = :soc2_quali, 
                soc2_ass = :soc2_ass, 
                soc2_capit = :soc2_capit, 
                soc2_govbr = :soc2_govbr, 
                soc2_qualif_govbr = :soc2_qualif_govbr, 
                soc3_name = :soc3_name, 
                soc3_cpf = :soc3_cpf, 
                soc3_entrada = :soc3_entrada, 
                soc3_email = :soc3_email, 
                soc3_tel = :soc3_tel, 
                soc3_cel = :soc3_cel, 
                soc3_cep = :soc3_cep, 
                soc3_ende = :soc3_ende, 
                soc3_nume = :soc3_nume, 
                soc3_comp = :soc3_comp, 
                soc3_bair = :soc3_bair, 
                soc3_cid = :soc3_cid, 
                soc3_uf = :soc3_uf, 
                soc3_quali = :soc3_quali, 
                soc3_ass = :soc3_ass, 
                soc3_capit = :soc3_capit, 
                soc3_govbr = :soc3_govbr, 
                soc3_qualif_govbr = :soc3_qualif_govbr, 
                email = :email, 
                usuario = :usuario, 
                updated_at = NOW() 
                WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            
            // Vincular parâmetros
            $stmt->bindParam(':id', $empresa->getId(), PDO::PARAM_INT);
            $this->bindEmpresaParams($stmt, $empresa);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao alterar empresa: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove uma empresa do banco de dados
     * 
     * @param Empresa $empresa Objeto com ID da empresa
     * @return bool True se a operação foi bem-sucedida
     */
    public function removerEmpresa(Empresa $empresa) {
        try {
            $sql = "DELETE FROM empresas WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $empresa->getId(), PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao remover empresa: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se já existe uma empresa com o código fornecido
     * 
     * @param Empresa $empresa Objeto com código da empresa
     * @return bool True se o código já existe
     */
    public function verificarCodigo(Empresa $empresa) {
        try {
            $sql = "SELECT COUNT(*) FROM empresas WHERE emp_code = :emp_code";
            if ($empresa->getId()) {
                $sql .= " AND id != :id";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':emp_code', $empresa->getEmp_code(), PDO::PARAM_STR);
            
            if ($empresa->getId()) {
                $stmt->bindParam(':id', $empresa->getId(), PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erro ao verificar código: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se já existe uma empresa com o nome fornecido
     * 
     * @param Empresa $empresa Objeto com nome da empresa
     * @return bool True se o nome já existe
     */
    public function verificarNome(Empresa $empresa) {
        try {
            $sql = "SELECT COUNT(*) FROM empresas WHERE emp_name = :emp_name";
            if ($empresa->getId()) {
                $sql .= " AND id != :id";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':emp_name', $empresa->getEmp_name(), PDO::PARAM_STR);
            
            if ($empresa->getId()) {
                $stmt->bindParam(':id', $empresa->getId(), PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erro ao verificar nome: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se já existe uma empresa com o CNPJ fornecido
     * 
     * @param Empresa $empresa Objeto com CNPJ da empresa
     * @return bool True se o CNPJ já existe
     */
    public function verificarCnpj(Empresa $empresa) {
        try {
            $sql = "SELECT COUNT(*) FROM empresas WHERE emp_cnpj = :emp_cnpj";
            if ($empresa->getId()) {
                $sql .= " AND id != :id";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':emp_cnpj', $empresa->getEmp_cnpj(), PDO::PARAM_STR);
            
            if ($empresa->getId()) {
                $stmt->bindParam(':id', $empresa->getId(), PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erro ao verificar CNPJ: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém uma empresa pelo ID
     * 
     * @param int $id ID da empresa
     * @return Empresa|null Objeto Empresa ou null se não encontrada
     */
    public function obterEmpresaPorId($id) {
        try {
            $sql = "SELECT * FROM empresas WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                return null;
            }
            
            return $this->criarEmpresaDeArray($row);
        } catch (PDOException $e) {
            error_log("Erro ao obter empresa por ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Lista todas as empresas ou filtra por critérios específicos
     * 
     * @param string|null $filtro Campo para filtrar
     * @param string|null $valor Valor para filtro
     * @param int $limit Limite de registros
     * @param int $offset Deslocamento para paginação
     * @return array Lista de empresas
     */
    public function listarEmpresas($filtro = null, $valor = null, $limit = 20, $offset = 0) {
        try {
            $sql = "SELECT * FROM empresas";
            $params = [];
            
            // Aplicar filtros se fornecidos
            if ($filtro && $valor) {
                $sql .= " WHERE ";
                
                switch ($filtro) {
                    case 'codigo':
                        $sql .= "emp_code LIKE :valor";
                        $params[':valor'] = "%$valor%";
                        break;
                    case 'nome':
                        $sql .= "emp_name LIKE :valor";
                        $params[':valor'] = "%$valor%";
                        break;
                    case 'cnpj':
                        $sql .= "emp_cnpj LIKE :valor";
                        $params[':valor'] = "%$valor%";
                        break;
                    case 'situacao':
                        $sql .= "emp_sit_cad = :valor";
                        $params[':valor'] = $valor;
                        break;
                    case 'porte':
                        $sql .= "emp_porte = :valor";
                        $params[':valor'] = $valor;
                        break;
                    case 'socio':
                        $sql .= "(soc1_name LIKE :valor OR soc2_name LIKE :valor OR soc3_name LIKE :valor)";
                        $params[':valor'] = "%$valor%";
                        break;
                    default:
                        // Filtro padrão por nome
                        $sql .= "emp_name LIKE :valor";
                        $params[':valor'] = "%$valor%";
                }
            }
            
            // Ordenação
            $sql .= " ORDER BY emp_name ASC";
            
            // Paginação
            $sql .= " LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            // Vincular parâmetros de filtro
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            // Vincular parâmetros de paginação
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $empresas = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $empresas[] = $this->criarEmpresaDeArray($row);
            }
            
            return $empresas;
        } catch (PDOException $e) {
            error_log("Erro ao listar empresas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Conta o total de empresas (para paginação)
     * 
     * @param string|null $filtro Campo para filtrar
     * @param string|null $valor Valor para filtro
     * @return int Total de empresas
     */
    public function contarEmpresas($filtro = null, $valor = null) {
        try {
            $sql = "SELECT COUNT(*) FROM empresas";
            $params = [];
            
            // Aplicar filtros se fornecidos
            if ($filtro && $valor) {
                $sql .= " WHERE ";
                
                switch ($filtro) {
                    case 'codigo':
                        $sql .= "emp_code LIKE :valor";
                        $params[':valor'] = "%$valor%";
                        break;
                    case 'nome':
                        $sql .= "emp_name LIKE :valor";
                        $params[':valor'] = "%$valor%";
                        break;
                    case 'cnpj':
                        $sql .= "emp_cnpj LIKE :valor";
                        $params[':valor'] = "%$valor%";
                        break;
                    case 'situacao':
                        $sql .= "emp_sit_cad = :valor";
                        $params[':valor'] = $valor;
                        break;
                    case 'porte':
                        $sql .= "emp_porte = :valor";
                        $params[':valor'] = $valor;
                        break;
                    case 'socio':
                        $sql .= "(soc1_name LIKE :valor OR soc2_name LIKE :valor OR soc3_name LIKE :valor)";
                        $params[':valor'] = "%$valor%";
                        break;
                    default:
                        // Filtro padrão por nome
                        $sql .= "emp_name LIKE :valor";
                        $params[':valor'] = "%$valor%";
                }
            }
            
            $stmt = $this->db->prepare($sql);
            
            // Vincular parâmetros de filtro
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erro ao contar empresas: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Auxiliar para vincular todos os parâmetros de Empresa ao statement
     * 
     * @param PDOStatement $stmt Statement preparado
     * @param Empresa $empresa Objeto com dados da empresa
     */
    private function bindEmpresaParams($stmt, $empresa) {
        $stmt->bindParam(':emp_sit_cad', $empresa->getEmp_sit_cad(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_code', $empresa->getEmp_code(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_name', $empresa->getEmp_name(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_tel', $empresa->getEmp_tel(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_cnpj', $empresa->getEmp_cnpj(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_iest', $empresa->getEmp_iest(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_imun', $empresa->getEmp_imun(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_reg_apu', $empresa->getEmp_reg_apu(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_porte', $empresa->getEmp_porte(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_tipo_jur', $empresa->getEmp_tipo_jur(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_nat_jur', $empresa->getEmp_nat_jur(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_cep', $empresa->getEmp_cep(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_ende', $empresa->getEmp_ende(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_nume', $empresa->getEmp_nume(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_comp', $empresa->getEmp_comp(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_bair', $empresa->getEmp_bair(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_cid', $empresa->getEmp_cid(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_uf', $empresa->getEmp_uf(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_org_reg', $empresa->getEmp_org_reg(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_reg_nire', $empresa->getEmp_reg_nire(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_ult_reg', $empresa->getEmp_ult_reg(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_cod_ace', $empresa->getEmp_cod_ace(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_cod_pre', $empresa->getEmp_cod_pre(), PDO::PARAM_STR);
        $stmt->bindParam(':senha_pfe', $empresa->getSenha_pfe(), PDO::PARAM_STR);
        $stmt->bindParam(':emp_cer_dig_data', $empresa->getEmp_cer_dig_data(), PDO::PARAM_STR);
        $stmt->bindParam(':name', $empresa->getName(), PDO::PARAM_STR);
        $stmt->bindParam(':email_empresa', $empresa->getEmail_empresa(), PDO::PARAM_STR);
        
        // SÓCIO 1
        $stmt->bindParam(':soc1_name', $empresa->getSoc1_name(), PDO::PARAM_STR);
        $stmt->bindParam(':soc1_cpf', $empresa->getSoc1_cpf(), PDO::PARAM_STR);
        $stmt->bindParam(':soc1_entrada', $empresa->getSoc1_entrada(), PDO::PARAM_STR);
        $stmt->bindParam(':soc1_email', $empresa->getSoc1_email(), PDO::PARAM_STR);
        $stmt->bindParam(':soc1_tel', $empresa->getSoc1_tel(), PDO::PARAM_STR);
        $stmt->bindParam(':soc1_cel', $empresa->getSoc1_cel(), PDO::PARAM_STR);
        $stmt->bindParam(':soc1_cep', $empresa->getSoc1_cep(), PDO::PARAM_STR);
        $stmt->bindParam(':soc1_ende', $empresa->getSoc1_ende(), PDO::PARAM_STR);
        $stmt->bindParam(':soc1_nume', $empresa->getSoc1_nume(), PDO::PARAM_STR);
        $stmt->bindParam(':soc1_comp', $empresa->getSoc1_comp(), PDO::PARAM_STR);
        $stmt->bindParam(':soc1_bair', $empresa->getSoc1_bair(), PDO::PARAM_STR);
        $stmt->bindParam(':soc1_cid', $empresa->getSoc1_cid(), PDO::PARAM_STR);
        $stmt->bindParam(':soc1_uf', $empresa->getSoc1_uf(), PDO::PARAM_STR);
        $stmt->bindParam(':soc1_quali', $empresa->getSoc1_quali(), PDO::PARAM_STR);
        $stmt->bindParam(':soc1_ass', $empresa->getSoc1_ass(), PDO::PARAM_STR);
        $stmt->bindParam(':soc1_capit', $empresa->getSoc1_capit(), PDO::PARAM_STR);
        $stmt->bindParam(':soc1_govbr', $empresa->getSoc1_govbr(), PDO::PARAM_STR);
        $stmt->bindParam(':soc1_qualif_govbr', $empresa->getSoc1_qualif_govbr(), PDO::PARAM_STR);
        
        // SÓCIO 2
        $stmt->bindParam(':soc2_name', $empresa->getSoc2_name(), PDO::PARAM_STR);
        $stmt->bindParam(':soc2_cpf', $empresa->getSoc2_cpf(), PDO::PARAM_STR);
        $stmt->bindParam(':soc2_entrada', $empresa->getSoc2_entrada(), PDO::PARAM_STR);
        $stmt->bindParam(':soc2_email', $empresa->getSoc2_email(), PDO::PARAM_STR);
        $stmt->bindParam(':soc2_tel', $empresa->getSoc2_tel(), PDO::PARAM_STR);
        $stmt->bindParam(':soc2_cel', $empresa->getSoc2_cel(), PDO::PARAM_STR);
        $stmt->bindParam(':soc2_cep', $empresa->getSoc2_cep(), PDO::PARAM_STR);
        $stmt->bindParam(':soc2_ende', $empresa->getSoc2_ende(), PDO::PARAM_STR);
        $stmt->bindParam(':soc2_nume', $empresa->getSoc2_nume(), PDO::PARAM_STR);
        $stmt->bindParam(':soc2_comp', $empresa->getSoc2_comp(), PDO::PARAM_STR);
        $stmt->bindParam(':soc2_bair', $empresa->getSoc2_bair(), PDO::PARAM_STR);
        $stmt->bindParam(':soc2_cid', $empresa->getSoc2_cid(), PDO::PARAM_STR);
        $stmt->bindParam(':soc2_uf', $empresa->getSoc2_uf(), PDO::PARAM_STR);
        $stmt->bindParam(':soc2_quali', $empresa->getSoc2_quali(), PDO::PARAM_STR);
        $stmt->bindParam(':soc2_ass', $empresa->getSoc2_ass(), PDO::PARAM_STR);
        $stmt->bindParam(':soc2_capit', $empresa->getSoc2_capit(), PDO::PARAM_STR);
        $stmt->bindParam(':soc2_govbr', $empresa->getSoc2_govbr(), PDO::PARAM_STR);
        $stmt->bindParam(':soc2_qualif_govbr', $empresa->getSoc2_qualif_govbr(), PDO::PARAM_STR);
        
        // SÓCIO 3
        $stmt->bindParam(':soc3_name', $empresa->getSoc3_name(), PDO::PARAM_STR);
        $stmt->bindParam(':soc3_cpf', $empresa->getSoc3_cpf(), PDO::PARAM_STR);
        $stmt->bindParam(':soc3_entrada', $empresa->getSoc3_entrada(), PDO::PARAM_STR);
        $stmt->bindParam(':soc3_email', $empresa->getSoc3_email(), PDO::PARAM_STR);
        $stmt->bindParam(':soc3_tel', $empresa->getSoc3_tel(), PDO::PARAM_STR);
        $stmt->bindParam(':soc3_cel', $empresa->getSoc3_cel(), PDO::PARAM_STR);
        $stmt->bindParam(':soc3_cep', $empresa->getSoc3_cep(), PDO::PARAM_STR);
        $stmt->bindParam(':soc3_ende', $empresa->getSoc3_ende(), PDO::PARAM_STR);
        $stmt->bindParam(':soc3_nume', $empresa->getSoc3_nume(), PDO::PARAM_STR);
        $stmt->bindParam(':soc3_comp', $empresa->getSoc3_comp(), PDO::PARAM_STR);
        $stmt->bindParam(':soc3_bair', $empresa->getSoc3_bair(), PDO::PARAM_STR);
        $stmt->bindParam(':soc3_cid', $empresa->getSoc3_cid(), PDO::PARAM_STR);
        $stmt->bindParam(':soc3_uf', $empresa->getSoc3_uf(), PDO::PARAM_STR);
        $stmt->bindParam(':soc3_quali', $empresa->getSoc3_quali(), PDO::PARAM_STR);
        $stmt->bindParam(':soc3_ass', $empresa->getSoc3_ass(), PDO::PARAM_STR);
        $stmt->bindParam(':soc3_capit', $empresa->getSoc3_capit(), PDO::PARAM_STR);
        $stmt->bindParam(':soc3_govbr', $empresa->getSoc3_govbr(), PDO::PARAM_STR);
        $stmt->bindParam(':soc3_qualif_govbr', $empresa->getSoc3_qualif_govbr(), PDO::PARAM_STR);
        
        // OUTROS DADOS
        $stmt->bindParam(':email', $empresa->getEmail(), PDO::PARAM_STR);
        $stmt->bindParam(':usuario', $empresa->getUsuario(), PDO::PARAM_STR);
        $stmt->bindParam(':pasta', $empresa->getPasta(), PDO::PARAM_STR);
    }
    
    /**
        * Cria um objeto Empresa a partir de um array associativo (do banco de dados)
        * 
        * @param array $data Array associativo com dados da empresa
        * @return Empresa Objeto preenchido
        */
    private function criarEmpresaDeArray($data) {
        $empresa = new Empresa();
        
        $empresa->setId($data['id']);
        $empresa->setEmp_sit_cad($data['emp_sit_cad']);
        $empresa->setEmp_code($data['emp_code']);
        $empresa->setEmp_name($data['emp_name']);
        $empresa->setEmp_tel($data['emp_tel']);
        $empresa->setEmp_cnpj($data['emp_cnpj']);
        $empresa->setEmp_iest($data['emp_iest']);
        $empresa->setEmp_imun($data['emp_imun']);
        $empresa->setEmp_reg_apu($data['emp_reg_apu']);
        $empresa->setEmp_porte($data['emp_porte']);
        $empresa->setEmp_tipo_jur($data['emp_tipo_jur']);
        $empresa->setEmp_nat_jur($data['emp_nat_jur']);
        $empresa->setEmp_cep($data['emp_cep']);
        $empresa->setEmp_ende($data['emp_ende']);
        $empresa->setEmp_nume($data['emp_nume']);
        $empresa->setEmp_comp($data['emp_comp']);
        $empresa->setEmp_bair($data['emp_bair']);
        $empresa->setEmp_cid($data['emp_cid']);
        $empresa->setEmp_uf($data['emp_uf']);
        $empresa->setEmp_org_reg($data['emp_org_reg']);
        $empresa->setEmp_reg_nire($data['emp_reg_nire']);
        $empresa->setEmp_ult_reg($data['emp_ult_reg']);
        $empresa->setEmp_cod_ace($data['emp_cod_ace']);
        $empresa->setEmp_cod_pre($data['emp_cod_pre']);
        $empresa->setSenha_pfe($data['senha_pfe']);
        $empresa->setEmp_cer_dig_data($data['emp_cer_dig_data']);
        $empresa->setName($data['name']);
        $empresa->setEmail_empresa($data['email_empresa']);
        
        // SÓCIO 1
        $empresa->setSoc1_name($data['soc1_name']);
        $empresa->setSoc1_cpf($data['soc1_cpf']);
        $empresa->setSoc1_entrada($data['soc1_entrada']);
        $empresa->setSoc1_email($data['soc1_email']);
        $empresa->setSoc1_tel($data['soc1_tel']);
        $empresa->setSoc1_cel($data['soc1_cel']);
        $empresa->setSoc1_cep($data['soc1_cep']);
        $empresa->setSoc1_ende($data['soc1_ende']);
        $empresa->setSoc1_nume($data['soc1_nume']);
        $empresa->setSoc1_comp($data['soc1_comp']);
        $empresa->setSoc1_bair($data['soc1_bair']);
        $empresa->setSoc1_cid($data['soc1_cid']);
        $empresa->setSoc1_uf($data['soc1_uf']);
        $empresa->setSoc1_quali($data['soc1_quali']);
        $empresa->setSoc1_ass($data['soc1_ass']);
        $empresa->setSoc1_capit($data['soc1_capit']);
        $empresa->setSoc1_govbr($data['soc1_govbr']);
        $empresa->setSoc1_qualif_govbr($data['soc1_qualif_govbr']);
        
        // SÓCIO 2
        $empresa->setSoc2_name($data['soc2_name']);
        $empresa->setSoc2_cpf($data['soc2_cpf']);
        $empresa->setSoc2_entrada($data['soc2_entrada']);
        $empresa->setSoc2_email($data['soc2_email']);
        $empresa->setSoc2_tel($data['soc2_tel']);
        $empresa->setSoc2_cel($data['soc2_cel']);
        $empresa->setSoc2_cep($data['soc2_cep']);
        $empresa->setSoc2_ende($data['soc2_ende']);
        $empresa->setSoc2_nume($data['soc2_nume']);
        $empresa->setSoc2_comp($data['soc2_comp']);
        $empresa->setSoc2_bair($data['soc2_bair']);
        $empresa->setSoc2_cid($data['soc2_cid']);
        $empresa->setSoc2_uf($data['soc2_uf']);
        $empresa->setSoc2_quali($data['soc2_quali']);
        $empresa->setSoc2_ass($data['soc2_ass']);
        $empresa->setSoc2_capit($data['soc2_capit']);
        $empresa->setSoc2_govbr($data['soc2_govbr']);
        $empresa->setSoc2_qualif_govbr($data['soc2_qualif_govbr']);
        
        // SÓCIO 3
        $empresa->setSoc3_name($data['soc3_name']);
        $empresa->setSoc3_cpf($data['soc3_cpf']);
        $empresa->setSoc3_entrada($data['soc3_entrada']);
        $empresa->setSoc3_email($data['soc3_email']);
        $empresa->setSoc3_tel($data['soc3_tel']);
        $empresa->setSoc3_cel($data['soc3_cel']);
        $empresa->setSoc3_cep($data['soc3_cep']);
        $empresa->setSoc3_ende($data['soc3_ende']);
        $empresa->setSoc3_nume($data['soc3_nume']);
        $empresa->setSoc3_comp($data['soc3_comp']);
        $empresa->setSoc3_bair($data['soc3_bair']);
        $empresa->setSoc3_cid($data['soc3_cid']);
        $empresa->setSoc3_uf($data['soc3_uf']);
        $empresa->setSoc3_quali($data['soc3_quali']);
        $empresa->setSoc3_ass($data['soc3_ass']);
        $empresa->setSoc3_capit($data['soc3_capit']);
        $empresa->setSoc3_govbr($data['soc3_govbr']);
        $empresa->setSoc3_qualif_govbr($data['soc3_qualif_govbr']);
        
        // OUTROS DADOS
        $empresa->setEmail($data['email']);
        $empresa->setUsuario($data['usuario']);
        $empresa->setPasta($data['pasta']);
        
        return $empresa;
   }
    /**
     * Obtém uma empresa pelo código
     * @param string $codigo
     * @return Empresa|null
     */
    public function obterEmpresaPorCodigo($codigo) {
        try {
            $sql = "SELECT * FROM empresas WHERE emp_code = :codigo";
            $params = [':codigo' => $codigo];
            
            $stmt = $this->db->getPDO()->prepare($sql);
            $stmt->execute($params);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado) {
                $empresa = new Empresa();
                $empresa->setId($resultado['id']);
                $empresa->setEmp_code($resultado['emp_code']);
                $empresa->setEmp_name($resultado['emp_name']);
                $empresa->setEmp_cnpj($resultado['emp_cnpj']);
                $empresa->setEmp_sit_cad($resultado['emp_sit_cad']);
                $empresa->setEmp_tipo_jur($resultado['emp_tipo_jur']);
                $empresa->setEmp_porte($resultado['emp_porte']);
                $empresa->setEmp_cid($resultado['emp_cid']);
                $empresa->setEmp_uf($resultado['emp_uf']);
                $empresa->setName($resultado['name']);
                $empresa->setEmp_tel($resultado['emp_tel']);
                $empresa->setEmail_empresa($resultado['email_empresa']);
                $empresa->setData($resultado['data']);
                
                return $empresa;
            }
            
            return null;
        } catch (PDOException $e) {
            throw new Exception("Erro ao obter empresa por código: " . $e->getMessage());
        }
    }   
}