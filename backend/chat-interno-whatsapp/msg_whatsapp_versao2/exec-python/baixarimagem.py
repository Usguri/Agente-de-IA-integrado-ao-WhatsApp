#!/usr/bin/env python

import os
import requests  # type: ignore

def BaixarArquivo(token, url, nomearq, typearq):
    headers = {
        "Authorization": "Bearer {}".format(token)
    }

    response = requests.get(url, headers=headers)
    
    if response.status_code == 200:

        # Determine a extensão do arquivo com base no tipo de conteúdo
        if typearq == 'image/jpeg':
            ext = '.jpg'
        elif typearq == 'image/png':
            ext = '.png'
        elif typearq == 'image/gif':
            ext = '.gif'
        else:
            print("Tipo de conteúdo desconhecido: {}".format(typearq))
            return

        nome_completo = "{}{}".format(nomearq, ext)
        
        caminho_arquivo = os.path.join('img-recebidas-cli', nome_completo)
        
        with open(caminho_arquivo, 'wb') as f:
            f.write(response.content)
        
        print("Arquivo baixado com sucesso!")
    else:
        print("Erro ao baixar o arquivo. Status Code: {}".format(response.status_code))
        print("Conteúdo da resposta: {}".format(response.text))

if __name__ == "__main__":
    token = os.getenv('TOKEN')
    url = os.getenv('URL')
    nomearq = os.getenv('NOME')
    typearq = os.getenv('TIPOARQ')
    
    
    BaixarArquivo(token, url, nomearq, typearq)
