# Project and Task Management API

## 📋 What is this API?

This is a **REST API** (Application Programming Interface) created in PHP to manage projects and tasks. Think of it as a "digital waiter" that:

- **Receives orders** (HTTP requests)
- **Processes the information** (queries the database)
- **Returns responses** (data in JSON format)

### 🎯 Key Features

✅ **Manage Projects** - Create projects with deadlines and descriptions
✅ **Progress Status** - Track the progress of projects and tasks

## 🚀 How to Run

### Prerequisites
- PHP 7.4 or higher
- PDO SQLite extension enabled

### Steps to Run

1. **Open the terminal** in the project folder
2. **Execute the PHP server**:
   ```bash
   php -S localhost:8000
   ```
3. **Access in the browser**: http://localhost:8000

## 📚 How to Use the API

### 🔍 Basic Concepts

**HTTP Methods:**
- `GET` = Retrieve/List data
- `POST` = Create new data
- `PUT` = Update existing data
- `DELETE` = Delete data

**Response Format:**
All responses are in JSON:
```json
{
  "success": true,
  "data": {...},
  "message": "Operation performed successfully"
}
```

📁 Project Endpoints
List all projects
```HTTP
GET /projects
```

#### Retrieve specific project
```HTTP
GET /projects/1
```

#### Create new project
```HTTP

POST /projects
Content-Type: application/json

{
  "title": "New Website",
  "description": "Company website development",
  "deadline": "2024-06-30",
  "created_by": 1
}
```

#### Update project
```HTTP

PUT /projects/1
Content-Type: application/json

{
  "title": "Updated Website",
  "status": "in_progress",
  "deadline": "2024-07-15"
}
```

#### Delete project
```HTTP
DELETE /projects/1
```

### ✅ Task Endpoints
#### List all tasks
```HTTP
GET /tasks
```

#### Filter tasks
```HTTP
GET /tasks?project_id=1&status=pending&priority=high
```

#### Retrieve specific task
```HTTP
GET /tasks/1
```

#### Create new task
```HTTP
POST /tasks
Content-Type: application/json

{
  "title": "Create homepage",
  "description": "Develop the homepage layout",
  "project_id": 1,
  "assigned_to": 2,
  "created_by": 1,
  "priority": "high",
  "deadline": "2024-03-15"
}
```

#### Update task
```HTTP
PUT /tasks/1
Content-Type: application/json

{
  "title": "Homepage finalized",
  "status": "completed",
  "assigned_to": 2
}
```

#### Delete task
```HTTP
DELETE /tasks/1
```

## 📊 Status and Priorities

### Project Statuses
- `active` - Project in progress

- `paused` - Project temporarily paused

- `completed` - Project finalized

- `canceled` - Project cancelled

## 🗄️ Database Structure

The API uses SQLite (a file-based database) with 3 main tables:

### `users` Table
- `id` - Unique identifier

- `name` - User name

- `email` - Email (unique)

- `created_at` - Creation date

### `projects` Table
- `id` - Unique identifier

- `title` - Project title

- `description` - Detailed description

- `deadline` - Deadline

- `status` - Current status

- `created_by` - Creator user ID

- `created_at` - Creation date

### `tasks` Table
- `id` - Unique identifier

- `title` - Task title

- `description` - Detailed description

- `status` - Current status

- `priority` - Priority

- `deadline` - Deadline

- `project_id` - Project ID (required)

- `assigned_to` - Responsible user ID

- `created_by` - Creator user ID

- `created_at` - Creation date

- `completed_at` - Completion date

## 🛠️ Testing the API

### Using the Browser
For GET requests, you can use the browser directly:
- http://localhost:8000/users
- http://localhost:8000/projects
- http://localhost:8000/tasks

### Using Tools
- **Postman** - Graphical interface for testing APIs

- **Insomnia** - Alternative to Postman

- **cURL** - Command line

- **Thunder Client** - VS Code Extension

### Example with cURL
```bash

# List users
curl http://localhost:8000/users

# Create user
curl -X POST http://localhost:8000/users \
  -H "Content-Type: application/json" \
  -d '{"name":"João","email":"joao@email.com"}'
```

## 🎯 Next Steps

Now that you have a functional API, you can:

1. **Create a web interface** (HTML/CSS/JavaScript)

2. **Develop a mobile app** that consumes this API

3. **Add authentication** (login/password)

4. **Implement notifications** by email

5. **Add reports** of productivity

## 🆘 Troubleshooting

### Error "Endpoint not found"
- Verify if the URL is correct
- Ensure the server is running

### Error "User not found"
- Verify if the user ID exists
- Use GET /users to see all users

### Error "Email already in use"
- Each user must have a unique email
- Check for duplicates

### Database not created
- Verify if the folder has write permissions
- The file will be automatically created in `database/projeto_api.db`
