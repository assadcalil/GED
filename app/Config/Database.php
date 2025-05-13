<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Configuração do Banco de Dados
 * 
 * Este arquivo contém as configurações de conexão com o banco de dados
 * e funções auxiliares para manipulação de dados.
 */

class Database {
    // Configurações de conexão
    private static $host = 'localhost';
    private static $dbname = 'estrela_contabilidade';
    private static $username = 'root';
    private static $password = '36798541';
    private static $charset = 'utf8mb4';
    private static $pdo = null;
    public $conn;

    /**
     * Obtém conexão com o banco de dados (singleton)
     */
    public static function getConnection() {
        // Se já existe uma conexão ativa, retorna-a
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        try {
            // Configurar DSN
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=" . self::$charset;
            
            // Opções para a conexão PDO
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true
            ];

            // Criar nova conexão PDO
            self::$pdo = new PDO($dsn, self::$username, self::$password, $options);
            
            return self::$pdo;
        } catch (PDOException $e) {
            // Registrar erro de conexão e exibir mensagem amigável
            if (class_exists('ErrorHandler')) {
                ErrorHandler::handleException($e);
            } else {
                // Fallback caso ErrorHandler não esteja disponível
                die("Erro de conexão com o banco de dados: " . $e->getMessage());
            }
        }
    }

    /**
     * Método de compatibilidade para o antigo sistema
     */
    public function dbConnection() {
        $this->conn = self::getConnection();
        return $this->conn;
    }

    /**
     * Método para executar consultas
     */
    public function runQuery($sql) {
        $stmt = self::getConnection()->prepare($sql);
        return $stmt;
    }

    /**
     * Executa uma consulta e retorna o Statement
     */
    public static function query($sql, $params = []) {
        try {
            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Registrar erro de consulta
            if (class_exists('ErrorHandler')) {
                ErrorHandler::handleException($e);
            } else {
                die("Erro na consulta SQL: " . $e->getMessage());
            }
        }
    }

    /**
     * Seleciona um único registro
     */
    public static function selectOne($sql, $params = []) {
        $stmt = self::query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Seleciona múltiplos registros
     */
    public static function select($sql, $params = []) {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insere um registro e retorna o ID
     */
    public static function insert($table, $data) {
        // Construir os campos e placeholders
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $fieldsStr = implode(', ', $fields);
        $placeholdersStr = implode(', ', $placeholders);
        
        // Preparar a consulta SQL
        $sql = "INSERT INTO {$table} ({$fieldsStr}) VALUES ({$placeholdersStr})";
        
        try {
            // Executar a inserção
            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute(array_values($data));
            
            // Retornar o ID do registro inserido
            return self::getConnection()->lastInsertId();
        } catch (PDOException $e) {
            // Registrar erro de inserção
            if (class_exists('ErrorHandler')) {
                ErrorHandler::handleException($e);
            } else {
                die("Erro ao inserir registro: " . $e->getMessage());
            }
        }
    }

    /**
     * Atualiza um registro e retorna o número de linhas afetadas
     */
    public static function update($table, $data, $where, $whereParams = []) {
        // Construir os campos para atualização
        $sets = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $sets[] = "{$field} = ?";
            $params[] = $value;
        }
        
        $setsStr = implode(', ', $sets);
        
        // Construir a consulta SQL
        $sql = "UPDATE {$table} SET {$setsStr} WHERE {$where}";
        
        // Adicionar parâmetros da cláusula WHERE
        $params = array_merge($params, $whereParams);
        
        try {
            // Executar a atualização
            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute($params);
            
            // Retornar o número de linhas afetadas
            return $stmt->rowCount();
        } catch (PDOException $e) {
            // Registrar erro de atualização
            if (class_exists('ErrorHandler')) {
                ErrorHandler::handleException($e);
            } else {
                die("Erro ao atualizar registro: " . $e->getMessage());
            }
        }
    }

    /**
     * Remove um registro e retorna o número de linhas afetadas
     */
    public static function delete($table, $where, $params = []) {
        // Construir a consulta SQL
        $sql = "DELETE FROM {$table} WHERE {$where}";
        
        try {
            // Executar a remoção
            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute($params);
            
            // Retornar o número de linhas afetadas
            return $stmt->rowCount();
        } catch (PDOException $e) {
            // Registrar erro de remoção
            if (class_exists('ErrorHandler')) {
                ErrorHandler::handleException($e);
            } else {
                die("Erro ao remover registro: " . $e->getMessage());
            }
        }
    }

    /**
     * Executa uma consulta (compatibilidade)
     */
    public static function execute($sql, $params = []) {
        try {
            $stmt = self::getConnection()->prepare($sql);
            $result = $stmt->execute($params);
            return $result;
        } catch (PDOException $e) {
            if (class_exists('ErrorHandler')) {
                ErrorHandler::handleException($e);
            } else {
                die("Erro ao executar consulta: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Inicia uma transação
     */
    public static function beginTransaction() {
        return self::getConnection()->beginTransaction();
    }

    /**
     * Confirma uma transação
     */
    public static function commit() {
        return self::getConnection()->commit();
    }

    /**
     * Reverte uma transação
     */
    public static function rollback() {
        return self::getConnection()->rollBack();
    }

    /**
     * Retorna o último ID inserido
     */
    public static function lastInsertId() {
        return self::getConnection()->lastInsertId();
    }

    /**
     * Escapa um valor para uso seguro em consultas SQL
     */
    public static function escape($value) {
        return self::getConnection()->quote($value);
    }
}
?>