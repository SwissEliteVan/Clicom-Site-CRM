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

    // Ajouter le CSRF token pour les requêtes POST
    if (options.method === 'POST' && this.csrfToken) {
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
}

// Créer une instance unique du client API
const apiClient = new ApiClient();
