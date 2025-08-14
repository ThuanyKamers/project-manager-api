<?php
// config/database.php - Configuração do banco de dados

/*
 * EXPLICAÇÃO: Banco de dados em JSON
 * 
 * Como o SQLite não está disponível, vamos usar arquivos JSON para armazenar os dados.
 * É uma solução simples e funcional para desenvolvimento.
 * 
 * Cada "tabela" será um arquivo JSON separado:
 * - users.json - para usuários
 * - projects.json - para projetos  
 * - tasks.json - para tarefas
 */

class Database {
    private $dataDir;
    private $users = [];
    private $projects = [];
    private $tasks = [];
    
    public function __construct() {
        // Define onde os arquivos JSON serão salvos
        $this->dataDir = __DIR__ . '/../database';
        
        // Cria o diretório se não existir
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0777, true);
        }
        
        $this->loadData();
        $this->createSampleData();
    }
    
    private function loadData() {
        // Carrega dados dos arquivos JSON
        $this->users = $this->loadJsonFile('users.json');
        $this->projects = $this->loadJsonFile('projects.json');
        $this->tasks = $this->loadJsonFile('tasks.json');
    }
    
    private function loadJsonFile($filename) {
        $filepath = $this->dataDir . '/' . $filename;
        if (file_exists($filepath)) {
            $content = file_get_contents($filepath);
            return json_decode($content, true) ?: [];
        }
        return [];
    }
    
    private function saveJsonFile($filename, $data) {
        $filepath = $this->dataDir . '/' . $filename;
        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    private function createSampleData() {
        // Cria dados de exemplo se não existirem
        if (empty($this->users)) {
            $this->users = [
                [
                    'id' => 1,
                    'name' => 'João Silva',
                    'email' => 'joao@email.com',
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 2,
                    'name' => 'Maria Santos',
                    'email' => 'maria@email.com',
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 3,
                    'name' => 'Pedro Oliveira',
                    'email' => 'pedro@email.com',
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ];
            $this->saveJsonFile('users.json', $this->users);
        }
        
        if (empty($this->projects)) {
            $this->projects = [
                [
                    'id' => 1,
                    'title' => 'Website da Empresa',
                    'description' => 'Desenvolvimento do novo site institucional',
                    'deadline' => '2024-03-15',
                    'status' => 'pending',
                    'created_by' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 2,
                    'title' => 'App Mobile',
                    'description' => 'Aplicativo para gestão de tarefas',
                    'deadline' => '2024-04-20',
                    'status' => 'in_progress',
                    'created_by' => 2,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ];
            $this->saveJsonFile('projects.json', $this->projects);
        }
        
        if (empty($this->tasks)) {
            $this->tasks = [
                [
                    'id' => 1,
                    'title' => 'Criar layout inicial',
                    'description' => 'Desenvolver o design das páginas principais',
                    'status' => 'in_progress',
                    'priority' => 'alta',
                    'deadline' => '2024-02-15',
                    'project_id' => 1,
                    'assigned_to' => 2,
                    'created_by' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 2,
                    'title' => 'Configurar banco de dados',
                    'description' => 'Estruturar as tabelas e relacionamentos',
                    'status' => 'completed',
                    'priority' => 'alta',
                    'deadline' => '2024-02-10',
                    'project_id' => 1,
                    'assigned_to' => 1,
                    'created_by' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ];
            $this->saveJsonFile('tasks.json', $this->tasks);
        }
    }
    
    // Métodos para acessar os dados
    public function getUsers() {
        return $this->users;
    }
    
    public function getProjects() {
        return $this->projects;
    }
    
    public function getTasks() {
        return $this->tasks;
    }
    
    public function addProject($project) {
        $project['id'] = $this->getNextId($this->projects);
        $project['created_at'] = date('Y-m-d H:i:s');
        $this->projects[] = $project;
        $this->saveJsonFile('projects.json', $this->projects);
        return $project;
    }
    
    public function updateProject($id, $data) {
        foreach ($this->projects as &$project) {
            if ($project['id'] == $id) {
                $project = array_merge($project, $data);
                $this->saveJsonFile('projects.json', $this->projects);
                return $project;
            }
        }
        return null;
    }
    
    public function deleteProject($id) {
        $this->projects = array_filter($this->projects, function($project) use ($id) {
            return $project['id'] != $id;
        });
        $this->projects = array_values($this->projects); // Reindexar
        $this->saveJsonFile('projects.json', $this->projects);
        return true;
    }
    
    private function getNextId($array) {
        if (empty($array)) return 1;
        $ids = array_column($array, 'id');
        return max($ids) + 1;
    }
}

// Função global para obter conexão com o banco
function getDatabase() {
    static $database = null;
    if ($database === null) {
        $database = new Database();
    }
    return $database;
}
?>