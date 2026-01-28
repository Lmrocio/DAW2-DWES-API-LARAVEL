window.onload = function() {
  // Changeable Configuration Block

  window.ui = SwaggerUIBundle({
    url: window.location.origin + '/storage/api-docs/api-docs.json',
    dom_id: '#swagger-ui',
    deepLinking: true,
    presets: [
      SwaggerUIBundle.presets.apis,
      SwaggerUIStandalonePreset
    ],
    plugins: [
      SwaggerUIBundle.plugins.DownloadUrl
    ],
    layout: 'StandaloneLayout'
  });
};
