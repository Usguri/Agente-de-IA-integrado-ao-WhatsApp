#!/usr/bin/env python

import os
import requests  # type: ignore

def BaixarArquivo(token, url, nome):
    headers = {
        "Authorization": "Bearer {}".format(token)
    }

    response = requests.get(url, headers=headers)

    if response.status_code == 200:
        print("status: ", response)
        # Caminho completo para salvar o arquivo
        caminho_arquivo = os.path.join('arq-recebidos-cli', nome)
        print("nome arq: ", caminho_arquivo)
        # Salva o conteúdo da resposta no arquivo
        with open(caminho_arquivo, 'wb') as f:
            f.write(response.content)
        
        print("Arquivo baixado com sucesso!")
    else:
        print("Erro ao baixar o arquivo. Status Code:", response.status_code)
        print("Conteúdo da resposta:", response.text)

if __name__ == "__main__":
    token = os.getenv('TOKEN')
    url = os.getenv('URL')
    nome = os.getenv('NOME')


    BaixarArquivo(token, url, nome)
