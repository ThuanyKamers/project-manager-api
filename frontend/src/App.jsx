import { useState, useEffect } from 'react'
import axios from 'axios'
import './App.css'

const API_URL = 'http://localhost:8000'

function App() {
  const [projects, setProjects] = useState([])
  const [loading, setLoading] = useState(true)
  const [showForm, setShowForm] = useState(false)
  const [theme, setTheme] = useState('light')
  const [newProject, setNewProject] = useState({
    title: '',
    description: '',
    deadline: '',
    status: 'pending'
  })

  // Buscar projetos da API
  useEffect(() => {
    fetchProjects()
  }, [])

  // Gerenciar tema
  useEffect(() => {
    const savedTheme = localStorage.getItem('theme') || 'light'
    setTheme(savedTheme)
    document.body.className = `theme-${savedTheme}`
  }, [])

  useEffect(() => {
    document.body.className = `theme-${theme}`
    localStorage.setItem('theme', theme)
  }, [theme])

  const handleThemeChange = (newTheme) => {
    setTheme(newTheme)
  }

  const fetchProjects = async () => {
    try {
      const response = await axios.get(`${API_URL}/projects`)
      setProjects(response.data.data || [])
      setLoading(false)
    } catch (error) {
      console.error('Erro ao buscar projetos:', error)
      setLoading(false)
    }
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    try {
      await axios.post(`${API_URL}/projects`, newProject)
      setNewProject({ title: '', description: '', deadline: '', status: 'pending' })
      setShowForm(false)
      fetchProjects() // Recarregar lista
    } catch (error) {
      console.error('Erro ao criar projeto:', error)
    }
  }

  const deleteProject = async (id) => {
    if (window.confirm('Tem certeza que deseja excluir este projeto?')) {
      try {
        await axios.delete(`${API_URL}/projects/${id}`)
        fetchProjects() // Recarregar lista
      } catch (error) {
        console.error('Erro ao excluir projeto:', error)
      }
    }
  }

  const updateProjectStatus = async (projectId, newStatus) => {
    try {
      const project = projects.find(p => p.id === projectId)
      if (!project) return

      const updatedProject = { ...project, status: newStatus }
      await axios.put(`${API_URL}/projects/${projectId}`, updatedProject)
      fetchProjects() // Recarregar lista
    } catch (error) {
      console.error('Erro ao atualizar status do projeto:', error)
    }
  }

  const handleDragStart = (e, project) => {
    e.dataTransfer.setData('text/plain', JSON.stringify(project))
    e.dataTransfer.effectAllowed = 'move'
    
    // Adiciona classe de dragging ao elemento
    setTimeout(() => {
      e.target.classList.add('dragging')
    }, 0)
  }

  const handleDragEnd = (e) => {
    // Remove classe de dragging
    e.target.classList.remove('dragging')
    
    // Remove classes de drag-over de todas as colunas
    document.querySelectorAll('.kanban-column').forEach(col => {
      col.classList.remove('drag-over')
    })
    document.querySelectorAll('.column-content').forEach(content => {
      content.classList.remove('drag-over')
    })
  }

  const handleDragOver = (e) => {
    e.preventDefault()
    e.dataTransfer.dropEffect = 'move'
  }

  const handleDragEnter = (e) => {
    e.preventDefault()
    const column = e.currentTarget
    const content = column.querySelector('.column-content')
    
    column.classList.add('drag-over')
    if (content) {
      content.classList.add('drag-over')
    }
  }

  const handleDragLeave = (e) => {
    // Só remove se realmente saiu da coluna (não de um filho)
    if (!e.currentTarget.contains(e.relatedTarget)) {
      const column = e.currentTarget
      const content = column.querySelector('.column-content')
      
      column.classList.remove('drag-over')
      if (content) {
        content.classList.remove('drag-over')
      }
    }
  }

  const handleDrop = (e, targetStatus) => {
    e.preventDefault()
    
    // Remove classes de drag-over
    const column = e.currentTarget
    const content = column.querySelector('.column-content')
    column.classList.remove('drag-over')
    if (content) {
      content.classList.remove('drag-over')
    }
    
    try {
      const projectData = JSON.parse(e.dataTransfer.getData('text/plain'))
      if (projectData.status !== targetStatus) {
        updateProjectStatus(projectData.id, targetStatus)
      }
    } catch (error) {
      console.error('Erro ao processar drop:', error)
    }
  }

  const getStatusColor = (status) => {
    switch (status) {
      case 'completed': return '#4CAF50'
      case 'in_progress': return '#FF9800'
      case 'pending': return '#2196F3'
      default: return '#757575'
    }
  }

  const getStatusText = (status) => {
    switch (status) {
      case 'completed': return 'Concluído'
      case 'in_progress': return 'Em Progresso'
      case 'pending': return 'Pendente'
      default: return status
    }
  }

  if (loading) {
    return (
      <div className="app">
        <div className="loading">Carregando projetos...</div>
      </div>
    )
  }

  return (
    <div className="app">
      <header className="header">
        <h1>🚀 Gerenciador de Projetos</h1>
        <div className="header-controls">
          <div className="theme-selector">
            <label>🎨 Tema:</label>
            <select 
              value={theme} 
              onChange={(e) => handleThemeChange(e.target.value)}
              className="theme-select"
            >
              <option value="light">Claro</option>
              <option value="dark">Escuro</option>
              <option value="corporate">Corporativo</option>
              <option value="lofi">Lo-fi</option>
              <option value="synthwave">SynthWave</option>
              <option value="cyberpunk">Cyberpunk</option>
              <option value="forest">Floresta</option>
              <option value="winter">Inverno</option>
              <option value="sunset">Pôr do Sol</option>
              <option value="sketch">Desenho</option>
              <option value="retro">Retrô</option>
              <option value="lemonade">Limonada</option>
              <option value="luxury">Luxo</option>
              <option value="black">Preto Total</option>
            </select>
          </div>
          <button 
            className="btn-primary"
            onClick={() => setShowForm(!showForm)}
          >
            {showForm ? 'Cancelar' : '+ Novo Projeto'}
          </button>
        </div>
      </header>

      {showForm && (
        <div className="form-container">
          <h2>Criar Novo Projeto</h2>
          <form onSubmit={handleSubmit}>
            <div className="form-group">
              <label>Título:</label>
              <input
                type="text"
                value={newProject.title}
                onChange={(e) => setNewProject({...newProject, title: e.target.value})}
                required
              />
            </div>
            <div className="form-group">
              <label>Descrição:</label>
              <textarea
                value={newProject.description}
                onChange={(e) => setNewProject({...newProject, description: e.target.value})}
                rows="3"
              />
            </div>
            <div className="form-group">
              <label>Prazo:</label>
              <input
                type="date"
                value={newProject.deadline}
                onChange={(e) => setNewProject({...newProject, deadline: e.target.value})}
              />
            </div>
            <div className="form-group">
              <label>Status:</label>
              <select
                value={newProject.status}
                onChange={(e) => setNewProject({...newProject, status: e.target.value})}
              >
                <option value="pending">Pendente</option>
                <option value="in_progress">Em Progresso</option>
                <option value="completed">Concluído</option>
              </select>
            </div>
            <button type="submit" className="btn-primary">Criar Projeto</button>
          </form>
        </div>
      )}

      <div className="projects-container">
        <h2>Meus Projetos ({projects.length})</h2>
        
        {projects.length === 0 ? (
          <div className="empty-state">
            <p>📋 Nenhum projeto encontrado</p>
            <p>Clique em "Novo Projeto" para começar!</p>
          </div>
        ) : (
          <div className="kanban-board">
            {/* Coluna Pendentes */}
            <div 
              className="kanban-column"
              onDragOver={handleDragOver}
              onDragEnter={handleDragEnter}
              onDragLeave={handleDragLeave}
              onDrop={(e) => handleDrop(e, 'pending')}
            >
              <div className="column-header pending">
                <h3>📋 Pendentes</h3>
                <span className="count">{projects.filter(p => p.status === 'pending').length}</span>
              </div>
              <div className="column-content">
                {projects.filter(project => project.status === 'pending').map(project => (
                  <div 
                    key={project.id} 
                    className="project-card"
                    draggable="true"
                    onDragStart={(e) => handleDragStart(e, project)}
                    onDragEnd={handleDragEnd}
                  >
                    <div className="project-header">
                      <h3>{project.title}</h3>
                    </div>
                    
                    <p className="project-description">{project.description}</p>
                    
                    {project.deadline && (
                      <div className="project-deadline">
                        📅 Prazo: {new Date(project.deadline).toLocaleDateString('pt-BR')}
                      </div>
                    )}
                    
                    <div className="project-actions">
                      <button 
                        className="btn-danger"
                        onClick={() => deleteProject(project.id)}
                      >
                        🗑️ Excluir
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Coluna Em Progresso */}
            <div 
              className="kanban-column"
              onDragOver={handleDragOver}
              onDragEnter={handleDragEnter}
              onDragLeave={handleDragLeave}
              onDrop={(e) => handleDrop(e, 'in_progress')}
            >
              <div className="column-header in-progress">
                <h3>⚡ Em Progresso</h3>
                <span className="count">{projects.filter(p => p.status === 'in_progress').length}</span>
              </div>
              <div className="column-content">
                {projects.filter(project => project.status === 'in_progress').map(project => (
                  <div 
                    key={project.id} 
                    className="project-card"
                    draggable="true"
                    onDragStart={(e) => handleDragStart(e, project)}
                    onDragEnd={handleDragEnd}
                  >
                    <div className="project-header">
                      <h3>{project.title}</h3>
                    </div>
                    
                    <p className="project-description">{project.description}</p>
                    
                    {project.deadline && (
                      <div className="project-deadline">
                        📅 Prazo: {new Date(project.deadline).toLocaleDateString('pt-BR')}
                      </div>
                    )}
                    
                    <div className="project-actions">
                      <button 
                        className="btn-danger"
                        onClick={() => deleteProject(project.id)}
                      >
                        🗑️ Excluir
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Coluna Finalizados */}
            <div 
              className="kanban-column"
              onDragOver={handleDragOver}
              onDragEnter={handleDragEnter}
              onDragLeave={handleDragLeave}
              onDrop={(e) => handleDrop(e, 'completed')}
            >
              <div className="column-header completed">
                <h3>✅ Finalizados</h3>
                <span className="count">{projects.filter(p => p.status === 'completed').length}</span>
              </div>
              <div className="column-content">
                {projects.filter(project => project.status === 'completed').map(project => (
                  <div 
                    key={project.id} 
                    className="project-card"
                    draggable="true"
                    onDragStart={(e) => handleDragStart(e, project)}
                    onDragEnd={handleDragEnd}
                  >
                    <div className="project-header">
                      <h3>{project.title}</h3>
                    </div>
                    
                    <p className="project-description">{project.description}</p>
                    
                    {project.deadline && (
                      <div className="project-deadline">
                        📅 Prazo: {new Date(project.deadline).toLocaleDateString('pt-BR')}
                      </div>
                    )}
                    
                    <div className="project-actions">
                      <button 
                        className="btn-danger"
                        onClick={() => deleteProject(project.id)}
                      >
                        🗑️ Excluir
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}

export default App