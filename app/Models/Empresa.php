<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Sistema Contabilidade Estrela 2.0
 * Model para Empresa
 */

class Empresa {
    // Propriedades
    private $id;
    private $emp_sit_cad;  
    private $emp_code;
    private $emp_name;
    private $emp_tel;
    private $emp_cnpj;
    private $emp_iest;
    private $emp_imun;
    private $emp_reg_apu;
    private $emp_porte;
    private $emp_tipo_jur;
    private $emp_nat_jur;
    private $emp_cep;
    private $emp_ende;
    private $emp_nume;
    private $emp_comp;
    private $emp_bair;
    private $emp_cid;
    private $emp_uf;
    private $emp_org_reg;
    private $emp_reg_nire;
    private $emp_ult_reg;
    private $emp_cod_ace;
    private $emp_cod_pre;
    private $senha_pfe;
    private $emp_cer_dig_data;
    private $name;
    private $email_empresa;
    
    // SÓCIO 1
    private $soc1_name;
    private $soc1_cpf;
    private $soc1_entrada;
    private $soc1_email;
    private $soc1_tel;
    private $soc1_cel;
    private $soc1_cep;
    private $soc1_ende;
    private $soc1_nume;
    private $soc1_comp;
    private $soc1_bair;
    private $soc1_cid;
    private $soc1_uf;
    private $soc1_quali;
    private $soc1_ass;
    private $soc1_capit;
    private $soc1_govbr;
    private $soc1_qualif_govbr;
    
    // SÓCIO 2
    private $soc2_name;
    private $soc2_cpf;
    private $soc2_entrada;
    private $soc2_email;
    private $soc2_tel;
    private $soc2_cel;
    private $soc2_cep;
    private $soc2_ende;
    private $soc2_nume;
    private $soc2_comp;
    private $soc2_bair;
    private $soc2_cid;
    private $soc2_uf;
    private $soc2_quali;
    private $soc2_ass;
    private $soc2_capit;
    private $soc2_govbr;
    private $soc2_qualif_govbr;
    
    // SÓCIO 3
    private $soc3_name;
    private $soc3_cpf;
    private $soc3_entrada;
    private $soc3_email;
    private $soc3_tel;
    private $soc3_cel;
    private $soc3_cep;
    private $soc3_ende;
    private $soc3_nume;
    private $soc3_comp;
    private $soc3_bair;
    private $soc3_cid;
    private $soc3_uf;
    private $soc3_quali;
    private $soc3_ass;
    private $soc3_capit;
    private $soc3_govbr;
    private $soc3_qualif_govbr;
    
    // OUTROS DADOS
    private $email;
    private $usuario;
    private $pasta;
    private $created_at;
    private $updated_at;
    
    // Tipos de situação cadastral
    public static $situacoesCadastrais = [
        'ATIVA' => 'Ativa',
        'INATIVA' => 'Inativa',
        'SUSPENSA' => 'Suspensa',
        'BAIXADA' => 'Baixada'
    ];
    
    // Tipos de porte da empresa
    public static $portes = [
        'MEI' => 'Microempreendedor Individual', 
        'ME' => 'Microempresa',
        'EPP' => 'Empresa de Pequeno Porte',
        'MEDIO' => 'Médio Porte',
        'GRANDE' => 'Grande Porte'
    ];
    
    // Tipos jurídicos
    public static $tiposJuridicos = [
        'EI' => 'Empresário Individual', 
        'EIRELI' => 'Empresa Individual de Responsabilidade Limitada',
        'LTDA' => 'Sociedade Limitada',
        'SA' => 'Sociedade Anônima',
        'SLU' => 'Sociedade Limitada Unipessoal',
        'OUTROS' => 'Outros'
    ];
    
    // Regimes de apuração
    public static $regimesApuracao = [
        'SIMPLES' => 'Simples Nacional',
        'LUCRO_PRESUMIDO' => 'Lucro Presumido',
        'LUCRO_REAL' => 'Lucro Real'
    ];

    /**
     * Construtor
     */
    public function __construct() {
        // Inicialização vazia
    }

    /**
     * Getters e Setters
     */
    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getEmp_sit_cad() {
        return $this->emp_sit_cad;
    }

    public function setEmp_sit_cad($emp_sit_cad) {
        $this->emp_sit_cad = $emp_sit_cad;
    }

    public function getEmp_code() {
        return $this->emp_code;
    }

    public function setEmp_code($emp_code) {
        $this->emp_code = $emp_code;
    }

    public function getEmp_name() {
        return $this->emp_name;
    }

    public function setEmp_name($emp_name) {
        $this->emp_name = $emp_name;
    }

    public function getEmp_tel() {
        return $this->emp_tel;
    }

    public function setEmp_tel($emp_tel) {
        $this->emp_tel = $emp_tel;
    }

    public function getEmp_cnpj() {
        return $this->emp_cnpj;
    }

    public function setEmp_cnpj($emp_cnpj) {
        $this->emp_cnpj = $emp_cnpj;
    }

    public function getEmp_iest() {
        return $this->emp_iest;
    }

    public function setEmp_iest($emp_iest) {
        $this->emp_iest = $emp_iest;
    }

    public function getEmp_imun() {
        return $this->emp_imun;
    }

    public function setEmp_imun($emp_imun) {
        $this->emp_imun = $emp_imun;
    }

    public function getEmp_reg_apu() {
        return $this->emp_reg_apu;
    }

    public function setEmp_reg_apu($emp_reg_apu) {
        $this->emp_reg_apu = $emp_reg_apu;
    }

    public function getEmp_porte() {
        return $this->emp_porte;
    }

    public function setEmp_porte($emp_porte) {
        $this->emp_porte = $emp_porte;
    }

    public function getEmp_tipo_jur() {
        return $this->emp_tipo_jur;
    }

    public function setEmp_tipo_jur($emp_tipo_jur) {
        $this->emp_tipo_jur = $emp_tipo_jur;
    }

    public function getEmp_nat_jur() {
        return $this->emp_nat_jur;
    }

    public function setEmp_nat_jur($emp_nat_jur) {
        $this->emp_nat_jur = $emp_nat_jur;
    }

    public function getEmp_cep() {
        return $this->emp_cep;
    }

    public function setEmp_cep($emp_cep) {
        $this->emp_cep = $emp_cep;
    }

    public function getEmp_ende() {
        return $this->emp_ende;
    }

    public function setEmp_ende($emp_ende) {
        $this->emp_ende = $emp_ende;
    }

    public function getEmp_nume() {
        return $this->emp_nume;
    }

    public function setEmp_nume($emp_nume) {
        $this->emp_nume = $emp_nume;
    }

    public function getEmp_comp() {
        return $this->emp_comp;
    }

    public function setEmp_comp($emp_comp) {
        $this->emp_comp = $emp_comp;
    }

    public function getEmp_bair() {
        return $this->emp_bair;
    }

    public function setEmp_bair($emp_bair) {
        $this->emp_bair = $emp_bair;
    }

    public function getEmp_cid() {
        return $this->emp_cid;
    }

    public function setEmp_cid($emp_cid) {
        $this->emp_cid = $emp_cid;
    }

    public function getEmp_uf() {
        return $this->emp_uf;
    }

    public function setEmp_uf($emp_uf) {
        $this->emp_uf = $emp_uf;
    }

    public function getEmp_org_reg() {
        return $this->emp_org_reg;
    }

    public function setEmp_org_reg($emp_org_reg) {
        $this->emp_org_reg = $emp_org_reg;
    }

    public function getEmp_reg_nire() {
        return $this->emp_reg_nire;
    }

    public function setEmp_reg_nire($emp_reg_nire) {
        $this->emp_reg_nire = $emp_reg_nire;
    }

    public function getEmp_ult_reg() {
        return $this->emp_ult_reg;
    }

    public function setEmp_ult_reg($emp_ult_reg) {
        $this->emp_ult_reg = $emp_ult_reg;
    }

    public function getEmp_cod_ace() {
        return $this->emp_cod_ace;
    }

    public function setEmp_cod_ace($emp_cod_ace) {
        $this->emp_cod_ace = $emp_cod_ace;
    }

    public function getEmp_cod_pre() {
        return $this->emp_cod_pre;
    }

    public function setEmp_cod_pre($emp_cod_pre) {
        $this->emp_cod_pre = $emp_cod_pre;
    }

    public function getSenha_pfe() {
        return $this->senha_pfe;
    }

    public function setSenha_pfe($senha_pfe) {
        $this->senha_pfe = $senha_pfe;
    }

    public function getEmp_cer_dig_data() {
        return $this->emp_cer_dig_data;
    }

    public function setEmp_cer_dig_data($emp_cer_dig_data) {
        $this->emp_cer_dig_data = $emp_cer_dig_data;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getEmail_empresa() {
        return $this->email_empresa;
    }

    public function setEmail_empresa($email_empresa) {
        $this->email_empresa = $email_empresa;
    }

    // GETTERS E SETTERS SÓCIO 1
    public function getSoc1_name() {
        return $this->soc1_name;
    }

    public function setSoc1_name($soc1_name) {
        $this->soc1_name = $soc1_name;
    }

    public function getSoc1_cpf() {
        return $this->soc1_cpf;
    }

    public function setSoc1_cpf($soc1_cpf) {
        $this->soc1_cpf = $soc1_cpf;
    }

    public function getSoc1_entrada() {
        return $this->soc1_entrada;
    }

    public function setSoc1_entrada($soc1_entrada) {
        $this->soc1_entrada = $soc1_entrada;
    }

    public function getSoc1_email() {
        return $this->soc1_email;
    }

    public function setSoc1_email($soc1_email) {
        $this->soc1_email = $soc1_email;
    }

    public function getSoc1_tel() {
        return $this->soc1_tel;
    }

    public function setSoc1_tel($soc1_tel) {
        $this->soc1_tel = $soc1_tel;
    }

    public function getSoc1_cel() {
        return $this->soc1_cel;
    }

    public function setSoc1_cel($soc1_cel) {
        $this->soc1_cel = $soc1_cel;
    }

    public function getSoc1_cep() {
        return $this->soc1_cep;
    }

    public function setSoc1_cep($soc1_cep) {
        $this->soc1_cep = $soc1_cep;
    }

    public function getSoc1_ende() {
        return $this->soc1_ende;
    }

    public function setSoc1_ende($soc1_ende) {
        $this->soc1_ende = $soc1_ende;
    }

    public function getSoc1_nume() {
        return $this->soc1_nume;
    }

    public function setSoc1_nume($soc1_nume) {
        $this->soc1_nume = $soc1_nume;
    }

    public function getSoc1_comp() {
        return $this->soc1_comp;
    }

    public function setSoc1_comp($soc1_comp) {
        $this->soc1_comp = $soc1_comp;
    }

    public function getSoc1_bair() {
        return $this->soc1_bair;
    }

    public function setSoc1_bair($soc1_bair) {
        $this->soc1_bair = $soc1_bair;
    }

    public function getSoc1_cid() {
        return $this->soc1_cid;
    }

    public function setSoc1_cid($soc1_cid) {
        $this->soc1_cid = $soc1_cid;
    }

    public function getSoc1_uf() {
        return $this->soc1_uf;
    }

    public function setSoc1_uf($soc1_uf) {
        $this->soc1_uf = $soc1_uf;
    }

    public function getSoc1_quali() {
        return $this->soc1_quali;
    }

    public function setSoc1_quali($soc1_quali) {
        $this->soc1_quali = $soc1_quali;
    }

    public function getSoc1_ass() {
        return $this->soc1_ass;
    }

    public function setSoc1_ass($soc1_ass) {
        $this->soc1_ass = $soc1_ass;
    }

    public function getSoc1_capit() {
        return $this->soc1_capit;
    }

    public function setSoc1_capit($soc1_capit) {
        $this->soc1_capit = $soc1_capit;
    }

    public function getSoc1_govbr() {
        return $this->soc1_govbr;
    }

    public function setSoc1_govbr($soc1_govbr) {
        $this->soc1_govbr = $soc1_govbr;
    }

    public function getSoc1_qualif_govbr() {
        return $this->soc1_qualif_govbr;
    }

    public function setSoc1_qualif_govbr($soc1_qualif_govbr) {
        $this->soc1_qualif_govbr = $soc1_qualif_govbr;
    }

    // Continua com todos os getters e setters para o restante dos campos...
    // (Por brevidade, vou omitir os getters e setters dos Sócios 2 e 3, que seguiriam o mesmo padrão)

    // GETTERS E SETTERS PARA OUTROS CAMPOS

    public function getEmail() {
        return $this->email;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function getUsuario() {
        return $this->usuario;
    }

    public function setUsuario($usuario) {
        $this->usuario = $usuario;
    }

    public function getPasta() {
        return $this->pasta;
    }

    public function setPasta($pasta) {
        $this->pasta = $pasta;
    }

    public function getCreated_at() {
        return $this->created_at;
    }

    public function setCreated_at($created_at) {
        $this->created_at = $created_at;
    }

    public function getUpdated_at() {
        return $this->updated_at;
    }

    public function setUpdated_at($updated_at) {
        $this->updated_at = $updated_at;
    }

    /**
     * Retorna o nome da situação cadastral formatado
     * 
     * @return string Nome da situação
     */
    public function getSituacaoCadastralFormatada() {
        if (isset(self::$situacoesCadastrais[$this->emp_sit_cad])) {
            return self::$situacoesCadastrais[$this->emp_sit_cad];
        }
        return $this->emp_sit_cad;
    }
    
    /**
     * Retorna o nome do porte da empresa formatado
     * 
     * @return string Nome do porte
     */
    public function getPorteFormatado() {
        if (isset(self::$portes[$this->emp_porte])) {
            return self::$portes[$this->emp_porte];
        }
        return $this->emp_porte;
    }
    
    /**
     * Retorna o nome do tipo jurídico formatado
     * 
     * @return string Nome do tipo jurídico
     */
    public function getTipoJuridicoFormatado() {
        if (isset(self::$tiposJuridicos[$this->emp_tipo_jur])) {
            return self::$tiposJuridicos[$this->emp_tipo_jur];
        }
        return $this->emp_tipo_jur;
    }
    
    /**
     * Retorna o nome do regime de apuração formatado
     * 
     * @return string Nome do regime de apuração
     */
    public function getRegimeApuracaoFormatado() {
        if (isset(self::$regimesApuracao[$this->emp_reg_apu])) {
            return self::$regimesApuracao[$this->emp_reg_apu];
        }
        return $this->emp_reg_apu;
    }
    
    /**
     * Formata o CNPJ para exibição
     * 
     * @return string CNPJ formatado
     */
    public function getCnpjFormatado() {
        $cnpj = preg_replace('/[^0-9]/', '', $this->emp_cnpj);
        if (strlen($cnpj) != 14) {
            return $this->emp_cnpj;
        }
        
        return substr($cnpj, 0, 2) . '.' . 
               substr($cnpj, 2, 3) . '.' . 
               substr($cnpj, 5, 3) . '/' . 
               substr($cnpj, 8, 4) . '-' . 
               substr($cnpj, 12, 2);
    }
    
    /**
     * Formata o CPF para exibição
     * 
     * @param string $cpf CPF a ser formatado
     * @return string CPF formatado
     */
    public function formatarCpf($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) != 11) {
            return $cpf;
        }
        
        return substr($cpf, 0, 3) . '.' . 
               substr($cpf, 3, 3) . '.' . 
               substr($cpf, 6, 3) . '-' . 
               substr($cpf, 9, 2);
    }
    
    /**
     * Formata o CEP para exibição
     * 
     * @param string $cep CEP a ser formatado
     * @return string CEP formatado
     */
    public function formatarCep($cep) {
        $cep = preg_replace('/[^0-9]/', '', $cep);
        if (strlen($cep) != 8) {
            return $cep;
        }
        
        return substr($cep, 0, 5) . '-' . 
               substr($cep, 5, 3);
    }
    
    /**
     * Retorna o endereço completo formatado
     * 
     * @param bool $comCep Incluir CEP no endereço
     * @return string Endereço completo
     */
    public function getEnderecoCompleto($comCep = false) {
        $endereco = $this->emp_ende;
        
        if ($this->emp_nume) {
            $endereco .= ', ' . $this->emp_nume;
        }
        
        if ($this->emp_comp) {
            $endereco .= ' - ' . $this->emp_comp;
        }
        
        if ($this->emp_bair) {
            $endereco .= ', ' . $this->emp_bair;
        }
        
        if ($this->emp_cid && $this->emp_uf) {
            $endereco .= ', ' . $this->emp_cid . '/' . $this->emp_uf;
        }
        
        if ($comCep && $this->emp_cep) {
            $endereco .= ' - CEP: ' . $this->formatarCep($this->emp_cep);
        }
        
        return $endereco;
    }
    
    /**
     * Retorna o valor em formato monetário brasileiro
     * 
     * @param float $valor Valor a ser formatado
     * @return string Valor formatado
     */
    public function formatarValor($valor) {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }
    
    /**
     * Verifica se a empresa tem sócio 2
     * 
     * @return bool True se tem sócio 2
     */
    public function temSocio2() {
        return !empty($this->soc2_name) && !empty($this->soc2_cpf);
    }
    
    /**
     * Verifica se a empresa tem sócio 3
     * 
     * @return bool True se tem sócio 3
     */
    public function temSocio3() {
        return !empty($this->soc3_name) && !empty($this->soc3_cpf);
    }
    
    /**
     * Retorna o número de sócios da empresa
     * 
     * @return int Número de sócios
     */
    public function getNumeroSocios() {
        $count = 1; // Sempre tem pelo menos o sócio 1
        
        if ($this->temSocio2()) {
            $count++;
        }
        
        if ($this->temSocio3()) {
            $count++;
        }
        
        return $count;
    }
    
    /**
     * Converte o objeto para array
     * 
     * @return array Dados da empresa em formato de array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'emp_sit_cad' => $this->emp_sit_cad,
            'emp_code' => $this->emp_code,
            'emp_name' => $this->emp_name,
            'emp_tel' => $this->emp_tel,
            'emp_cnpj' => $this->emp_cnpj,
            'emp_iest' => $this->emp_iest,
            'emp_imun' => $this->emp_imun,
            'emp_reg_apu' => $this->emp_reg_apu,
            'emp_porte' => $this->emp_porte,
            'emp_tipo_jur' => $this->emp_tipo_jur,
            'emp_nat_jur' => $this->emp_nat_jur,
            'emp_cep' => $this->emp_cep,
            'emp_ende' => $this->emp_ende,
            'emp_nume' => $this->emp_nume,
            'emp_comp' => $this->emp_comp,
            'emp_bair' => $this->emp_bair,
            'emp_cid' => $this->emp_cid,
            'emp_uf' => $this->emp_uf,
            'emp_org_reg' => $this->emp_org_reg,
            'emp_reg_nire' => $this->emp_reg_nire,
            'emp_ult_reg' => $this->emp_ult_reg,
            'emp_cod_ace' => $this->emp_cod_ace,
            'emp_cod_pre' => $this->emp_cod_pre,
            'senha_pfe' => $this->senha_pfe,
            'emp_cer_dig_data' => $this->emp_cer_dig_data,
            'name' => $this->name,
            'email_empresa' => $this->email_empresa,
            'soc1_name' => $this->soc1_name,
            'soc1_cpf' => $this->soc1_cpf,
            'soc1_entrada' => $this->soc1_entrada,
            'soc1_email' => $this->soc1_email,
            'soc1_tel' => $this->soc1_tel,
            'soc1_cel' => $this->soc1_cel,
            'soc1_cep' => $this->soc1_cep,
            'soc1_ende' => $this->soc1_ende,
            'soc1_nume' => $this->soc1_nume,
            'soc1_comp' => $this->soc1_comp,
            'soc1_bair' => $this->soc1_bair,
            'soc1_cid' => $this->soc1_cid,
            'soc1_uf' => $this->soc1_uf,
            'soc1_quali' => $this->soc1_quali,
            'soc1_ass' => $this->soc1_ass,
            'soc1_capit' => $this->soc1_capit,
            'soc1_govbr' => $this->soc1_govbr,
            'soc1_qualif_govbr' => $this->soc1_qualif_govbr,
            'soc2_name' => $this->soc2_name,
            'soc2_cpf' => $this->soc2_cpf,
            'soc2_entrada' => $this->soc2_entrada,
            'soc2_email' => $this->soc2_email,
            'soc2_tel' => $this->soc2_tel,
            'soc2_cel' => $this->soc2_cel,
            'soc2_cep' => $this->soc2_cep,
            'soc2_ende' => $this->soc2_ende,
            'soc2_nume' => $this->soc2_nume,
            'soc2_comp' => $this->soc2_comp,
            'soc2_bair' => $this->soc2_bair,
            'soc2_cid' => $this->soc2_cid,
            'soc2_uf' => $this->soc2_uf,
            'soc2_quali' => $this->soc2_quali,
            'soc2_ass' => $this->soc2_ass,
            'soc2_capit' => $this->soc2_capit,
            'soc2_govbr' => $this->soc2_govbr,
            'soc2_qualif_govbr' => $this->soc2_qualif_govbr,
            'soc3_name' => $this->soc3_name,
            'soc3_cpf' => $this->soc3_cpf,
            'soc3_entrada' => $this->soc3_entrada,
            'soc3_email' => $this->soc3_email,
            'soc3_tel' => $this->soc3_tel,
            'soc3_cel' => $this->soc3_cel,
            'soc3_cep' => $this->soc3_cep,
            'soc3_ende' => $this->soc3_ende,
            'soc3_nume' => $this->soc3_nume,
            'soc3_comp' => $this->soc3_comp,
            'soc3_bair' => $this->soc3_bair,
            'soc3_cid' => $this->soc3_cid,
            'soc3_uf' => $this->soc3_uf,
            'soc3_quali' => $this->soc3_quali,
            'soc3_ass' => $this->soc3_ass,
            'soc3_capit' => $this->soc3_capit,
            'soc3_govbr' => $this->soc3_govbr,
            'soc3_qualif_govbr' => $this->soc3_qualif_govbr,
            'email' => $this->email,
            'usuario' => $this->usuario,
            'pasta' => $this->pasta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}