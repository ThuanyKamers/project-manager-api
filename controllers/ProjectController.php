<?php
// controllers/ProjectController.php - Controlador para gerenciar projetos

/*
 * EXPLICAÇÃO: Controller de Projetos
 * 
 * Este controller gerencia tudo relacionado aos projetos:
 * - Um projeto é como uma "pasta" que contém várias tarefas
 * - Cada projeto tem um título, descrição, prazo e status
 * - Projetos podem ter várias tarefas associadas
 * - Podemos ver quantas tarefas cada projeto tem e qual o progresso
 */

class ProjectController {
    private $db;
    
    public function __construct() {
        $this->db = getDatabase();
    }
    
    // Lista todos os projetos
    public function getAll() {
        try {
            $projects = $this->db->getProjects();
            $users = $this->db->getUsers();
            $tasks = $this->db->getTasks();
            
            // Enriquece os dados dos projetos
            foreach ($projects as &$project) {
                // Encontra o usuário criador
                $creator = array_filter($users, function($user) use ($project) {
                    return $user['id'] == $project['created_by'];
                });
                $creator = reset($creator);
                
                $project['created_by_name'] = $creator ? $creator['name'] : 'Usuário não encontrado';
                $project['created_by_email'] = $creator ? $creator['email'] : '';
                
                // Conta tarefas do projeto
                $projectTasks = array_filter($tasks, function($task) use ($project) {
                    return $task['project_id'] == $project['id'];
                });
                
                $project['total_tasks'] = count($projectTasks);
                $project['completed_tasks'] = count(array_filter($projectTasks, function($task) {
                    return $task['status'] == 'completed';
                }));
                $project['pending_tasks'] = count(array_filter($projectTasks, function($task) {
                    return $task['status'] == 'pending';
                }));
                $project['in_progress_tasks'] = count(array_filter($projectTasks, function($task) {
                    return $task['status'] == 'in_progress';
                }));
                
                // Calcula o progresso
                $total = $project['total_tasks'];
                $completed = $project['completed_tasks'];
                
                if ($total > 0) {
                    $project['progress_percentage'] = round(($completed / $total) * 100, 1);
                } else {
                    $project['progress_percentage'] = 0;
                }
                
                // Verifica se o projeto está atrasado
                if ($project['deadline']) {
                    $deadline = new DateTime($project['deadline']);
                    $today = new DateTime();
                    $project['is_overdue'] = $deadline < $today && $project['status'] !== 'concluido';
                    $project['days_until_deadline'] = $today->diff($deadline)->days * ($deadline < $today ? -1 : 1);
                } else {
                    $project['is_overdue'] = false;
                    $project['days_until_deadline'] = null;
                }
            }
            
            return $projects;
        } catch (Exception $e) {
            throw new Exception("Erro ao buscar projetos: " . $e->getMessage());
        }
    }
    
    // Busca um projeto por ID
    public function getById($id) {
        try {
            $projects = $this->db->getProjects();
            $users = $this->db->getUsers();
            
            $project = array_filter($projects, function($p) use ($id) {
                return $p['id'] == $id;
            });
            $project = reset($project);
            
            if (!$project) {
                return null;
            }
            
            // Encontra o usuário criador
            $creator = array_filter($users, function($user) use ($project) {
                return $user['id'] == $project['created_by'];
            });
            $creator = reset($creator);
            
            $project['created_by_name'] = $creator ? $creator['name'] : 'Usuário não encontrado';
            $project['created_by_email'] = $creator ? $creator['email'] : '';
            
            return $project;
        } catch (Exception $e) {
            throw new Exception("Erro ao buscar projeto: " . $e->getMessage());
        }
    }
    
    // Cria um novo projeto
    public function create($data) {
        try {
            // Validação básica
            if (empty($data['title']) || empty($data['description'])) {
                throw new Exception('Título e descrição são obrigatórios');
            }
            
            $projectData = [
                'title' => $data['title'],
                'description' => $data['description'],
                'deadline' => !empty($data['deadline']) ? $data['deadline'] : null,
                'status' => $data['status'] ?? 'active',
                'created_by' => $data['created_by'] ?? 1,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $projectId = $this->db->addProject($projectData);
            
            return $this->getById($projectId);
        } catch (Exception $e) {
            throw new Exception("Erro ao criar projeto: " . $e->getMessage());
        }
    }
    
    // Atualiza um projeto existente
    public function update($id, $data) {
        try {
            // Validação básica
            if (empty($data['title']) || empty($data['description'])) {
                throw new Exception('Título e descrição são obrigatórios');
            }
            
            $projectData = [
                'title' => $data['title'],
                'description' => $data['description'],
                'deadline' => !empty($data['deadline']) ? $data['deadline'] : null,
                'status' => $data['status'] ?? 'active'
            ];
            
            $this->db->updateProject($id, $projectData);
            
            return $this->getById($id);
        } catch (Exception $e) {
            throw new Exception("Erro ao atualizar projeto: " . $e->getMessage());
        }
    }
    
    // Deleta um projeto
    public function delete($id) {
        try {
            $result = $this->db->deleteProject($id);
            
            if (!$result) {
                throw new Exception('Projeto não encontrado');
            }
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Erro ao deletar projeto: " . $e->getMessage());
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