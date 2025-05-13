<?php

class Imposto {
    private $id;
    private $codigo;
    private $nome;
    private $cpf;
    private $cep;
    private $ende; 
    private $num;
    private $comple;
    private $bairro;
    private $cidade;
    private $estado;
    private $email;
    private $tel;
    private $cel;
    private $valor2024;
    private $valor2025;
    private $vencimento;
    private $status_boleto_2024;
    private $status_boleto_2025;
    private $usuario;
    private $data;
    private $data_pagamento_2024;
    private $data_pagamento_2025;

    function __construct() {
        // Construtor vazio
    }

    // Getters
    function getId() {
        return $this->id;
    }

    function getCodigo() {
        return $this->codigo;
    }

    function getNome() {
        return $this->nome;
    }

    function getCpf() {
        return $this->cpf;
    }

    function getCep() {
        return $this->cep;
    }

    function getEnde() {
        return $this->ende;
    }

    function getNum() {
        return $this->num;
    }

    function getComple() {
        return $this->comple;
    }

    function getBairro() {
        return $this->bairro;
    }

    function getCidade() {
        return $this->cidade;
    }

    function getEstado() {
        return $this->estado;
    }

    function getEmail() {
        return $this->email;
    }

    function getTel() {
        return $this->tel;
    }

    function getCel() {
        return $this->cel;
    }
    
    function getValor2024() {
        return $this->valor2024;
    }
    
    function getValor2025() {
        return $this->valor2025;
    }
    
    function getVencimento() {
        return $this->vencimento;
    }

    function getStatus_boleto_2024() {
        return $this->status_boleto_2024;
    }

    function getStatus_boleto_2025() {
        return $this->status_boleto_2025;
    }

    function getUsuario() {
        return $this->usuario;
    }
    
    function getData() {
        return $this->data;
    }
    
    function getData_pagamento_2024() {
        return $this->data_pagamento_2024;
    }
    
    function getData_pagamento_2025() {
        return $this->data_pagamento_2025;
    }

    // Setters
    function setId($id) {
        $this->id = $id;
    }

    function setCodigo($codigo) {
        $this->codigo = $codigo;
    }

    function setNome($nome) {
        $this->nome = $nome;
    }

    function setCpf($cpf) {
        $this->cpf = $cpf;
    }

    function setCep($cep) {
        $this->cep = $cep;
    }

    function setEnde($ende) {
        $this->ende = $ende;
    }

    function setNum($num) {
        $this->num = $num;
    }

    function setComple($comple) {
        $this->comple = $comple;
    }

    function setBairro($bairro) {
        $this->bairro = $bairro;
    }

    function setCidade($cidade) {
        $this->cidade = $cidade;
    }

    function setEstado($estado) {
        $this->estado = $estado;
    }

    function setEmail($email) {
        $this->email = $email;
    }

    function setTel($tel) {
        $this->tel = $tel;
    }

    function setCel($cel) {
        $this->cel = $cel;
    }

    function setValor2024($valor2024) {
        $this->valor2024 = $valor2024;
    }

    function setValor2025($valor2025) {
        $this->valor2025 = $valor2025;
    }
    
    function setVencimento($vencimento) {
        $this->vencimento = $vencimento;
    }

    function setStatus_boleto_2024($status_boleto_2024) {
        $this->status_boleto_2024 = $status_boleto_2024;
    }

    function setStatus_boleto_2025($status_boleto_2025) {
        $this->status_boleto_2025 = $status_boleto_2025;
    }

    function setUsuario($usuario) {
        $this->usuario = $usuario;
    }
    
    function setData($data) {
        $this->data = $data;
    }
    
    function setData_pagamento_2024($data_pagamento_2024) {
        $this->data_pagamento_2024 = $data_pagamento_2024;
    }
    
    function setData_pagamento_2025($data_pagamento_2025) {
        $this->data_pagamento_2025 = $data_pagamento_2025;
    }
}