// The dev server forwards /api requests to the API, so the browser sees one address (no CORS needed).
// API_URL is set by docker-compose; when running without Docker the API is on localhost.
module.exports = {
  '/api': {
    target: process.env.API_URL || 'http://localhost:3001',
  },
};
