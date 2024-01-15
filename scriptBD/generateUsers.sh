#!/bin/bash
read -p "Entrez le nom d'utilisateur de la base de données: " username
read -s -p "Entrez le mot de passe de l'utilisateur de la base de données: " password
read -p "Entrez le nom de la base de données: " database
read -p "Entrez l'adresse de l'hôte de la base de données: " host
checkconnection=$(mysql --user=$username --password=$password --database=$database --host=$host --execute="SELECT 1;" -s)
if [ -n "$checkconnection" ]; then
    echo "Connexion à la base de données réussie."
    read -p "Entrez le nombre d'utilisateurs à générer: " nbUsers

    url=$(curl -s -X POST 'https://api.json-generator.com/templates/o6h3k6fKzelR/data' \
                  -H 'Content-Type: application/json' \
                  -d '{"access_token": "40ie76mmkvod21ajihb93k5yqzzji7yzg43o5j2d"}')
    length=$($url | jq '.[] | length')

    echo $($url | jq '.')
    echo $($url | jq '.[].name')
    
    #for i in 0 .. $length
    #do
        #echo $(curl -s $url | jq '.[].name')
    #done
else
    echo "Connexion à la base de données échouée."
    exit 1
fi

# 40ie76mmkvod21ajihb93k5yqzzji7yzg43o5j2d