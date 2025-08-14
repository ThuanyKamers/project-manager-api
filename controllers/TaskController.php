<?php
// controllers/TaskController.php - Controlador para gerenciar tarefas

/*
 * EXPLICAÇÃO: Controller de Tarefas
 * 
 * Este é o controller mais importante da nossa API!
 * Uma tarefa é como um "item da lista de afazeres" dentro de um projeto:
 * - Cada tarefa pertence a um projeto específico
 * - Pode ser atribuída a um usuário específico
 * - Tem prioridade (alta, média, baixa)
 * - Tem status (pendente, em andamento, concluída)
 * - Pode ter prazo definido
 * - Registra quando foi criada e quando foi concluída
 */

class TaskController {
    private $db;
    
    public function __construct() {
        $this->db = getDatabase()->getConnection();
    }
    
    // Lista todas as tarefas
    public function getAll() {
        try {
            // Permite filtrar por projeto, usuário ou status via query parameters
            $projectId = isset($_GET['project_id']) ? $_GET['project_id'] : null;
            $assignedTo = isset($_GET['assigned_to']) ? $_GET['assigned_to'] : null;
            $status = isset($_GET['status']) ? $_GET['status'] : null;
            $priority = isset($_GET['priority']) ? $_GET['priority'] : null;
            
            $sql = '
                SELECT 
                    t.*,
                    p.title as project_title,
                    p.status as project_status,
                    u1.name as assigned_to_name,
                    u1.email as assigned_to_email,
                    u2.name as created_by_name,
                    u2.email as created_by_email
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN users u1 ON t.assigned_to = u1.id
                LEFT JOIN users u2 ON t.created_by = u2.id
                WHERE 1=1
            ';
            
            $params = [];
            
            if ($projectId) {
                $sql .= ' AND t.project_id = ?';
                $params[] = $projectId;
            }
            
            if ($assignedTo) {
                $sql .= ' AND t.assigned_to = ?';
                $params[] = $assignedTo;
            }
            
            if ($status) {
                $sql .= ' AND t.status = ?';
                $params[] = $status;
            }
            
            if ($priority) {
                $sql .= ' AND t.priority = ?';
                $params[] = $priority;
            }
            
            $sql .= '
                ORDER BY 
                    CASE t.priority 
                        WHEN "alta" THEN 1 
                        WHEN "media" THEN 2 
                        WHEN "baixa" THEN 3 
                    END,
                    t.deadline ASC,
                    t.created_at DESC
            ';
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Adiciona informações extras para cada tarefa
            foreach ($tasks as &$task) {
                // Verifica se a tarefa está atrasada
                if ($task['deadline']) {
                    $deadline = new DateTime($task['deadline']);
                    $today = new DateTime();
                    $task['is_overdue'] = $deadline < $today && $task['status'] !== 'concluida';
                    $task['days_until_deadline'] = $today->diff($deadline)->days * ($deadline < $today ? -1 : 1);
                } else {
                    $task['is_overdue'] = false;
                    $task['days_until_deadline'] = null;
                }
                
                // Calcula há quantos dias a tarefa foi criada
                $created = new DateTime($task['created_at']);
                $today = new DateTime();
                $task['days_since_created'] = $today->diff($created)->days;
                
                // Se foi concluída, calcula quantos dias levou
                if ($task['completed_at']) {
                    $completed = new DateTime($task['completed_at']);
                    $task['days_to_complete'] = $created->diff($completed)->days;
                }
            }
            
            $this->sendResponse([
                'success' => true,
                'data' => $tasks,
                'total' => count($tasks),
                'filters_applied' => [
                    'project_id' => $projectId,
                    'assigned_to' => $assignedTo,
                    'status' => $status,
                    'priority' => $priority
                ],
                'message' => 'Tarefas listadas com sucesso'
            ]);
            
        } catch (PDOException $e) {
            $this->sendError('Erro ao buscar tarefas: ' . $e->getMessage());
        }
    }
    
    // Busca uma tarefa específica pelo ID
    public function getById($id) {
        try {
            $stmt = $this->db->prepare('
                SELECT 
                    t.*,
                    p.title as project_title,
                    p.description as project_description,
                    p.status as project_status,
                    p.deadline as project_deadline,
                    u1.name as assigned_to_name,
                    u1.email as assigned_to_email,
                    u2.name as created_by_name,
                    u2.email as created_by_email
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN users u1 ON t.assigned_to = u1.id
                LEFT JOIN users u2 ON t.created_by = u2.id
                WHERE t.id = ?
            ');
            
            $stmt->execute([$id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                $this->sendError('Tarefa não encontrada', 404);
                return;
            }
            
            // Adiciona informações extras
            if ($task['deadline']) {
                $deadline = new DateTime($task['deadline']);
                $today = new DateTime();
                $task['is_overdue'] = $deadline < $today && $task['status'] !== 'concluida';
                $task['days_until_deadline'] = $today->diff($deadline)->days * ($deadline < $today ? -1 : 1);
            }
            
            $created = new DateTime($task['created_at']);
            $today = new DateTime();
            $task['days_since_created'] = $today->diff($created)->days;
            
            if ($task['completed_at']) {
                $completed = new DateTime($task['completed_at']);
                $task['days_to_complete'] = $created->diff($completed)->days;
            }
            
            $this->sendResponse([
                'success' => true,
                'data' => $task,
                'message' => 'Tarefa encontrada com sucesso'
            ]);
            
        } catch (PDOException $e) {
            $this->sendError('Erro ao buscar tarefa: ' . $e->getMessage());
        }
    }
    
    // Cria uma nova tarefa
    public function create() {
        try {
            // Pega os dados enviados na requisição
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validação básica
            if (empty($input['title'])) {
                $this->sendError('Título é obrigatório');
                return;
            }
            
            if (empty($input['project_id'])) {
                $this->sendError('ID do projeto é obrigatório');
                return;
            }
            
            if (empty($input['created_by'])) {
                $this->sendError('ID do criador é obrigatório');
                return;
            }
            
            // Verifica se o projeto existe
            $stmt = $this->db->prepare('SELECT id, status FROM projects WHERE id = ?');
            $stmt->execute([$input['project_id']]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$project) {
                $this->sendError('Projeto não encontrado');
                return;
            }
            
            if ($project['status'] === 'concluido' || $project['status'] === 'cancelado') {
                $this->sendError('Não é possível criar tarefas em projetos concluídos ou cancelados');
                return;
            }
            
            // Verifica se o usuário criador existe
            $stmt = $this->db->prepare('SELECT id FROM users WHERE id = ?');
            $stmt->execute([$input['created_by']]);
            if (!$stmt->fetch()) {
                $this->sendError('Usuário criador não encontrado');
                return;
            }
            
            // Verifica se o usuário atribuído existe (se fornecido)
            $assignedTo = null;
            if (!empty($input['assigned_to'])) {
                $stmt = $this->db->prepare('SELECT id FROM users WHERE id = ?');
                $stmt->execute([$input['assigned_to']]);
                if (!$stmt->fetch()) {
                    $this->sendError('Usuário atribuído não encontrado');
                    return;
                }
                $assignedTo = $input['assigned_to'];
            }
            
            // Valida a prioridade
            $validPriorities = ['baixa', 'media', 'alta'];
            $priority = isset($input['priority']) ? $input['priority'] : 'media';
            if (!in_array($priority, $validPriorities)) {
                $this->sendError('Prioridade deve ser: ' . implode(', ', $validPriorities));
                return;
            }
            
            // Valida o status
            $validStatuses = ['pendente', 'em_andamento', 'concluida'];
            $status = isset($input['status']) ? $input['status'] : 'pendente';
            if (!in_array($status, $validStatuses)) {
                $this->sendError('Status deve ser: ' . implode(', ', $validStatuses));
                return;
            }
            
            // Valida a data de prazo se fornecida
            $deadline = null;
            if (!empty($input['deadline'])) {
                $deadline = $input['deadline'];
                $date = DateTime::createFromFormat('Y-m-d', $deadline);
                if (!$date) {
                    $this->sendError('Data de prazo deve estar no formato YYYY-MM-DD');
                    return;
                }
            }
            
            // Define completed_at se o status for 'concluida'
            $completedAt = null;
            if ($status === 'concluida') {
                $completedAt = date('Y-m-d H:i:s');
            }
            
            // Insere a nova tarefa
            $stmt = $this->db->prepare('
                INSERT INTO tasks (title, description, status, priority, deadline, project_id, assigned_to, created_by, completed_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            
            $stmt->execute([
                trim($input['title']),
                isset($input['description']) ? trim($input['description']) : null,
                $status,
                $priority,
                $deadline,
                $input['project_id'],
                $assignedTo,
                $input['created_by'],
                $completedAt
            ]);
            
            $taskId = $this->db->lastInsertId();
            
            // Busca a tarefa criada para retornar
            $stmt = $this->db->prepare('
                SELECT 
                    t.*,
                    p.title as project_title,
                    u1.name as assigned_to_name,
                    u1.email as assigned_to_email,
                    u2.name as created_by_name,
                    u2.email as created_by_email
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN users u1 ON t.assigned_to = u1.id
                LEFT JOIN users u2 ON t.created_by = u2.id
                WHERE t.id = ?
            ');
            $stmt->execute([$taskId]);
            $newTask = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->sendResponse([
                'success' => true,
                'data' => $newTask,
                'message' => 'Tarefa criada com sucesso'
            ], 201);
            
        } catch (PDOException $e) {
            $this->sendError('Erro ao criar tarefa: ' . $e->getMessage());
        }
    }
    
    // Atualiza uma tarefa existente
    public function update($id) {
        try {
            // Verifica se a tarefa existe
            $stmt = $this->db->prepare('SELECT * FROM tasks WHERE id = ?');
            $stmt->execute([$id]);
            $existingTask = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingTask) {
                $this->sendError('Tarefa não encontrada', 404);
                return;
            }
            
            // Pega os dados enviados na requisição
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validação básica
            if (empty($input['title'])) {
                $this->sendError('Título é obrigatório');
                return;
            }
            
            // Verifica se o usuário atribuído existe (se fornecido)
            $assignedTo = $existingTask['assigned_to'];
            if (isset($input['assigned_to'])) {
                if (!empty($input['assigned_to'])) {
                    $stmt = $this->db->prepare('SELECT id FROM users WHERE id = ?');
                    $stmt->execute([$input['assigned_to']]);
                    if (!$stmt->fetch()) {
                        $this->sendError('Usuário atribuído não encontrado');
                        return;
                    }
                    $assignedTo = $input['assigned_to'];
                } else {
                    $assignedTo = null;
                }
            }
            
            // Valida a prioridade
            $validPriorities = ['baixa', 'media', 'alta'];
            $priority = isset($input['priority']) ? $input['priority'] : $existingTask['priority'];
            if (!in_array($priority, $validPriorities)) {
                $this->sendError('Prioridade deve ser: ' . implode(', ', $validPriorities));
                return;
            }
            
            // Valida o status
            $validStatuses = ['pendente', 'em_andamento', 'concluida'];
            $status = isset($input['status']) ? $input['status'] : $existingTask['status'];
            if (!in_array($status, $validStatuses)) {
                $this->sendError('Status deve ser: ' . implode(', ', $validStatuses));
                return;
            }
            
            // Valida a data de prazo se fornecida
            $deadline = $existingTask['deadline'];
            if (isset($input['deadline'])) {
                if (!empty($input['deadline'])) {
                    $deadline = $input['deadline'];
                    $date = DateTime::createFromFormat('Y-m-d', $deadline);
                    if (!$date) {
                        $this->sendError('Data de prazo deve estar no formato YYYY-MM-DD');
                        return;
                    }
                } else {
                    $deadline = null;
                }
            }
            
            // Gerencia completed_at baseado no status
            $completedAt = $existingTask['completed_at'];
            if ($status === 'concluida' && $existingTask['status'] !== 'concluida') {
                // Tarefa foi marcada como concluída agora
                $completedAt = date('Y-m-d H:i:s');
            } elseif ($status !== 'concluida' && $existingTask['status'] === 'concluida') {
                // Tarefa foi desmarcada como concluída
                $completedAt = null;
            }
            
            // Atualiza a tarefa
            $stmt = $this->db->prepare('
                UPDATE tasks 
                SET title = ?, description = ?, status = ?, priority = ?, deadline = ?, assigned_to = ?, completed_at = ?
                WHERE id = ?
            ');
            
            $stmt->execute([
                trim($input['title']),
                isset($input['description']) ? trim($input['description']) : $existingTask['description'],
                $status,
                $priority,
                $deadline,
                $assignedTo,
                $completedAt,
                $id
            ]);
            
            // Busca a tarefa atualizada
            $stmt = $this->db->prepare('
                SELECT 
                    t.*,
                    p.title as project_title,
                    u1.name as assigned_to_name,
                    u1.email as assigned_to_email,
                    u2.name as created_by_name,
                    u2.email as created_by_email
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN users u1 ON t.assigned_to = u1.id
                LEFT JOIN users u2 ON t.created_by = u2.id
                WHERE t.id = ?
            ');
            $stmt->execute([$id]);
            $updatedTask = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->sendResponse([
                'success' => true,
                'data' => $updatedTask,
                'message' => 'Tarefa atualizada com sucesso'
            ]);
            
        } catch (PDOException $e) {
            $this->sendError('Erro ao atualizar tarefa: ' . $e->getMessage());
        }
    }
    
    // Deleta uma tarefa
    public function delete($id) {
        try {
            // Verifica se a tarefa existe
            $stmt = $this->db->prepare('
                SELECT t.*, p.title as project_title 
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id 
                WHERE t.id = ?
            ');
            $stmt->execute([$id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                $this->sendError('Tarefa não encontrada', 404);
                return;
            }
            
            // Deleta a tarefa
            $stmt = $this->db->prepare('DELETE FROM tasks WHERE id = ?');
            $stmt->execute([$id]);
            
            $this->sendResponse([
                'success' => true,
                'message' => "Tarefa '{$task['title']}' do projeto '{$task['project_title']}' foi deletada com sucesso"
            ]);
            
        } catch (PDOException $e) {
            $this->sendError('Erro ao deletar tarefa: ' . $e->getMessage());
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