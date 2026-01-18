/**
 * Client API CLICOM - Wrapper pour gérer tous les appels fetch
 *
 * Ce module centralise tous les appels API avec :
 * - Gestion automatique des credentials (cookies)
 * - Gestion automatique du CSRF token
 * - Gestion des erreurs 401 (non authentifié) et 500 (erreur serveur)
 * - Parsing automatique du JSON
 */

class ApiClient {
  constructor() {
    this.csrfToken = null;
    this.isAuthenticated = false;
  }

  /**
   * Initialise le client API (récupère le CSRF token)
   */
  async init() {
    try {
      const response = await this.get(CONFIG.API_ENDPOINTS.AUTH);
      this.csrfToken = response.csrf_token;
      this.isAuthenticated = response.authenticated || false;
      return response;
    } catch (error) {
      console.error('API initialization failed:', error);
      throw error;
    }
  }

  /**
   * Effectue une requête GET
   * @param {string} endpoint - Endpoint de l'API (ex: '/auth.php')
   * @returns {Promise<Object>} - Réponse JSON
   */
  async get(endpoint) {
    return this.request(endpoint, {
      method: 'GET',
    });
  }

  /**
   * Effectue une requête POST
   * @param {string} endpoint - Endpoint de l'API
   * @param {Object} data - Données à envoyer
   * @returns {Promise<Object>} - Réponse JSON
   */
  async post(endpoint, data = {}) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  /**
   * Effectue une requête PUT
   * @param {string} endpoint - Endpoint de l'API
   * @param {Object} data - Données à envoyer
   * @returns {Promise<Object>} - Réponse JSON
   */
  async put(endpoint, data = {}) {
    return this.request(endpoint, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  /**
   * Effectue une requête DELETE
   * @param {string} endpoint - Endpoint de l'API
   * @param {Object} data - Données à envoyer
   * @returns {Promise<Object>} - Réponse JSON
   */
  async delete(endpoint, data = {}) {
    return this.request(endpoint, {
      method: 'DELETE',
      body: JSON.stringify(data),
    });
  }

  /**
   * Requête générique avec gestion des erreurs
   * @param {string} endpoint - Endpoint de l'API
   * @param {Object} options - Options fetch
   * @returns {Promise<Object>} - Réponse JSON
   */
  async request(endpoint, options = {}) {
    const url = `${CONFIG.API_BASE_URL}${endpoint}`;

    // Configuration par défaut
    const defaultOptions = {
      credentials: 'include', // Inclure les cookies (session)
      headers: {
        'Content-Type': 'application/json',
      },
    };

    // Ajouter le CSRF token pour les requêtes POST, PUT, DELETE
    if (['POST', 'PUT', 'DELETE'].includes(options.method) && this.csrfToken) {
      defaultOptions.headers[CONFIG.SECURITY.CSRF_HEADER] = this.csrfToken;
    }

    // Fusionner les options
    const finalOptions = {
      ...defaultOptions,
      ...options,
      headers: {
        ...defaultOptions.headers,
        ...options.headers,
      },
    };

    try {
      const response = await fetch(url, finalOptions);

      // Gérer les erreurs HTTP
      if (!response.ok) {
        await this.handleHttpError(response);
      }

      // Parser la réponse JSON
      const contentType = response.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
        return await response.json();
      }

      // Si pas de contenu JSON, retourner un objet vide
      return {};
    } catch (error) {
      // Gérer les erreurs réseau
      this.handleNetworkError(error);
      throw error;
    }
  }

  /**
   * Gère les erreurs HTTP (401, 403, 500, etc.)
   * @param {Response} response - Réponse fetch
   */
  async handleHttpError(response) {
    const status = response.status;
    let errorData = {};

    try {
      errorData = await response.json();
    } catch (e) {
      // Si pas de JSON, créer un message d'erreur par défaut
      errorData = { error: `HTTP ${status}` };
    }

    switch (status) {
      case 401:
        // Non authentifié - rediriger vers la page de login
        this.isAuthenticated = false;
        if (window.location.pathname !== '/app/login.html') {
          this.showError(CONFIG.MESSAGES.AUTH_REQUIRED);
          setTimeout(() => {
            window.location.href = '/app/login.html';
          }, 1500);
        }
        break;

      case 403:
        // Interdit (CSRF invalide ou accès refusé)
        this.showError(errorData.error || 'Accès refusé');
        break;

      case 422:
        // Données invalides
        this.showError(errorData.error || 'Données invalides');
        break;

      case 429:
        // Rate limit dépassé
        this.showError(errorData.error || 'Trop de requêtes. Veuillez patienter.');
        break;

      case 500:
      case 502:
      case 503:
        // Erreur serveur
        this.showError('Erreur serveur. Veuillez réessayer plus tard.');
        break;

      default:
        this.showError(errorData.error || CONFIG.MESSAGES.GENERIC_ERROR);
    }

    throw new Error(errorData.error || `HTTP ${status}`);
  }

  /**
   * Gère les erreurs réseau (pas de connexion)
   * @param {Error} error - Erreur réseau
   */
  handleNetworkError(error) {
    console.error('Network error:', error);
    this.showError(CONFIG.MESSAGES.NETWORK_ERROR);
  }

  /**
   * Affiche un message d'erreur à l'utilisateur
   * @param {string} message - Message d'erreur
   */
  showError(message) {
    // Chercher un élément #error-message dans la page
    const errorElement = document.getElementById('error-message');
    if (errorElement) {
      errorElement.textContent = message;
      errorElement.style.display = 'block';

      // Masquer après 5 secondes
      setTimeout(() => {
        errorElement.style.display = 'none';
      }, 5000);
    } else {
      // Fallback: alert
      alert(message);
    }
  }

  /**
   * Affiche un message de succès à l'utilisateur
   * @param {string} message - Message de succès
   */
  showSuccess(message) {
    const successElement = document.getElementById('success-message');
    if (successElement) {
      successElement.textContent = message;
      successElement.style.display = 'block';

      setTimeout(() => {
        successElement.style.display = 'none';
      }, 3000);
    }
  }

  /**
   * Connexion utilisateur
   * @param {string} email - Email de l'utilisateur
   * @param {string} password - Mot de passe
   * @returns {Promise<Object>} - Réponse de l'API
   */
  async login(email, password) {
    const response = await this.post(CONFIG.API_ENDPOINTS.AUTH, {
      action: 'login',
      email,
      password,
    });

    if (response.status === 'authenticated') {
      this.isAuthenticated = true;
    }

    return response;
  }

  /**
   * Déconnexion utilisateur
   * @returns {Promise<Object>} - Réponse de l'API
   */
  async logout() {
    const response = await this.post(CONFIG.API_ENDPOINTS.AUTH, {
      action: 'logout',
    });

    this.isAuthenticated = false;
    this.csrfToken = null;

    return response;
  }

  /**
   * Soumet le formulaire de contact
   * @param {Object} formData - Données du formulaire
   * @returns {Promise<Object>} - Réponse de l'API
   */
  async submitContact(formData) {
    return this.post(CONFIG.API_ENDPOINTS.CONTACT, formData);
  }

  // ========== DASHBOARD ==========

  /**
   * Récupère les statistiques du dashboard
   * @returns {Promise<Object>} - Stats du dashboard
   */
  async getDashboardStats() {
    return this.get('/dashboard.php');
  }

  // ========== CLIENTS ==========

  /**
   * Récupère la liste des clients
   * @param {Object} filters - Filtres (status, search, limit, offset)
   * @returns {Promise<Object>} - Liste des clients
   */
  async getClients(filters = {}) {
    const params = new URLSearchParams(filters);
    return this.get(`/clients.php?${params}`);
  }

  /**
   * Récupère un client spécifique
   * @param {number} id - ID du client
   * @returns {Promise<Object>} - Client
   */
  async getClient(id) {
    return this.get(`/clients.php?id=${id}`);
  }

  /**
   * Crée un nouveau client
   * @param {Object} clientData - Données du client
   * @returns {Promise<Object>} - Client créé
   */
  async createClient(clientData) {
    return this.post('/clients.php', clientData);
  }

  /**
   * Met à jour un client
   * @param {number} id - ID du client
   * @param {Object} clientData - Données à mettre à jour
   * @returns {Promise<Object>} - Client mis à jour
   */
  async updateClient(id, clientData) {
    return this.put('/clients.php', { id, ...clientData });
  }

  /**
   * Supprime un client
   * @param {number} id - ID du client
   * @returns {Promise<Object>} - Confirmation
   */
  async deleteClient(id) {
    return this.delete('/clients.php', { id });
  }

  // ========== INVOICES ==========

  /**
   * Récupère la liste des factures
   * @param {Object} filters - Filtres (status, limit, offset)
   * @returns {Promise<Object>} - Liste des factures
   */
  async getInvoices(filters = {}) {
    const params = new URLSearchParams(filters);
    return this.get(`/invoices.php?${params}`);
  }

  /**
   * Récupère une facture spécifique
   * @param {number} id - ID de la facture
   * @returns {Promise<Object>} - Facture
   */
  async getInvoice(id) {
    return this.get(`/invoices.php?id=${id}`);
  }

  /**
   * Crée une nouvelle facture
   * @param {Object} invoiceData - Données de la facture
   * @returns {Promise<Object>} - Facture créée
   */
  async createInvoice(invoiceData) {
    return this.post('/invoices.php', invoiceData);
  }

  /**
   * Met à jour une facture
   * @param {number} id - ID de la facture
   * @param {Object} invoiceData - Données à mettre à jour
   * @returns {Promise<Object>} - Facture mise à jour
   */
  async updateInvoice(id, invoiceData) {
    return this.put('/invoices.php', { id, ...invoiceData });
  }

  /**
   * Supprime une facture
   * @param {number} id - ID de la facture
   * @returns {Promise<Object>} - Confirmation
   */
  async deleteInvoice(id) {
    return this.delete('/invoices.php', { id });
  }

  // ========== PROJECTS ==========

  /**
   * Récupère la liste des projets
   * @param {Object} filters - Filtres (status, client_id, limit, offset)
   * @returns {Promise<Object>} - Liste des projets
   */
  async getProjects(filters = {}) {
    const params = new URLSearchParams(filters);
    return this.get(`/projects.php?${params}`);
  }

  /**
   * Récupère un projet spécifique
   * @param {number} id - ID du projet
   * @returns {Promise<Object>} - Projet
   */
  async getProject(id) {
    return this.get(`/projects.php?id=${id}`);
  }

  /**
   * Crée un nouveau projet
   * @param {Object} projectData - Données du projet
   * @returns {Promise<Object>} - Projet créé
   */
  async createProject(projectData) {
    return this.post('/projects.php', projectData);
  }

  /**
   * Met à jour un projet
   * @param {number} id - ID du projet
   * @param {Object} projectData - Données à mettre à jour
   * @returns {Promise<Object>} - Projet mis à jour
   */
  async updateProject(id, projectData) {
    return this.put('/projects.php', { id, ...projectData });
  }

  /**
   * Supprime un projet
   * @param {number} id - ID du projet
   * @returns {Promise<Object>} - Confirmation
   */
  async deleteProject(id) {
    return this.delete('/projects.php', { id });
  }

  // ========== TASKS ==========

  /**
   * Récupère la liste des tâches
   * @param {Object} filters - Filtres (status, priority, project_id, client_id, limit, offset)
   * @returns {Promise<Object>} - Liste des tâches
   */
  async getTasks(filters = {}) {
    const params = new URLSearchParams(filters);
    return this.get(`/tasks.php?${params}`);
  }

  /**
   * Récupère une tâche spécifique
   * @param {number} id - ID de la tâche
   * @returns {Promise<Object>} - Tâche
   */
  async getTask(id) {
    return this.get(`/tasks.php?id=${id}`);
  }

  /**
   * Crée une nouvelle tâche
   * @param {Object} taskData - Données de la tâche
   * @returns {Promise<Object>} - Tâche créée
   */
  async createTask(taskData) {
    return this.post('/tasks.php', taskData);
  }

  /**
   * Met à jour une tâche
   * @param {number} id - ID de la tâche
   * @param {Object} taskData - Données à mettre à jour
   * @returns {Promise<Object>} - Tâche mise à jour
   */
  async updateTask(id, taskData) {
    return this.put('/tasks.php', { id, ...taskData });
  }

  /**
   * Supprime une tâche
   * @param {number} id - ID de la tâche
   * @returns {Promise<Object>} - Confirmation
   */
  async deleteTask(id) {
    return this.delete('/tasks.php', { id });
  }
}

// Créer une instance unique du client API
const apiClient = new ApiClient();
