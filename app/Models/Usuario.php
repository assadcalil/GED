<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Modelo de Usuário
 */

class User {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Constantes para tipos de usuário
     */
    const ADMIN = 1;
    const EDITOR = 2;
    const TAX = 3;
    const EMPLOYEE = 4;
    const FINANCIAL = 5;
    const CLIENT = 6;
    
    /**
     * Array com nomes dos tipos de usuário
     */
    public static $userTypes = [
        self::ADMIN => 'Administrador',
        self::EDITOR => 'Editor',
        self::TAX => 'Imposto de Renda',
        self::EMPLOYEE => 'Funcionário',
        self::FINANCIAL => 'Financeiro',
        self::CLIENT => 'Cliente'
    ];
    
    /**
     * Cria um novo usuário
     */
    public function create($data) {
        try {
            // Preparar a query
            $query = "INSERT INTO users 
                     (name, email, password, type, status, created_at, phone, document, address, city, state, postal_code) 
                     VALUES 
                     (:name, :email, :password, :type, :status, NOW(), :phone, :document, :address, :city, :state, :postal_code)";
            
            // Verificar se o email já existe
            if ($this->emailExists($data['email'])) {
                return [
                    'success' => false,
                    'message' => 'Este email já está cadastrado no sistema.'
                ];
            }
            
            // Hash da senha
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Preparar statement
            $stmt = $this->db->prepare($query);
            
            // Sanitizar e vincular parâmetros
            $stmt->bindParam(':name', htmlspecialchars(strip_tags($data['name'])));
            $stmt->bindParam(':email', htmlspecialchars(strip_tags($data['email'])));
            $stmt->bindParam(':password', $passwordHash);
            $stmt->bindParam(':type', $data['type'], PDO::PARAM_INT);
            $stmt->bindParam(':status', $data['status'], PDO::PARAM_INT);
            $stmt->bindParam(':phone', htmlspecialchars(strip_tags($data['phone'] ?? '')));
            $stmt->bindParam(':document', htmlspecialchars(strip_tags($data['document'] ?? '')));
            $stmt->bindParam(':address', htmlspecialchars(strip_tags($data['address'] ?? '')));
            $stmt->bindParam(':city', htmlspecialchars(strip_tags($data['city'] ?? '')));
            $stmt->bindParam(':state', htmlspecialchars(strip_tags($data['state'] ?? '')));
            $stmt->bindParam(':postal_code', htmlspecialchars(strip_tags($data['postal_code'] ?? '')));
            
            // Executar query
            if($stmt->execute()) {
                return [
                    'success' => true,
                    'id' => $this->db->lastInsertId(),
                    'message' => 'Usuário criado com sucesso!'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Erro ao criar usuário.'
            ];
            
        } catch(PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro de banco de dados: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verifica se email já existe
     */
    public function emailExists($email) {
        $query = "SELECT COUNT(*) FROM users WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return (int)$stmt->fetchColumn() > 0;
    }
    
    /**
     * Lista todos os usuários com opção de filtro por tipo
     */
    public function getAll($type = null, $search = null, $limit = 20, $offset = 0) {
        try {
            $query = "SELECT id, name, email, type, status, created_at, last_login 
                     FROM users 
                     WHERE 1=1";
                     
            $params = [];
            
            // Adicionar filtro por tipo
            if ($type !== null) {
                $query .= " AND type = :type";
                $params[':type'] = $type;
            }
            
            // Adicionar busca
            if ($search !== null) {
                $query .= " AND (name LIKE :search OR email LIKE :search)";
                $params[':search'] = "%$search%";
            }
            
            // Ordenação e paginação
            $query .= " ORDER BY name ASC LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            $stmt = $this->db->prepare($query);
            
            // Vincular parâmetros
            foreach ($params as $key => $value) {
                if ($key == ':limit' || $key == ':offset') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao listar usuários: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtém um usuário pelo ID
     */
    public function getById($id) {
        try {
            $query = "SELECT id, name, email, type, status, phone, document, 
                     address, city, state, postal_code, created_at, last_login 
                     FROM users 
                     WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao buscar usuário: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Atualiza um usuário
     */
    public function update($id, $data) {
        try {
            // Construir a query de atualização
            $query = "UPDATE users SET 
                     name = :name, 
                     email = :email, 
                     type = :type, 
                     status = :status,
                     phone = :phone,
                     document = :document,
                     address = :address,
                     city = :city,
                     state = :state,
                     postal_code = :postal_code";
            
            // Se a senha for fornecida, atualizá-la
            if (!empty($data['password'])) {
                $query .= ", password = :password";
            }
            
            $query .= " WHERE id = :id";
            
            // Verificar se o novo email já existe (se for diferente do atual)
            $currentUser = $this->getById($id);
            if ($currentUser['email'] != $data['email'] && $this->emailExists($data['email'])) {
                return [
                    'success' => false,
                    'message' => 'Este email já está sendo usado por outro usuário.'
                ];
            }
            
            $stmt = $this->db->prepare($query);
            
            // Sanitizar e vincular parâmetros
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':name', htmlspecialchars(strip_tags($data['name'])));
            $stmt->bindParam(':email', htmlspecialchars(strip_tags($data['email'])));
            $stmt->bindParam(':type', $data['type'], PDO::PARAM_INT);
            $stmt->bindParam(':status', $data['status'], PDO::PARAM_INT);
            $stmt->bindParam(':phone', htmlspecialchars(strip_tags($data['phone'] ?? '')));
            $stmt->bindParam(':document', htmlspecialchars(strip_tags($data['document'] ?? '')));
            $stmt->bindParam(':address', htmlspecialchars(strip_tags($data['address'] ?? '')));
            $stmt->bindParam(':city', htmlspecialchars(strip_tags($data['city'] ?? '')));
            $stmt->bindParam(':state', htmlspecialchars(strip_tags($data['state'] ?? '')));
            $stmt->bindParam(':postal_code', htmlspecialchars(strip_tags($data['postal_code'] ?? '')));
            
            // Se a senha for fornecida, fazer o hash e vincular
            if (!empty($data['password'])) {
                $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmt->bindParam(':password', $passwordHash);
            }
            
            // Executar query
            if($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Usuário atualizado com sucesso!'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Erro ao atualizar usuário.'
            ];
            
        } catch(PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro de banco de dados: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Exclui um usuário
     */
    public function delete($id) {
        try {
            $query = "DELETE FROM users WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Usuário excluído com sucesso!'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Erro ao excluir usuário.'
            ];
            
        } catch(PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro de banco de dados: ' . $e->getMessage()
            ];
        }
    }
}
?>