# Installation et connexion au projet de site de film avec Symfony et MySQL

## Installer et connecter un projet Symfony
"""
Ce code est un exemple de procédure d'installation et de connexion d'un projet Symfony.

Pour installer et connecter un projet Symfony, suivez les étapes suivantes :

1. Assurez-vous d'avoir PHP installé sur votre machine. Vous pouvez vérifier en exécutant la commande suivante dans votre terminal :
    ```
    php -v
    ```
    Si PHP est installé, vous devriez voir la version de PHP installée sur votre machine.
    Si PHP n'est pas installé, vous pouvez télécharger PHP à partir du site officiel : https://www.php.net/downloads.php

2. Installez Symfony, en exécutant la commande suivante dans votre terminal :
    ```
    wget https://get.symfony.com/cli/installer -O - | bash
    ```
    Si vous utilisez Windows, vous pouvez télécharger l'installateur Symfony à partir du site officiel : https://symfony.com/download

3. Ouvrez votre terminal et aller dans le .bashrc avec la commande suivante :
    ```
    nano ~/.bashrc
    ```
    Ajoutez la ligne suivante à la fin du fichier :
    ```
    export PATH=$PATH:$HOME/symfony
    ```
    Enregistrez le fichier et fermez-le.
    Cela permettra d'exécuter la commande Symfony depuis n'importe quel répertoire de votre machine.


4. Maintenant aller dans notre projet symfony et exécuter la commande suivante :
    ```
    symfony server:start
    ```
    Si vous utilisez Windows, vous pouvez également exécuter la commande suivante :
    ```
    symfony serve
    ```
5. Ouvrez votre navigateur et accédez à l'URL suivante : http://localhost:8000
    Vous devriez voir la page d'accueil de votre projet Symfony.

Maintenant, vous avez installé et connecté avec succès votre projet Symfony !

N'oubliez pas de consulter la documentation officielle de Symfony pour en savoir plus sur le développement avec Symfony : https://symfony.com/doc/current/index.html

## Installation de la base de données

Notre projet Symfony utilise une base de données MySQL. Pour installer la base de données, suivez les étapes suivantes :

1. Connecter vous sur la platforme myphpadmin de votre serveur avec ce lien : https://apps.iut.u-bordeaux.fr/phpmyadmin/

2. Rentrer vos identifiants de connexion

3. Dans la barre de navigation il faut aller dans l'onglet "importer"

4. Dans fichier à importer, Dans  "Fichier à importer", cliquer sur "Parcourir" et sélectionner le fichier "shows.sql.zip" qui se trouve dans le dossier "Documents" du projet.

5. Puis décendre en bas de la page et cliquer sur "Importer"

6. La base de données est maintenant installée.

## Connexion à la base de données

Pour vous connecter à la base de données, suivez les étapes suivantes :

1. Ouvrez le fichier ".env" qui se trouve à la racine du projet.

2. Dans le fichier ".env", modifiez la ligne suivante :
    ```
    DATABASE_URL=mysql://USERNAME:PASSWORD@SERVER/HOST
    ```
    Remplacez "USERNAME" par votre nom d'utilisateur et "PASSWORD" par votre mot de passe. Remplacez "SERVER" par le nom du serveur et "HOST" par le nom de la base de données.

3. Enregistrez le fichier et fermez-le.

4. Maintenant, vous êtes connecté à la base de données !

## Modifier les droits d'administration des utilisateurs du site

Pour modifier les droits d'administration des utilisateurs du site, suivez les étapes suivantes :

Pour mettre un utilisateur adminstrateur lancer le script:
    ```
    ./scriptBDAdminRights/grantAdminRights.sh
    ```

Pour enlever un utilisateur adminstrateur lancer le script:
    ```
    ./scriptBDAdminRights/removeAdminRights.sh
    ```

