#!/usr/bin/env python

import os
import requests  # type: ignore

def BaixarArquivo(token, url, nome):

    headers = {
        "Authorization": "Bearer {}".format(token)
    }

    response = requests.get(url, headers=headers)
    if response.status_code == 200:

        nome += '.ogg'
        
        caminho_arquivo = os.path.join('aud-recebidos-cli', nome) # Caminho completo para salvar o arquivo
        
        # Verifica se o diretório existe e o cria se não existir
        os.makedirs(os.path.dirname(caminho_arquivo), exist_ok=True)
        
        with open(caminho_arquivo, 'wb') as f: # Salva o conteúdo da resposta no arquivo
            f.write(response.content)
        
        print("Áudio baixado com sucesso como!")
    else:
        print("Erro ao baixar o arquivo. Status Code: {}".format(response.status_code))
        print("Conteúdo da resposta: {}".format(response.text))

if __name__ == "__main__":
    token = os.getenv('TOKEN')
    url = os.getenv('URL')
    nome = os.getenv('NOME')

    BaixarArquivo(token, url, nome)
