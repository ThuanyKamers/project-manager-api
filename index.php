<?php
// index.php - Ponto de entrada da nossa API

// Configurações básicas
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Se for uma requisição OPTIONS (preflight), retorna apenas os headers
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Inclui os arquivos necessários
require_once 'config/database.php';
require_once 'controllers/ProjectController.php';
require_once 'controllers/TaskController.php';
require_once 'controllers/UserController.php';

// Função para enviar resposta JSON
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Função para tratar erros
function sendError($message, $statusCode = 400) {
    sendResponse(['error' => $message], $statusCode);
}

// Pega a URL e o método da requisição
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove parâmetros da query string
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove o diretório base se necessário
$path = str_replace('/projeto', '', $path);

// Divide o caminho em segmentos
$segments = explode('/', trim($path, '/'));

// Roteamento simples
try {
    // Se não há segmentos, mostra informações da API
    if (empty($segments[0])) {
        sendResponse([
            'message' => 'API de Gerenciamento de Projetos e Tarefas',
            'version' => '1.0',
            'endpoints' => [
                'GET /users' => 'Listar usuários',
                'POST /users' => 'Criar usuário',
                'GET /projects' => 'Listar projetos',
                'POST /projects' => 'Criar projeto',
                'GET /projects/{id}' => 'Obter projeto específico',
                'PUT /projects/{id}' => 'Atualizar projeto',
                'DELETE /projects/{id}' => 'Deletar projeto',
                'GET /tasks' => 'Listar tarefas',
                'POST /tasks' => 'Criar tarefa',
                'GET /tasks/{id}' => 'Obter tarefa específica',
                'PUT /tasks/{id}' => 'Atualizar tarefa',
                'DELETE /tasks/{id}' => 'Deletar tarefa'
            ]
        ]);
    }

    $resource = $segments[0];
    $id = isset($segments[1]) ? $segments[1] : null;

    // Roteamento para diferentes recursos
    switch ($resource) {
        case 'users':
            $controller = new UserController();
            break;
        case 'projects':
            $controller = new ProjectController();
            break;
        case 'tasks':
            $controller = new TaskController();
            break;
        default:
            sendError('Endpoint não encontrado', 404);
    }

    // Chama o método apropriado baseado no método HTTP
    switch ($requestMethod) {
        case 'GET':
            if ($id) {
                $result = $controller->getById($id);
                if ($result === null) {
                    sendError('Recurso não encontrado', 404);
                }
                sendResponse(['success' => true, 'data' => $result]);
            } else {
                $result = $controller->getAll();
                sendResponse(['success' => true, 'data' => $result, 'total' => count($result)]);
            }
            break;
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                sendError('Dados JSON inválidos', 400);
            }
            $result = $controller->create($input);
            sendResponse(['success' => true, 'data' => $result, 'message' => 'Criado com sucesso'], 201);
            break;
        case 'PUT':
            if (!$id) {
                sendError('ID é obrigatório para atualização', 400);
            }
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                sendError('Dados JSON inválidos', 400);
            }
            $result = $controller->update($id, $input);
            if ($result === null) {
                sendError('Recurso não encontrado', 404);
            }
            sendResponse(['success' => true, 'data' => $result, 'message' => 'Atualizado com sucesso']);
            break;
        case 'DELETE':
            if (!$id) {
                sendError('ID é obrigatório para exclusão', 400);
            }
            $result = $controller->delete($id);
            if (!$result) {
                sendError('Recurso não encontrado', 404);
            }
            sendResponse(['success' => true, 'message' => 'Deletado com sucesso']);
            break;
        default:
            sendError('Método não permitido', 405);
    }

} catch (Exception $e) {
    sendError('Erro interno do servidor: ' . $e->getMessage(), 500);
}
?>