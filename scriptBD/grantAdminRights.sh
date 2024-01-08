#!/bin/bash

# Demander l'email de l'utilisateur
read -p "Entrez l'email de l'utilisateur à mettre à jour en admin: " email

# Validate the email address
if [[ "$email" =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$ ]]
then
    # Vérifier si l'email existe dans la base de données
    user_exists=$(mysql --user=qartigala --password=5asTWrkD --database=etu_qartigala --host=info-titania --execute="SELECT COUNT(*) FROM user WHERE email='$email' and admin=0;" -s)

    if [ -n "$user_exists" ] && [ $user_exists -eq 1 ]; then
        # Mettre à jour le statut de l'utilisateur en admin
        mysql --user=qartigala --password=5asTWrkD --database=etu_qartigala --host=info-titania --execute="UPDATE user SET admin=1 WHERE email='$email';"
        echo "Mise à jour effectuée avec succès. L'utilisateur avec l'email $email est maintenant un administrateur."
    else
        echo "L'utilisateur avec l'email $email n'existe pas ou est déjà admin dans la base de données."
    fi
else
    echo "L'email entré n'est pas valide."
fi