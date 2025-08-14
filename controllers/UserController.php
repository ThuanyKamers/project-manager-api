<?php
// controllers/UserController.php - Controlador para gerenciar usuários

/*
 * EXPLICAÇÃO: O que é um Controller?
 * 
 * Um controller é como um gerente de restaurante:
 * - Recebe os pedidos dos clientes (requisições da API)
 * - Decide o que fazer com cada pedido
 * - Coordena com a "cozinha" (banco de dados) para preparar a resposta
 * - Entrega o resultado final para o cliente
 * 
 * Este controller específico gerencia tudo relacionado aos usuários:
 * - Criar novos usuários
 * - Listar todos os usuários
 * - Buscar um usuário específico
 * - Atualizar dados de usuários
 * - Deletar usuários
 */

class UserController {
    private $db;
    
    public function __construct() {
        $this->db = getDatabase()->getConnection();
    }
    
    // Lista todos os usuários
    public function getAll() {
        try {
            $stmt = $this->db->query('
                SELECT 
                    id, 
                    name, 
                    email, 
                    created_at,
                    (SELECT COUNT(*) FROM projects WHERE created_by = users.id) as total_projects,
                    (SELECT COUNT(*) FROM tasks WHERE assigned_to = users.id) as total_tasks
                FROM users 
                ORDER BY name
            ');
            
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->sendResponse([
                'success' => true,
                'data' => $users,
                'total' => count($users),
                'message' => 'Usuários listados com sucesso'
            ]);
            
        } catch (PDOException $e) {
            $this->sendError('Erro ao buscar usuários: ' . $e->getMessage());
        }
    }
    
    // Busca um usuário específico pelo ID
    public function getById($id) {
        try {
            $stmt = $this->db->prepare('
                SELECT 
                    u.id, 
                    u.name, 
                    u.email, 
                    u.created_at,
                    (SELECT COUNT(*) FROM projects WHERE created_by = u.id) as total_projects,
                    (SELECT COUNT(*) FROM tasks WHERE assigned_to = u.id) as total_tasks_assigned,
                    (SELECT COUNT(*) FROM tasks WHERE created_by = u.id) as total_tasks_created
                FROM users u 
                WHERE u.id = ?
            ');
            
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $this->sendError('Usuário não encontrado', 404);
                return;
            }
            
            // Busca os projetos criados pelo usuário
            $stmt = $this->db->prepare('
                SELECT id, title, status, deadline 
                FROM projects 
                WHERE created_by = ? 
                ORDER BY created_at DESC
            ');
            $stmt->execute([$id]);
            $user['projects'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Busca as tarefas atribuídas ao usuário
            $stmt = $this->db->prepare('
                SELECT t.id, t.title, t.status, t.priority, t.deadline, p.title as project_title 
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id 
                WHERE t.assigned_to = ? 
                ORDER BY t.deadline ASC
            ');
            $stmt->execute([$id]);
            $user['tasks_assigned'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->sendResponse([
                'success' => true,
                'data' => $user,
                'message' => 'Usuário encontrado com sucesso'
            ]);
            
        } catch (PDOException $e) {
            $this->sendError('Erro ao buscar usuário: ' . $e->getMessage());
        }
    }
    
    // Cria um novo usuário
    public function create() {
        try {
            // Pega os dados enviados na requisição
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validação básica
            if (empty($input['name']) || empty($input['email'])) {
                $this->sendError('Nome e email são obrigatórios');
                return;
            }
            
            // Valida formato do email
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                $this->sendError('Email inválido');
                return;
            }
            
            // Verifica se o email já existe
            $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$input['email']]);
            if ($stmt->fetch()) {
                $this->sendError('Este email já está em uso');
                return;
            }
            
            // Insere o novo usuário
            $stmt = $this->db->prepare('
                INSERT INTO users (name, email) 
                VALUES (?, ?)
            ');
            
            $stmt->execute([
                trim($input['name']),
                trim(strtolower($input['email']))
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Busca o usuário criado para retornar
            $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $newUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->sendResponse([
                'success' => true,
                'data' => $newUser,
                'message' => 'Usuário criado com sucesso'
            ], 201);
            
        } catch (PDOException $e) {
            $this->sendError('Erro ao criar usuário: ' . $e->getMessage());
        }
    }
    
    // Atualiza um usuário existente
    public function update($id) {
        try {
            // Verifica se o usuário existe
            $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingUser) {
                $this->sendError('Usuário não encontrado', 404);
                return;
            }
            
            // Pega os dados enviados na requisição
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validação básica
            if (empty($input['name']) || empty($input['email'])) {
                $this->sendError('Nome e email são obrigatórios');
                return;
            }
            
            // Valida formato do email
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                $this->sendError('Email inválido');
                return;
            }
            
            // Verifica se o email já existe (exceto para o próprio usuário)
            $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$input['email'], $id]);
            if ($stmt->fetch()) {
                $this->sendError('Este email já está em uso por outro usuário');
                return;
            }
            
            // Atualiza o usuário
            $stmt = $this->db->prepare('
                UPDATE users 
                SET name = ?, email = ? 
                WHERE id = ?
            ');
            
            $stmt->execute([
                trim($input['name']),
                trim(strtolower($input['email'])),
                $id
            ]);
            
            // Busca o usuário atualizado
            $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->sendResponse([
                'success' => true,
                'data' => $updatedUser,
                'message' => 'Usuário atualizado com sucesso'
            ]);
            
        } catch (PDOException $e) {
            $this->sendError('Erro ao atualizar usuário: ' . $e->getMessage());
        }
    }
    
    // Deleta um usuário
    public function delete($id) {
        try {
            // Verifica se o usuário existe
            $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $this->sendError('Usuário não encontrado', 404);
                return;
            }
            
            // Verifica se o usuário tem projetos ou tarefas associadas
            $stmt = $this->db->prepare('
                SELECT 
                    (SELECT COUNT(*) FROM projects WHERE created_by = ?) as projects,
                    (SELECT COUNT(*) FROM tasks WHERE assigned_to = ? OR created_by = ?) as tasks
            ');
            $stmt->execute([$id, $id, $id]);
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($counts['projects'] > 0 || $counts['tasks'] > 0) {
                $this->sendError(
                    'Não é possível deletar este usuário pois ele possui projetos ou tarefas associadas. ' .
                    'Projetos: ' . $counts['projects'] . ', Tarefas: ' . $counts['tasks']
                );
                return;
            }
            
            // Deleta o usuário
            $stmt = $this->db->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$id]);
            
            $this->sendResponse([
                'success' => true,
                'message' => 'Usuário deletado com sucesso'
            ]);
            
        } catch (PDOException $e) {
            $this->sendError('Erro ao deletar usuário: ' . $e->getMessage());
        }
    }
    
    // Função auxiliar para enviar resposta JSON
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // Função auxiliar para enviar erro
    private function sendError($message, $statusCode = 400) {
        $this->sendResponse(['success' => false, 'error' => $message], $statusCode);
    }
}
?>