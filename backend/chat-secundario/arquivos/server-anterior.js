const express = require('express');
const http = require('http');
const path = require('path');
const indexRouter = require('../router.js');
const backendRoutes = require('./public/backend/rotasback.js');

// Configuração Express e criação do servidor HTTP
const app = express();
const server = http.createServer(app);
const frontendDirectory = path.resolve(__dirname, '../../../frontend/chat-secundario/public');

// Middleware para servir arquivos estáticos
app.use(express.json());
app.use(express.static(frontendDirectory));

app.use('/', indexRouter);
app.use('/api', backendRoutes);

// Iniciar o servidor
const PORT = 5000;
server.listen(PORT, () => {
    console.log(`Servidor rodando em http://localhost:${PORT}`);
});

// Exportar app e server para reutilização
module.exports = { app, server };
