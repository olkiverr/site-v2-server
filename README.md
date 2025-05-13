ajoute google api client php
php mailer

# README pour le projet MangaMuse

## Description
Ce projet, intitulé **MangaMuse**, est un site web PHP centré sur la gestion des utilisateurs et des images, tout en mettant en avant des thèmes autour des mangas et des animés. Il inclut une interface utilisateur intuitive et utilise des opérations CRUD (Create, Read, Update, Delete) pour la gestion des données, ainsi qu'un forum de discussion et un système d'importation automatique d'anime depuis l'API Jikan.

## Fonctionnalités principales
- Authentification des utilisateurs (connexion et déconnexion).
- Gestion des utilisateurs (ajout, édition, suppression).
- Upload et suppression d'images liées à des mangas/animés.
- Panneau d'administration pour gérer le contenu.
- Forum de discussion avec communautés, sujets et commentaires.
- Importation automatique d'animés depuis l'API Jikan (MyAnimeList).
- Système de vote pour les sujets et commentaires du forum.
- Sélection aléatoire d'anime avec filtrage du contenu pour adultes.

## Structure du projet

### Racine du projet
- `index.php` : Page principale du site.
- `ajoutedbsql.py` : Script Python pour l'importation automatique des animés.

### Dossiers
1. **partials**
   - `header.php` : En-tête commune à toutes les pages.
   - `footer.php` : Pied de page commun à toutes les pages.

2. **php**
   - `add_user.php` : Ajout de nouveaux utilisateurs.
   - `auth.php` : Gestion de l'authentification.
   - `db.php` : Fichier de connexion à la base de données.
   - `delete_image.php` : Suppression d'images.
   - `delete_user.php` : Suppression d'utilisateurs.
   - `edit_user.php` : Modification des informations d'un utilisateur.
   - `logout.php` : Déconnexion de l'utilisateur.
   - `random_anime.php` : Sélection aléatoire d'un anime avec filtrage du contenu pour adultes.
   - `save_image.php` : Enregistrement d'images dans le système.
   - **forum/**
     - `forum_functions.php` : Fonctions pour le forum (gestion des posts, votes, etc).

3. **sql**
   - `mangamuse.sql` : Script SQL pour créer et initialiser la base de données.

4. **anime_img**
   - Contient des images liées à des mangas populaires (par exemple, "Blue Lock", "Sword Art Online", etc.).

5. **css**
   - `styles.css` : Style général du site.
   - Fichiers CSS supplémentaires pour des pages et composants spécifiques.
   - **forum/** : Styles spécifiques pour le forum.

6. **img**
   - Contient des icônes et des logos utilisés sur le site (par exemple, logos MangaMuse, icônes utilisateur, etc.).

7. **js**
   - `admin-panel.js` : Script JavaScript pour les fonctionnalités du panneau d'administration.
   - Scripts pour la connexion, l'inscription, et des interactions sur le site.

8. **pages**
   - `add_user.php` : Page pour ajouter un utilisateur.
   - `admin_panel.php` : Panneau d'administration principal.
   - `edit_user.php` : Page pour éditer les utilisateurs.
   - `login.php` : Page de connexion.
   - `register.php` : Page d'inscription.
   - `forum.php` : Page principale du forum.
   - `forum_topic.php` : Page de sujet individuel du forum.
   - `m.php` : Page de communauté du forum.
   - `forum_new_topic.php` : Page pour créer un nouveau sujet.
   - `create_community.php` : Page pour créer une nouvelle communauté.

9. **logs**
   - Contient les fichiers de logs générés par le script d'importation d'animés.
   
10. **cache**
    - Stockage des réponses API en cache pour optimiser les requêtes d'importation.

## Installation

1. **Cloner le répertoire** :
   ```bash
   git clone <url-du-repository>
   cd <nom-du-repertoire>
   ```

2. **Configurer la base de données** :
   - Importez le fichier `mangamuse.sql` dans votre base de données MySQL.
   - Modifiez `php/db.php` avec vos informations de connexion MySQL :
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'nom_de_la_base');
     define('DB_USER', 'utilisateur');
     define('DB_PASS', 'mot_de_passe');
     ```

3. **Déployer sur un serveur local** :
   - Placez tous les fichiers et dossiers dans le dossier racine de votre serveur (par exemple, `htdocs` pour XAMPP).

4. **Configurer l'environnement Python (pour l'importation d'anime)** :
   - Installez Python 3.6 ou supérieur
   - Installez les dépendances requises :
     ```bash
     pip install requests mysql-connector-python
     ```
   - Créez les dossiers `logs` et `cache` s'ils n'existent pas

5. **Accéder au site** :
   Ouvrez votre navigateur et rendez-vous sur :
   ```
   http://localhost/site-v2
   ```

## Utilisation du script d'importation d'anime

Le script `ajoutedbsql.py` permet d'importer automatiquement des animés depuis l'API Jikan (MyAnimeList).

### Exemples d'utilisation :

```bash
# Importation de base (commence à la page 1)
python ajoutedbsql.py

# Importation à partir d'une page spécifique
python ajoutedbsql.py --start 10

# Importation avec une plage de pages définies
python ajoutedbsql.py --start 1 --end 5

# Filtrer le contenu pour adultes
python ajoutedbsql.py --filter-adult

# Accélérer l'importation en ignorant les détails des créateurs
python ajoutedbsql.py --skip-details

# Utiliser plusieurs threads pour les requêtes parallèles
python ajoutedbsql.py --threads 4
```

## Forum

Le forum MangaMuse permet aux utilisateurs de:
- Créer et rejoindre des communautés thématiques
- Créer des sujets de discussion
- Commenter et voter sur les sujets et commentaires
- Rechercher des communautés et des sujets

Les administrateurs peuvent également:
- Modérer le contenu (supprimer/éditer des sujets et commentaires)
- Gérer les communautés
- Configurer la base de données du forum

## Prérequis
- PHP 7.4 ou supérieur
- Serveur web (Apache ou Nginx)
- MySQL
- Python 3.6+ (pour le script d'importation d'anime)

## Dépendances externes
- Google API Client (PHP)
- PHPMailer
- MySQL Connector pour Python
- Requests (Python)

## Améliorations futures
- Implémentation de la pagination pour la liste des utilisateurs.
- Ajout de validations côté client pour les formulaires.
- Amélioration de l'interface utilisateur avec un framework CSS (Bootstrap ou Tailwind).
- Ajout d'un système de rôles pour gérer les droits d'accès.
- Intégration d'un système de notifications pour le forum.
- Ajout de fonctionnalités de recherche avancée pour les animés.

## Auteur
- **Nom** : Zielinski Olivier
