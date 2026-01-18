/**
 * Configuration centralisée pour l'application CLICOM CRM
 *
 * Ce fichier contient toutes les configurations globales de l'application
 * notamment les URLs d'API, les constantes et les paramètres.
 */

const CONFIG = {
  // URL de l'API Backend
  // En production: https://api.clicom.ch
  // En développement: http://localhost/backend/api (ou votre URL locale)
  API_BASE_URL: window.location.hostname === 'localhost'
    ? 'http://localhost/backend/api'
    : 'https://api.clicom.ch',

  // Endpoints de l'API
  API_ENDPOINTS: {
    AUTH: '/auth.php',
    CONTACT: '/contact.php',
    CLIENTS: '/clients.php',
    INVOICES: '/invoices.php',
    PROJECTS: '/projects.php',
    TASKS: '/tasks.php',
  },

  // Paramètres de l'application
  APP: {
    NAME: 'CLICOM CRM',
    VERSION: '1.0.0',
    LANGUAGE: 'fr',
  },

  // Paramètres de sécurité
  SECURITY: {
    SESSION_TIMEOUT: 30 * 60 * 1000, // 30 minutes en millisecondes
    CSRF_HEADER: 'X-CSRF-Token',
  },

  // Messages d'erreur par défaut
  MESSAGES: {
    NETWORK_ERROR: 'Erreur de connexion au serveur. Veuillez réessayer.',
    AUTH_REQUIRED: 'Vous devez être connecté pour accéder à cette page.',
    SESSION_EXPIRED: 'Votre session a expiré. Veuillez vous reconnecter.',
    GENERIC_ERROR: 'Une erreur est survenue. Veuillez réessayer.',
  },
};

// Geler l'objet pour éviter les modifications accidentelles
Object.freeze(CONFIG);
Object.freeze(CONFIG.API_ENDPOINTS);
Object.freeze(CONFIG.APP);
Object.freeze(CONFIG.SECURITY);
Object.freeze(CONFIG.MESSAGES);
