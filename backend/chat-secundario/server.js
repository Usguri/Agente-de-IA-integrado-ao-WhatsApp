const express = require('express');
const http = require('http');
const path = require('path');
const oracledb = require('oracledb');
const ldap = require('ldapjs');
const crypto = require('crypto');
const frontendDirectory = path.resolve(__dirname, '../../frontend/chat-secundario/public');
const indexRouter = require('./router.js');

const app = express();
const server = http.createServer(app);

// Configurações de conexão com Oracle
const dbConfig = {
    user: "user",
    password: "passwor",
    connectString: "conexão"
};

// Middleware para analisar o corpo das requisições JSON
app.use(express.json());

// Middleware para servir arquivos estáticos
app.use(express.static(frontendDirectory));

// Middleware para rotas
app.use('/', indexRouter);

// ------------------------------------------------------------------------ \\

// Função para verificar login e autenticação via LDAP e OracleDB
async function verificaLogin(usuario, senha) {
    const client = ldap.createClient({ url: 'ip' });

    // Tratamento de erros de conexão LDAP
    client.on('error', (err) => {
        console.error('Erro na conexão com o LDAP:', err);
    });

    return new Promise((resolve, reject) => {
        client.bind(usuario + '@cotriba', senha, async (err) => {
            if (err) {
                console.error('Erro ao autenticar o usuário no LDAP:', usuario, err);
                reject(err);
            } else {
                console.log('Autenticação bem-sucedida para', usuario);

                // Desconectar do LDAP após autenticação
                client.unbind((err) => {
                    if (err) {
                        console.error('Erro ao desconectar do LDAP:', err);
                    } else {
                        console.log('Desconexão bem-sucedida do LDAP!');
                    }
                });

                try {
                    // Conexão com o OracleDB
                    const connection = await oracledb.getConnection(dbConfig);

                    // Deletar usuário caso já esteja logado
                    await connection.execute(`DELETE FROM CH_USUARIO WHERE USUARIO = :usuario`, { usuario: usuario }, { autoCommit: true });


                    // token de acesso
                    function generateRandomToken(length = 32) {
                        const charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                        let token = '';
                        const randomValues = crypto.randomBytes(length);

                        for (let i = 0; i < length; i++) {
                            token += charset[randomValues[i] % charset.length];
                        }

                        return token;
                    }

                    const token = generateRandomToken();

                    // Inserir novo registro de login
                    const result = await connection.execute(`INSERT INTO CH_USUARIO (USUARIO, TOKEN, DATA) VALUES (:usuario, :token, SYSTIMESTAMP)`, { usuario: usuario, token: token }, { autoCommit: true });
                    console.log('Usuário logado com sucesso:', result.rowsAffected);

                    // Buscar o ID do usuário
                    const result1 = await connection.execute(`SELECT ZUSER FROM Z_USER_PESSOA WHERE USUARIO_CINCO = :usuario`, { usuario: usuario });
                    const usuarios = result1.rows.map((row) => ({ contato: row[0] }));

                    await connection.close();

                    resolve([token, usuarios[0].contato, usuario]);

                } catch (dbErr) {
                    console.error('Erro ao operar no OracleDB:', dbErr);
                    reject(dbErr);
                }
            }
        });
    });
}

// Rota para verificar login (chamada do frontend)
app.post('/verifica-login', async (req, res) => {
    const { usuario, senha } = req.body;

    try {
        const resultado = await verificaLogin(usuario, senha);
        res.json({ sucesso: true, dados: resultado });
    } catch (err) {
        res.status(500).json({ sucesso: false, mensagem: 'Erro ao verificar login', erro: err.message });
    }
});

app.post('/verificalogincinco', async (req, res) => {

    const { userId, username } = req.body;

    try {
        // Conexão com o OracleDB
        const connection = await oracledb.getConnection(dbConfig);

        // Deletar usuário caso já esteja logado
        await connection.execute(`DELETE FROM CH_USUARIO WHERE USUARIO = :usuario`, { usuario: username }, { autoCommit: true });

        // token de acesso
        function generateRandomToken(length = 32) {
            const charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let token = '';
            const randomValues = crypto.randomBytes(length);

            for (let i = 0; i < length; i++) {
                token += charset[randomValues[i] % charset.length];
            }

            return token;
        }

        const token = generateRandomToken();

        // Inserir novo registro de login
        const result = await connection.execute(`INSERT INTO CH_USUARIO (USUARIO, TOKEN, DATA) VALUES (:usuario, :token, SYSTIMESTAMP)`, { usuario: username, token: token }, { autoCommit: true });
        console.log('Usuário logado com sucesso:', result.rowsAffected);

        await connection.close();

        res.json({ sucesso: true, dados: token });

    } catch (dbErr) {
        console.error('Erro ao operar no OracleDB:', dbErr);
        reject(dbErr);
    }

});

// ------------------------------------------------------------------------ \\

// Iniciar o servidor Express
const PORT = 5000;
server.listen(PORT, () => {
    console.log(`Servidor rodando em http://localhost:${PORT}`);
});

// Tratamento global de exceções não capturadas
process.on('uncaughtException', (err) => {
    console.error('Ocorreu um erro inesperado:', err);
    process.exit(1);
});
