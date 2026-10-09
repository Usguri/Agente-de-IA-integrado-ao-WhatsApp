const express = require('express');
const http = require('http');
const path = require('path');

// Configuração Express e criação do servidor HTTP
const app = express();
const server = http.createServer(app);
const frontendDirectory = path.resolve(__dirname, '../../../frontend/chat-secundario/public');

// Configurações de conexão com Oracle
const dbConfig = {
    user: "VIASOFT",
    password: "VIASOFT",
    connectString: "rac-scan/prod.cotriba"
};

// Middleware para servir arquivos estáticos e rotas
app.use(express.json());
app.use(express.static(frontendDirectory));

// Exportar o app e o server para serem reutilizados em outros módulos
module.exports = { app, server, dbConfig };