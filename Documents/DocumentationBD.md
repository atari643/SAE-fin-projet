# Documentation de la base de données

Ce document a pour objectif d'assurer une meilleure compréhension de la base grâce à une description conceptuelle des données.

## Contexte
Cette base de données est utilisée dans le cadre d'un projet de conception d'une web-app. Dans cette web-app, la base servira à enregistrer de nouveaux utilisateurs, de nouvelles séries, ou encore à stocker les séries vues et suivies par ces utilisateurs.

Voici le schéma MCD de la base :

![MCD](MCD-SaeFinS3.png)

## Description des tables

- ### Table *actor*
    
    Cette table sert à stocker des informations sur les différents acteurs.

    _**Attributs :**_
        
    - **id**: l'identifiant de la table.
    - **name**: nom de l'acteur.

    _**Contraintes :**_
        
    - **id** : clé primaire.
    - Unicité de **actor.name**.
    - Tous les attributs sont obligatoires.
  
    ---

- ### Table *actor_series*
    
    Cette table est une table d'association pour établir une relation many-to-many entre des acteurs et des series.

    _**Attributs :**_
        
    - **actor_id** : identifiant de l'acteur.
    - **series_id** : identifiant de la serie.
        
    _**Contraintes :**_
    - **actor_id** : clé étrangère faisant réfèrence à _**actor.id**_.
    - **series_id** : clé étrangère faisant réfèrence à _**series.id**_.
    - Unicité du couple (**acotor_id**,**series_id**)
    - Tous les attributs sont obligatoires.
  
    ---

- ### Table *country*
    
     Cette table sert à stocker des informations sur les différents pays.

    _**Attributs :**_
        
    - **id** : identifiant de la table.
    - **name** : nom du pays.

    _**Contraintes :**_
        
    - **id** : clé primaire.
    - Unicité de l'attribut **name**.
    - Tous les attributs sont obligatoires.

    ---
  
- ### Table *country_series*

    Cette table est une table d'association pour établir une relation many-to-many entre des séries et des pays.

    _**Attributs :**_
        
    - **country_id** : identifiant du pays.
    - **series_id** : identifiant de la serie.
        
    _**Contraintes :**_

    - **country_id** : clé étrangère faisant référence à _**country.id**_.
    - **series_id** : clé étrangère faisant référence à _**series.id**_.
    - Unicité du couple (**country_id**,**series_id**)
    - Tous les attributs sont obligatoires.

    --- 

- ### Table *episode*

    Cette table sert à stocker des informations sur les différents épisodes des séries.

    _**Attributs :**_
        
    - **id** : identifiant de la table.
    - **season_id** : identifiant de la saison à laquelle l'épisode appartient.
    - **title** : titre de l'épisode.
    - **date** : date de l'épisode.
    - **imdb** : identifiant IMDb de l'épisode.
    - **imdbrating** : note IMDb de l'épisode.
    - **number** : numéro de l'épisode dans la saison.
        
    _**Contraintes :**_

    - **id** : clé primaire.
    - **season_id** : clé étrangère faisant référence à _**season.id**_
    - Unicité de l'attribut **imdb**.
    - Tous les attributs sont obligatoires.

    #### Note :
    L'attribut _**imdb**_ fait référence à Internet Movie Database (IMDb), une base de données en ligne regroupant des informations sur les films, émissions de télévision, séries, jeux vidéo et professionnels de l'industrie cinématographique.

    ---

- ### Table *external_rating*

    Cette table sert à stocker des évaluations externes ou des notations associées à des séries dans une base de données.

    _**Attributs :**_
        
    - **id** : identifiant de la table.
    - **series_id** : identifiant de la série à laquelle l'évaluation appartient.
    - **source_id** : identifiant de la source de l'évaluation.
    - **value** : valeur de l'évaluation.
    - **votes** : nombre de votes associés à l'évaluation.
        
    _**Contraintes :**_

    - **id** : clé primaire.
    - **series_id** : clé étrangère faisant référence à _**series.id**_.
    - **source_id** : clé étrangère faisant référence à _**external_rating_source.id**_.
    - Tous les attributs sont obligatoires sauf _**votes**_, qui est par défaut à **NULL**.

    ---

- ### Table *external_rating_source*

    Cette table sert à stocker des informations sur les différentes sources externes qui fournissent des évaluations pour les séries.

    _**Attributs :**_
        
    - **id** : identifiant de la table.
    - **name** : nom de la source.
        
    _**Contraintes :**_

    - **id** : clé primaire.
    - Unicité de l'attribut **name**.
    - Tous les attributs sont obligatoires.
  
    ---

- ### Table *genre*

    Cette table stocke des informations sur les différents genres associés aux séries.

    _**Attributs :**_
        
    - **id** : identifiant de la table.
    - **name** : nom du genre.
        
    _**Contraintes :**_

    - **id** : clé primaire.
    - Unicité de l'attribut **name**.
    - Tous les attributs sont obligatoires.
    
    ---

- ### Table _**genre_series**_

    Cette table est une table d'association pour établir une relation many-to-many entre les series et leurs genres.

    _**Attributs :**_

    - **genre_id** : identifiant du genre.
    - **series_id** : identifiant de la serie.

    _**Contraintes :**_

    - **genre_id** : clé étrangère référencant **genre.id**.
    - **series_id** : clé étrangère référencant **series.id**
    - Unicité du couple (**genre_id**,**series_id**)
    - Tous les attributs obligatoires.
  
    ---

- ### Table *rating*

    Cette table enregistre les évaluations données par les utilisateurs aux séries.

    _**Attributs :**_
        
    - **id** : identifiant de la table.
    - **series_id** : identifiant de la série évaluée.
    - **user_id** : identifiant de l'utilisateur qui a donné l'évaluation.
    - **value** : valeur de l'évaluation attribuée à la série.
    - **comment** : commentaire associé à l'évaluation.
    - **date** : date à laquelle l'évaluation a été donnée.

    _**Contraintes :**_

    - **id** : clé primaire.
    - **series_id** : clé étrangère faisant référence à _**series.id**_.
    - **user_id** : clé étrangère faisant référence à _**user.id**_.
    - Tous les attributs obligatoires sauf **comment**.

    ---

- ### Table *season*

    Cette table enregistre des informations sur les saisons associées aux séries.

    _**Attributs :**_
        
    - **id** : identifiant de la saison.
    - **series_id** : identifiant de la série à laquelle la saison appartient.
    - **number** : numéro de la saison dans la série.

    _**Contraintes :**_

    - **id** : clé primaire.
    - **series_id** : clé étrangère faisant référence à _**series.id**_.
    - Tous les attributs sont obligatoires.

    ---

- ### Table *series*

    Cette table stocke des informations sur les différentes séries de l'application.

    _**Attributs :**_
        
    - **id** : identifiant de la série.
    - **title** : nom de la série.
    - **plot** : intrigue de la série.
    - **imdb** : identifiant IMDb de la série. [note](#note)
    - **poster** : affiche de la série.
    - **director** : réalisateur de la série.
    - **youtube_trailer** : vidéo du trailer.
    - **awards** : récompenses de la série.
    - **year_start** : date de début de la série.
    - **year_end** : date de fin de la série.

    _**Contraintes :**_

    - **id** : clé primaire.
    - Unicité des attributs **title**, **imdb**, **poster**, **youtube_trailer**.
    - Les attributs obligatoires sont **id**, **title**, **imdb**.

    ---

- ### Table *user*  

    Cette table stocke des informations sur les utilisateurs de l'application.

    _**Attributs :**_

    - **id** : identifiant de la table.
    - **name** : nom de l'utilisateur.
    - **email** : email valide de l'utilisateur.
    - **password** : mot de passe chiffré de l'utilisateur.
    - **register_date** : date de création du compte.
    - **admin** : 1 si l'utilisateur est admin sinon 0.
    - **country_id** : identifiant du pays de l'utilisateur.
    
    _**Contraintes :**_

    - **id** : clé primaire.
    - Unicité de l'attribut **email**.
    - **country_id** : clé étrangère référencant **country.id**
    - Tous les attributs obligatoires sauf **register_date** et **country_id**.
  
    ---

- ### Table _**user_episode**_

    Cette table est une table d'association pour établir une relation many-to-many entre les utilisateurs et les épisodes qu'ils suivent.

    _**Attributs :**_

    - **user_id** : identifiant de l'utilisateur.
    - **episode_id** : identifiant de l'épisode.

    _**Contraintes :**_

    - **user_id** : clé étrangère référencant **user.id**.
    - **episode_id** : clé étrangère référencant **episode.id**
    - Unicité du couple (**user_id**,**episode_id**)
    - Tous les attributs obligatoires.

    ---

- ### Table _**user_series**_

    Cette table est une table d'association pour établir une relation many-to-many entre les utilisateurs et les series qu'ils suivent.

    _**Attributs :**_

    - **user_id** : identifiant de l'utilisateur.
    - **series_id** : identifiant de la serie.

    _**Contraintes :**_

    - **user_id** : clé étrangère référencant **user.id**.
    - **series_id** : clé étrangère référencant **series.id**
    - Unicité du couple (**user_id**,**series_id**)
    - Tous les attributs obligatoires.







