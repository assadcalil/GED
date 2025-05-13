<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Controlador de Usuários
 */

class UserController {
    private $db;
    private $user;
    
    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db);
    }
    
    /**
     * Renderiza a página de listagem de usuários
     */
    public function listUsers() {
        // Verificar permissões (apenas Admin e Editor)
        if (!Auth::hasPermission([Auth::ADMIN, Auth::EDITOR])) {
            header('Location: /dashboard');
            exit;
        }
        
        // Obter parâmetros de filtro e paginação
        $type = isset($_GET['type']) ? (int)$_GET['type'] : null;
        $search = isset($_GET['search']) ? $_GET['search'] : null;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Obter usuários
        $users = $this->user->getAll($type, $search, $limit, $offset);
        
        // Incluir a view
        include_once '../views/users/list.php';
    }
    
    /**
     * Renderiza a página de criação de usuário
     */
    public function createUserForm() {
        // Verificar permissões (apenas Admin e Editor)
        if (!Auth::hasPermission([Auth::ADMIN, Auth::EDITOR])) {
            header('Location: /dashboard');
            exit;
        }
        
        // Array com tipos de usuário para o select
        $userTypes = User::$userTypes;
        
        // Incluir a view
        include_once '../views/users/create.php';
    }
    
    /**
     * Processa o formulário de criação de usuário
     */
    public function createUser() {
        // Verificar permissões (apenas Admin e Editor)
        if (!Auth::hasPermission([Auth::ADMIN, Auth::EDITOR])) {
            header('Location: /dashboard');
            exit;
        }
        
        // Verificar se o formulário foi enviado
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /users/create.php');
            exit;
        }
        
        // Validar dados
        $errors = [];
        
        if (empty($_POST['name'])) {
            $errors[] = 'O nome é obrigatório.';
        }
        
        if (empty($_POST['email'])) {
            $errors[] = 'O email é obrigatório.';
        } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido.';
        }
        
        if (empty($_POST['password'])) {
            $errors[] = 'A senha é obrigatória.';
        } elseif (strlen($_POST['password']) < 6) {
            $errors[] = 'A senha deve ter pelo menos 6 caracteres.';
        }
        
        if (empty($_POST['type'])) {
            $errors[] = 'O tipo de usuário é obrigatório.';
        }
        
        // Se houver erros, retornar ao formulário
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            header('Location: /users/create.php');
            exit;
        }
        
        // Preparar dados
        $userData = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'type' => (int)$_POST['type'],
            'status' => isset($_POST['status']) ? 1 : 0,
            'phone' => $_POST['phone'] ?? '',
            'document' => $_POST['document'] ?? '',
            'address' => $_POST['address'] ?? '',
            'city' => $_POST['city'] ?? '',
            'state' => $_POST['state'] ?? '',
            'postal_code' => $_POST['postal_code'] ?? ''
        ];
        
        // Criar usuário
        $result = $this->user->create($userData);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            header('Location: /users/list.php');
            exit;
        } else {
            $_SESSION['errors'] = [$result['message']];
            $_SESSION['form_data'] = $_POST;
            header('Location: /users/create.php');
            exit;
        }
    }
    
    /**
     * Renderiza a página de edição de usuário
     */
    public function editUserForm($id) {
        // Verificar permissões (apenas Admin e Editor)
        if (!Auth::hasPermission([Auth::ADMIN, Auth::EDITOR])) {
            header('Location: /dashboard');
            exit;
        }
        
        // Obter dados do usuário
        $userData = $this->user->getById($id);
        
        if (!$userData) {
            $_SESSION['errors'] = ['Usuário não encontrado.'];
            header('Location: /users/list.php');
            exit;
        }
        
        // Array com tipos de usuário para o select
        $userTypes = User::$userTypes;
        
        // Incluir a view
        include_once '../views/users/edit.php';
    }
    
    /**
     * Processa o formulário de edição de usuário
     */
    public function updateUser($id) {
        // Verificar permissões (apenas Admin e Editor)
        if (!Auth::hasPermission([Auth::ADMIN, Auth::EDITOR])) {
            header('Location: /dashboard');
            exit;
        }
        
        // Verificar se o formulário foi enviado
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /users/edit.php?id='.$id);
            exit;
        }
        
        // Validar dados
        $errors = [];
        
        if (empty($_POST['name'])) {
            $errors[] = 'O nome é obrigatório.';
        }
        
        if (empty($_POST['email'])) {
            $errors[] = 'O email é obrigatório.';
        } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido.';
        }
        
        if (!empty($_POST['password']) && strlen($_POST['password']) < 6) {
            $errors[] = 'A senha deve ter pelo menos 6 caracteres.';
        }
        
        if (empty($_POST['type'])) {
            $errors[] = 'O tipo de usuário é obrigatório.';
        }
        
        // Se houver erros, retornar ao formulário
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            header('Location: /users/edit.php?id='.$id);
            exit;
        }
        
        // Preparar dados
        $userData = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'type' => (int)$_POST['type'],
            'status' => isset($_POST['status']) ? 1 : 0,
            'phone' => $_POST['phone'] ?? '',
            'document' => $_POST['document'] ?? '',
            'address' => $_POST['address'] ?? '',
            'city' => $_POST['city'] ?? '',
            'state' => $_POST['state'] ?? '',
            'postal_code' => $_POST['postal_code'] ?? ''
        ];
        
        // Adicionar senha se fornecida
        if (!empty($_POST['password'])) {
            $userData['password'] = $_POST['password'];
        }
        
        // Atualizar usuário
        $result = $this->user->update($id, $userData);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            header('Location: /users/list.php');
            exit;
        } else {
            $_SESSION['errors'] = [$result['message']];
            $_SESSION['form_data'] = $_POST;
            header('Location: /users/edit.php?id='.$id);
            exit;
        }
    }
    
    /**
     * Processa a exclusão de usuário
     */
    public function deleteUser($id) {
        // Verificar permissões (apenas Admin)
        if (!Auth::hasPermission([Auth::ADMIN])) {
            header('Location: /dashboard');
            exit;
        }
        
        // Excluir usuário
        $result = $this->user->delete($id);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['errors'] = [$result['message']];
        }
        
        header('Location: /users/list.php');
        exit;
    }
}
?>