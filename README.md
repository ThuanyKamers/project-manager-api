# API de Gerenciamento de Projetos e Tarefas

## 📋 O que é esta API?

Esta é uma **API REST** (Application Programming Interface) criada em PHP para gerenciar projetos e tarefas. Pense nela como um "garçom digital" que:

- **Recebe pedidos** (requisições HTTP)
- **Processa as informações** (consulta o banco de dados)
- **Retorna respostas** (dados em formato JSON)

### 🎯 Funcionalidades Principais

✅ **Gerenciar Projetos** - Criar projetos com prazos e descrições
✅ **Status de Progresso** - Acompanhar o andamento de projetos e tarefas

## 🚀 Como Executar

### Pré-requisitos
- PHP 7.4 ou superior
- Extensão PDO SQLite habilitada

### Passos para Executar

1. **Abra o terminal** na pasta do projeto
2. **Execute o servidor PHP**:
   ```bash
   php -S localhost:8000
   ```
3. **Acesse no navegador**: http://localhost:8000

## 📚 Como Usar a API

### 🔍 Conceitos Básicos

**Métodos HTTP:**
- `GET` = Buscar/Listar dados
- `POST` = Criar novos dados
- `PUT` = Atualizar dados existentes
- `DELETE` = Deletar dados

**Formato das Respostas:**
Todas as respostas são em JSON:
```json
{
  "success": true,
  "data": {...},
  "message": "Operação realizada com sucesso"
}
```
### 📁 Endpoints de Projetos

#### Listar todos os projetos
```http
GET /projects
```

#### Buscar projeto específico
```http
GET /projects/1
```

#### Criar novo projeto
```http
POST /projects
Content-Type: application/json

{
  "title": "Novo Website",
  "description": "Desenvolvimento do site da empresa",
  "deadline": "2024-06-30",
  "created_by": 1
}
```

#### Atualizar projeto
```http
PUT /projects/1
Content-Type: application/json

{
  "title": "Website Atualizado",
  "status": "em_andamento",
  "deadline": "2024-07-15"
}
```

#### Deletar projeto
```http
DELETE /projects/1
```

### ✅ Endpoints de Tarefas

#### Listar todas as tarefas
```http
GET /tasks
```

#### Filtrar tarefas
```http
GET /tasks?project_id=1&status=pendente&priority=alta
```

#### Buscar tarefa específica
```http
GET /tasks/1
```

#### Criar nova tarefa
```http
POST /tasks
Content-Type: application/json

{
  "title": "Criar página inicial",
  "description": "Desenvolver o layout da homepage",
  "project_id": 1,
  "assigned_to": 2,
  "created_by": 1,
  "priority": "alta",
  "deadline": "2024-03-15"
}
```

#### Atualizar tarefa
```http
PUT /tasks/1
Content-Type: application/json

{
  "title": "Página inicial finalizada",
  "status": "concluida",
  "assigned_to": 2
}
```

#### Deletar tarefa
```http
DELETE /tasks/1
```

## 📊 Status e Prioridades

### Status de Projetos
- `ativo` - Projeto em andamento
- `pausado` - Projeto temporariamente parado
- `concluido` - Projeto finalizado
- `cancelado` - Projeto cancelado

## 🗄️ Estrutura do Banco de Dados

A API usa SQLite (um banco de dados em arquivo) com 3 tabelas principais:

### Tabela `users`
- `id` - Identificador único
- `name` - Nome do usuário
- `email` - Email (único)
- `created_at` - Data de criação

### Tabela `projects`
- `id` - Identificador único
- `title` - Título do projeto
- `description` - Descrição detalhada
- `deadline` - Data limite
- `status` - Status atual
- `created_by` - ID do usuário criador
- `created_at` - Data de criação

### Tabela `tasks`
- `id` - Identificador único
- `title` - Título da tarefa
- `description` - Descrição detalhada
- `status` - Status atual
- `priority` - Prioridade
- `deadline` - Data limite
- `project_id` - ID do projeto (obrigatório)
- `assigned_to` - ID do usuário responsável
- `created_by` - ID do usuário criador
- `created_at` - Data de criação
- `completed_at` - Data de conclusão

## 🛠️ Testando a API

### Usando o Navegador
Para requisições GET, você pode usar diretamente o navegador:
- http://localhost:8000/users
- http://localhost:8000/projects
- http://localhost:8000/tasks

### Usando Ferramentas
- **Postman** - Interface gráfica para testar APIs
- **Insomnia** - Alternativa ao Postman
- **cURL** - Linha de comando
- **Thunder Client** - Extensão do VS Code

### Exemplo com cURL
```bash
# Listar usuários
curl http://localhost:8000/users

# Criar usuário
curl -X POST http://localhost:8000/users \
  -H "Content-Type: application/json" \
  -d '{"name":"João","email":"joao@email.com"}'
```

## 🎯 Próximos Passos

Agora que você tem uma API funcionando, pode:

1. **Criar uma interface web** (HTML/CSS/JavaScript)
2. **Desenvolver um app mobile** que consuma esta API
3. **Adicionar autenticação** (login/senha)
4. **Implementar notificações** por email
5. **Adicionar relatórios** de produtividade

## 🆘 Resolução de Problemas

### Erro "Endpoint não encontrado"
- Verifique se a URL está correta
- Certifique-se de que o servidor está rodando

### Erro "Usuário não encontrado"
- Verifique se o ID do usuário existe
- Use GET /users para ver todos os usuários

### Erro "Email já está em uso"
- Cada usuário deve ter um email único
- Verifique se não há duplicatas

### Banco de dados não criado
- Verifique se a pasta tem permissões de escrita
- O arquivo será criado automaticamente em `database/projeto_api.db`

---

**Parabéns! 🎉** Você agora tem uma API completa para gerenciar projetos e tarefas!

Esta API pode ser a base para sistemas mais complexos como:
- Sistemas de gestão empresarial
- Aplicativos de produtividade
- Ferramentas de colaboração em equipe
- Dashboards de acompanhamento de projetos
