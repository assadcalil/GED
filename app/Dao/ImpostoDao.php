<?php

class ImpostoDAO {
    private $conn;

    // Método construtor
    public function __construct() {
        $database = new Database();
        $db = $database->dbConnection();
        $this->conn = $db;
    }

    // Método para rodar query
    public function runQuery($sql) {
        $stmt = $this->conn->prepare($sql);
        return $stmt;
    }

    // Método para adicionar um cliente de imposto
    public function adicionarImposto(Imposto $imposto) {
        try {
            $codigo = $imposto->getCodigo();
            $nome = $imposto->getNome();
            $cpf = $imposto->getCpf();
            $cep = $imposto->getCep();
            $ende = $imposto->getEnde();
            $num = $imposto->getNum();
            $comple = $imposto->getComple();
            $bairro = $imposto->getBairro();
            $cidade = $imposto->getCidade();
            $estado = $imposto->getEstado();
            $email = $imposto->getEmail();
            $tel = $imposto->getTel();
            $cel = $imposto->getCel();
            $valor2024 = $imposto->getValor2024();
            $valor2025 = $imposto->getValor2025();
            $vencimento = $imposto->getVencimento();
            $status_boleto_2024 = $imposto->getStatus_boleto_2024();
            $status_boleto_2025 = $imposto->getStatus_boleto_2025();
            $usuario = $imposto->getUsuario();
            $data = date('Y-m-d H:i:s');

            $stmt = $this->conn->prepare("INSERT INTO impostos (codigo, nome, cpf, cep, ende, num, comple, bairro, cidade, 
                                          estado, email, tel, cel, valor2024, valor2025, vencimento, status_boleto_2024, 
                                          status_boleto_2025, usuario, data) 
                                          VALUES (:codigo, :nome, :cpf, :cep, :ende, :num, :comple, :bairro, :cidade, 
                                          :estado, :email, :tel, :cel, :valor2024, :valor2025, :vencimento, :status_boleto_2024, 
                                          :status_boleto_2025, :usuario, :data)");

            $stmt->bindParam(":codigo", $codigo, PDO::PARAM_STR);
            $stmt->bindParam(":nome", $nome, PDO::PARAM_STR);
            $stmt->bindParam(":cpf", $cpf, PDO::PARAM_STR);
            $stmt->bindParam(":cep", $cep, PDO::PARAM_STR);
            $stmt->bindParam(":ende", $ende, PDO::PARAM_STR);
            $stmt->bindParam(":num", $num, PDO::PARAM_STR);
            $stmt->bindParam(":comple", $comple, PDO::PARAM_STR);
            $stmt->bindParam(":bairro", $bairro, PDO::PARAM_STR);
            $stmt->bindParam(":cidade", $cidade, PDO::PARAM_STR);
            $stmt->bindParam(":estado", $estado, PDO::PARAM_STR);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->bindParam(":tel", $tel, PDO::PARAM_STR);
            $stmt->bindParam(":cel", $cel, PDO::PARAM_STR);
            $stmt->bindParam(":valor2024", $valor2024, PDO::PARAM_STR);
            $stmt->bindParam(":valor2025", $valor2025, PDO::PARAM_STR);
            $stmt->bindParam(":vencimento", $vencimento, PDO::PARAM_STR);
            $stmt->bindParam(":status_boleto_2024", $status_boleto_2024, PDO::PARAM_STR);
            $stmt->bindParam(":status_boleto_2025", $status_boleto_2025, PDO::PARAM_STR);
            $stmt->bindParam(":usuario", $usuario, PDO::PARAM_STR);
            $stmt->bindParam(":data", $data, PDO::PARAM_STR);
            
            $stmt->execute();

            // Criação da pasta do cliente se necessário
            $diretorio = "../PastasImpostos/" . $nome;
            if (!file_exists($diretorio)) {
                mkdir($diretorio, 0777, true);
                chmod($diretorio, 0777);
            }
            
            return 0; // Sucesso
        } catch (Exception $ex) {
            error_log('Erro ao adicionar imposto: ' . $ex->getMessage());
            return 1; // Erro
        }
    }

    // Método para alterar um cliente de imposto
    public function alterarImposto(Imposto $imposto) {
        try {
            $id = $imposto->getId();
            $codigo = $imposto->getCodigo();
            $nome = $imposto->getNome();
            $cpf = $imposto->getCpf();
            $cep = $imposto->getCep();
            $ende = $imposto->getEnde();
            $num = $imposto->getNum();
            $comple = $imposto->getComple();
            $bairro = $imposto->getBairro();
            $cidade = $imposto->getCidade();
            $estado = $imposto->getEstado();
            $email = $imposto->getEmail();
            $tel = $imposto->getTel();
            $cel = $imposto->getCel();
            $valor2024 = $imposto->getValor2024();
            $valor2025 = $imposto->getValor2025();
            $vencimento = $imposto->getVencimento();
            $status_boleto_2024 = $imposto->getStatus_boleto_2024();
            $status_boleto_2025 = $imposto->getStatus_boleto_2025();
            $usuario = $imposto->getUsuario();

            $stmt = $this->conn->prepare("UPDATE impostos SET 
                                          codigo = :codigo, 
                                          nome = :nome, 
                                          cpf = :cpf, 
                                          cep = :cep, 
                                          ende = :ende, 
                                          num = :num, 
                                          comple = :comple, 
                                          bairro = :bairro, 
                                          cidade = :cidade, 
                                          estado = :estado, 
                                          email = :email, 
                                          tel = :tel, 
                                          cel = :cel, 
                                          valor2024 = :valor2024, 
                                          valor2025 = :valor2025, 
                                          vencimento = :vencimento, 
                                          status_boleto_2024 = :status_boleto_2024, 
                                          status_boleto_2025 = :status_boleto_2025, 
                                          usuario = :usuario 
                                          WHERE id = :id");

            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->bindParam(":codigo", $codigo, PDO::PARAM_STR);
            $stmt->bindParam(":nome", $nome, PDO::PARAM_STR);
            $stmt->bindParam(":cpf", $cpf, PDO::PARAM_STR);
            $stmt->bindParam(":cep", $cep, PDO::PARAM_STR);
            $stmt->bindParam(":ende", $ende, PDO::PARAM_STR);
            $stmt->bindParam(":num", $num, PDO::PARAM_STR);
            $stmt->bindParam(":comple", $comple, PDO::PARAM_STR);
            $stmt->bindParam(":bairro", $bairro, PDO::PARAM_STR);
            $stmt->bindParam(":cidade", $cidade, PDO::PARAM_STR);
            $stmt->bindParam(":estado", $estado, PDO::PARAM_STR);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->bindParam(":tel", $tel, PDO::PARAM_STR);
            $stmt->bindParam(":cel", $cel, PDO::PARAM_STR);
            $stmt->bindParam(":valor2024", $valor2024, PDO::PARAM_STR);
            $stmt->bindParam(":valor2025", $valor2025, PDO::PARAM_STR);
            $stmt->bindParam(":vencimento", $vencimento, PDO::PARAM_STR);
            $stmt->bindParam(":status_boleto_2024", $status_boleto_2024, PDO::PARAM_STR);
            $stmt->bindParam(":status_boleto_2025", $status_boleto_2025, PDO::PARAM_STR);
            $stmt->bindParam(":usuario", $usuario, PDO::PARAM_STR);
            
            $stmt->execute();
            
            return 0; // Sucesso
        } catch (Exception $ex) {
            error_log('Erro ao alterar imposto: ' . $ex->getMessage());
            return 1; // Erro
        }
    }

    // Método para atualizar o status do boleto
    public function atualizarStatusBoleto($id, $ano, $status, $data_pagamento = null) {
        try {
            $campo_status = "status_boleto_$ano";
            $campo_data = "data_pagamento_$ano";
            
            $data_atual = $data_pagamento ? $data_pagamento : date('Y-m-d');
            
            $sql = "UPDATE impostos SET $campo_status = :status";
            
            if ($status == 1 || $status == 6) {
                $sql .= ", $campo_data = :data_pagamento";
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->bindParam(":status", $status, PDO::PARAM_INT);
            
            if ($status == 1 || $status == 6) {
                $stmt->bindParam(":data_pagamento", $data_atual, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            return 0; // Sucesso
        } catch (Exception $ex) {
            error_log('Erro ao atualizar status do boleto: ' . $ex->getMessage());
            return 1; // Erro
        }
    }

    // Método para remover um cliente de imposto
    public function removerImposto(Imposto $imposto) {
        try {
            $id = $imposto->getId();
            
            // Obter o nome para referência à pasta
            $stmt = $this->conn->prepare("SELECT nome FROM impostos WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $pasta = $row['nome'];
            
            // Remover o registro do banco de dados
            $stmt = $this->conn->prepare("DELETE FROM impostos WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Opcionalmente, remover a pasta do cliente
            // Observe: considere se você realmente deseja remover os arquivos
            /*
            $diretorio = "../PastasImpostos/" . $pasta;
            if (is_dir($diretorio)) {
                $this->removerDiretorio($diretorio);
            }
            */
            
            return 0; // Sucesso
        } catch (Exception $ex) {
            error_log('Erro ao remover imposto: ' . $ex->getMessage());
            return 1; // Erro
        }
    }
    
    // Método auxiliar para remover diretório recursivamente
    private function removerDiretorio($diretorio) {
        if (!is_dir($diretorio)) {
            return;
        }
        
        $objects = scandir($diretorio);
        foreach ($objects as $object) {
            if ($object == "." || $object == "..") {
                continue;
            }
            
            $caminho = $diretorio . "/" . $object;
            
            if (is_dir($caminho)) {
                $this->removerDiretorio($caminho);
            } else {
                unlink($caminho);
            }
        }
        
        rmdir($diretorio);
    }
}