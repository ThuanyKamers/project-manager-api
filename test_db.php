<?php
// test_db.php - Teste do banco de dados

try {
    require_once 'config/database.php';
    
    echo "Testando conexão com o banco de dados...\n";
    
    $db = getDatabase();
    echo "✅ Conexão estabelecida com sucesso!\n";
    
    $connection = $db->getConnection();
    
    // Testa se as tabelas existem
    $tables = ['users', 'projects', 'tasks'];
    foreach ($tables as $table) {
        $stmt = $connection->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "✅ Tabela '$table': $count registros\n";
    }
    
    // Testa buscar projetos
    echo "\nTestando busca de projetos...\n";
    $stmt = $connection->query('SELECT * FROM projects');
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Encontrados " . count($projects) . " projetos\n";
    
    foreach ($projects as $project) {
        echo "- ID: {$project['id']}, Título: {$project['title']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>