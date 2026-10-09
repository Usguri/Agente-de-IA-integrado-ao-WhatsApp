const express = require('express');
const path = require('path');
const router = express.Router();
const frontendDirectory = path.resolve(__dirname, '../../frontend/chat-secundario/public');

router.get('/', (req, res) => {
    res.sendFile(path.join(frontendDirectory, 'index.html'));
});

router.get('/tela-inicial', (req, res) => {
    res.sendFile(path.join(frontendDirectory, 'tela-inicial.html'));
});

module.exports = router;
