#!/bin/bash
read -p "Entrez le nom d'utilisateur de la base de données: " username
read -s -p "Entrez le mot de passe de l'utilisateur de la base de données: " password
read -p "Entrez le nom de la base de données: " database
read -p "Entrez l'adresse de l'hôte de la base de données: " host
checkconnection=$(mysql --user=$username --password=$password --database=$database --host=$host --execute="SELECT 1;" -s)
if [ -n "$checkconnection" ]; then
    echo "Connexion à la base de données réussie."
    read -p "Entrez l'email de l'utilisateur à mettre à jour en admin: " email
    if [[ "$email" =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$ ]]
    then
        user_exists=$(mysql --user=$username--password=$password --database=$database --host=$host --execute="SELECT COUNT(*) FROM user WHERE email='$email' and admin=0;" -s)
        if [ -n "$user_exists" ] && [ $user_exists -eq 1 ]; then
            mysql --user=$username--password=$password --database=$database --host=$host --execute="UPDATE user SET admin=1 WHERE email='$email';"
            echo "Mise à jour effectuée avec succès. L'utilisateur avec l'email $email est maintenant un administrateur."
        else
            echo "L'utilisateur avec l'email $email n'existe pas ou est déjà admin dans la base de données."
        fi
    else
        echo "L'email entré n'est pas valide."
    fi
else
    echo "Connexion à la base de données échouée."
    exit 1
fi

