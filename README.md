ajoute google api client php
php mailer

# README pour le projet MangaMuse

## Description
Ce projet, intitulé **MangaMuse**, est un site web PHP centré sur la gestion des utilisateurs et des images, tout en mettant en avant des thèmes autour des mangas et des animés. Il inclut une interface utilisateur intuitive et utilise des opérations CRUD (Create, Read, Update, Delete) pour la gestion des données.

## Fonctionnalités principales
- Authentification des utilisateurs (connexion et déconnexion).
- Gestion des utilisateurs (ajout, édition, suppression).
- Upload et suppression d'images liées à des mangas/animés.
- Panneau d'administration pour gérer le contenu.

## Structure du projet

### Racine du projet
- `index.php` : Page principale du site.

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
   - `save_image.php` : Enregistrement d'images dans le système.

3. **sql**
   - `mangamuse.sql` : Script SQL pour créer et initialiser la base de données.

4. **anime_img**
   - Contient des images liées à des mangas populaires (par exemple, "Blue Lock", "Sword Art Online", etc.).

5. **css**
   - `styles.css` : Style général du site.
   - Fichiers CSS supplémentaires pour des pages et composants spécifiques (édition d'utilisateurs, en-tête, pied de page, etc.).

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

4. **Accéder au site** :
   Ouvrez votre navigateur et rendez-vous sur :
   ```
   http://localhost/site-v2
   ```

## Prérequis
- PHP 7.4 ou supérieur
- Serveur web (Apache ou Nginx)
- MySQL

## Améliorations futures
- Implémentation de la pagination pour la liste des utilisateurs.
- Ajout de validations côté client pour les formulaires.
- Amélioration de l'interface utilisateur avec un framework CSS (Bootstrap ou Tailwind).
- Ajout d'un système de rôles pour gérer les droits d'accès.

## Auteur
- **Nom** : Zielinski Olivier
