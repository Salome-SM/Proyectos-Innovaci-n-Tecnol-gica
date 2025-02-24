// Archivo: assets/js/utils/api.js
export async function apiRequest(endpoint, options = {}) {
  try {
      // Asegurarnos que la ruta del endpoint es correcta
      const baseUrl = '/plant_counter'; // Ajusta esto según tu estructura
      const url = `${baseUrl}/${endpoint}`;

      const response = await fetch(url, {
          ...options,
          headers: {
              ...options.headers,
              'Accept': 'application/json'
          }
      });

      if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
      }

      const contentType = response.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
          const data = await response.json();
          return data;
      } else {
          throw new Error('La respuesta no es JSON válido');
      }
  } catch (error) {
      console.error('API Request Error:', error);
      throw error;
  }
}

export function formatError(error) {
  return {
      message: error.message || 'Ocurrió un error inesperado',
      timestamp: new Date().toISOString(),
      code: error.code || 'UNKNOWN_ERROR'
  };
}