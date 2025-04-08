import requests
import mysql.connector
import time
import json
import argparse
import sys
import os
import logging
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime
import hashlib

# Configurer le système de logs
class Logger:
    def __init__(self, log_dir="logs", log_level=logging.INFO):
        self.terminal = sys.stdout
        
        # Créer le répertoire de logs s'il n'existe pas
        os.makedirs(log_dir, exist_ok=True)
        
        # Configurer le logger
        self.logger = logging.getLogger("MangamuseImporter")
        self.logger.setLevel(log_level)
        
        # Définir le format des logs
        formatter = logging.Formatter('%(asctime)s - %(levelname)s - %(message)s')
        
        # Créer un fichier de log avec la date du jour
        timestamp = datetime.now().strftime("%Y-%m-%d_%H-%M-%S")
        log_filename = os.path.join(log_dir, f"import_{timestamp}.log")
        
        # Configurer le handler de fichier
        file_handler = logging.FileHandler(log_filename, encoding='utf-8')
        file_handler.setFormatter(formatter)
        self.logger.addHandler(file_handler)
        
        print(f"Les logs seront enregistrés dans: {log_filename}")
    
    def log(self, message, level=logging.INFO):
        """Enregistre un message dans le fichier de logs et l'affiche aussi dans le terminal"""
        # Afficher dans le terminal
        print(message)
        
        # Enregistrer dans le fichier de logs
        if level == logging.DEBUG:
            self.logger.debug(message)
        elif level == logging.INFO:
            self.logger.info(message)
        elif level == logging.WARNING:
            self.logger.warning(message)
        elif level == logging.ERROR:
            self.logger.error(message)
        elif level == logging.CRITICAL:
            self.logger.critical(message)

def parse_arguments():
    parser = argparse.ArgumentParser(description="Ajouter des animés à la base de données MangaMuse")
    parser.add_argument("--start", type=int, default=1, help="Page de départ (par défaut: 1)")
    parser.add_argument("--end", type=int, help="Page de fin (optionnel, va jusqu'à la dernière page disponible si non spécifié)")
    parser.add_argument("--filter-adult", action="store_true", help="Activer le filtre pour exclure le contenu adulte (Ecchi, Erotica, Hentai)")
    parser.add_argument("--no-filter", action="store_true", help="Désactiver tous les filtres de contenu")
    parser.add_argument("--db-host", default="localhost", help="Hôte de la base de données (par défaut: localhost)")
    parser.add_argument("--db-user", default="root", help="Utilisateur de la base de données (par défaut: root)")
    parser.add_argument("--db-password", default="", help="Mot de passe de la base de données (par défaut: vide)")
    parser.add_argument("--db-name", default="mangamuse", help="Nom de la base de données (par défaut: mangamuse)")
    parser.add_argument("--skip-details", action="store_true", help="Ignorer la recherche des détails supplémentaires (créateurs) pour accélérer le traitement")
    parser.add_argument("--batch-size", type=int, default=10, help="Nombre d'éléments à traiter avant une insertion groupée en base de données (par défaut: 10)")
    parser.add_argument("--cache-dir", default="cache", help="Directory to store API responses in cache (default: 'cache')")
    parser.add_argument("--threads", type=int, default=1, help="Nombre de threads pour les requêtes API parallèles (par défaut: 1)")
    parser.add_argument("--log-dir", default="logs", help="Répertoire pour stocker les fichiers de logs (par défaut: 'logs')")
    parser.add_argument("--verbose", action="store_true", help="Activer les logs détaillés")
    
    return parser.parse_args()

# Classe pour gérer le cache des requêtes API
class ApiCache:
    def __init__(self, cache_dir="cache", logger=None):
        self.cache_dir = cache_dir
        self.logger = logger
        os.makedirs(cache_dir, exist_ok=True)
        
    def get_cache_key(self, url):
        return hashlib.md5(url.encode()).hexdigest()
        
    def get_from_cache(self, url):
        cache_key = self.get_cache_key(url)
        cache_file = os.path.join(self.cache_dir, f"{cache_key}.json")
        
        if os.path.exists(cache_file):
            # Vérifier si le cache est plus récent que 24 heures
            file_time = os.path.getmtime(cache_file)
            if time.time() - file_time < 86400:  # 24 heures en secondes
                try:
                    with open(cache_file, 'r', encoding='utf-8') as f:
                        return json.load(f)
                except Exception:
                    return None
        return None
        
    def save_to_cache(self, url, data):
        cache_key = self.get_cache_key(url)
        cache_file = os.path.join(self.cache_dir, f"{cache_key}.json")
        
        try:
            with open(cache_file, 'w', encoding='utf-8') as f:
                json.dump(data, f)
        except Exception as e:
            if self.logger:
                self.logger.log(f"⚠️ Unable to save to cache: {e}", logging.WARNING)
            else:
                print(f"⚠️ Unable to save to cache: {e}")

# Classe pour gérer les requêtes API avec gestion de la limitation de débit
class ApiClient:
    def __init__(self, cache_handler=None, logger=None):
        self.session = requests.Session()
        self.cache = cache_handler
        self.logger = logger
        self.last_request_time = 0
        self.rate_limit_delay = 1.0  # Délai entre chaque requête en secondes
        
    def get(self, url, use_cache=True):
        # Vérifier si nous avons la réponse en cache
        if use_cache and self.cache:
            cached_data = self.cache.get_from_cache(url)
            if cached_data:
                if self.logger:
                    self.logger.log(f"🔄 Utilisation du cache pour: {url}", logging.INFO)
                else:
                    print(f"🔄 Utilisation du cache pour: {url}")
                return cached_data
                
        # Respecter le délai entre les requêtes
        elapsed = time.time() - self.last_request_time
        if elapsed < self.rate_limit_delay:
            time.sleep(self.rate_limit_delay - elapsed)
            
        # Effectuer la requête
        if self.logger:
            self.logger.log(f"📡 Récupération des données depuis: {url}", logging.INFO)
        else:
            print(f"📡 Récupération des données depuis: {url}")
        
        response = self.session.get(url)
        self.last_request_time = time.time()
        
        if not response.ok:
            raise Exception(f"Erreur lors de la récupération des données API. Code: {response.status_code}")
            
        data = response.json()
        
        # Sauvegarder dans le cache
        if use_cache and self.cache:
            self.cache.save_to_cache(url, data)
            
        return data

# Fonction pour connecter à la base de données
def connect_to_database(args):
    try:
        conn = mysql.connector.connect(
            host=args.db_host,
            user=args.db_user,
            password=args.db_password,
            database=args.db_name
        )
        print(f"✅ Connexion à la base de données '{args.db_name}' établie avec succès")
        return conn
    except mysql.connector.Error as err:
        print(f"❌ Erreur de connexion à la base de données: {err}")
        sys.exit(1)

# Fonction pour afficher les dernières lignes d'un fichier de logs
def display_recent_logs(log_file, lines=20):
    """Affiche les dernières lignes d'un fichier de logs"""
    if not os.path.exists(log_file):
        print(f"Le fichier de logs {log_file} n'existe pas.")
        return
    
    try:
        with open(log_file, 'r', encoding='utf-8') as f:
            # Lire toutes les lignes et prendre les dernières
            all_lines = f.readlines()
            recent_lines = all_lines[-lines:] if len(all_lines) > lines else all_lines
            
            for line in recent_lines:
                print(line.strip())
    except Exception as e:
        print(f"Erreur lors de la lecture du fichier de logs: {e}")

# Fonction pour obtenir le dernier fichier de logs créé
def get_latest_log_file(log_dir="logs"):
    """Retourne le chemin du fichier de logs le plus récent"""
    if not os.path.exists(log_dir):
        return None
    
    log_files = [os.path.join(log_dir, f) for f in os.listdir(log_dir) if f.startswith("import_") and f.endswith(".log")]
    
    if not log_files:
        return None
    
    # Trier par date de modification (la plus récente en premier)
    log_files.sort(key=os.path.getmtime, reverse=True)
    return log_files[0]

# Fonction pour récupérer les IDs d'anime existants dans la base de données
def get_existing_anime_ids(conn):
    cursor = conn.cursor()
    cursor.execute("SELECT id FROM pages")
    result = cursor.fetchall()
    existing_ids = {row[0] for row in result}
    cursor.close()
    return existing_ids

# Fonction pour insérer les animes par lots
def batch_insert_animes(conn, animes, logger):
    if not animes:
        return
        
    cursor = conn.cursor()
    
    # Construction de la requête pour insertion multiple
    sql = """
    INSERT INTO pages (id, title, creator, broadcast, genres, episodes, studio, img, category, description, style, background_color, border_color, title_color, label_color, text_color)
    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
    """
    
    # Préparation des données pour l'insertion par lots
    values = [(
        anime['id'],
        anime['title'],
        anime['creator'],
        anime['broadcast'],
        anime['genres'],
        anime['episodes'],
        anime['studio'],
        anime['img'],
        anime['category'],
        anime['synopsis'],
        anime['style'],
        anime['background_color'],
        anime['border_color'],
        anime['title_color'],
        anime['label_color'],
        anime['text_color']
    ) for anime in animes]
    
    # Exécution de l'insertion par lots
    cursor.executemany(sql, values)
    conn.commit()
    cursor.close()
    
    logger.log(f"✅ {len(animes)} animés ont été ajoutés en lot à la base de données.")

# Fonction pour obtenir les détails du créateur d'un anime
def get_anime_creator(api_client, anime_id, skip_details=False, logger=None):
    if skip_details:
        return "Not specified (skipped)"
        
    creator = "Not specified"
    
    # Obtenir les détails complets de l'anime
    try:
        anime_url = f"https://api.jikan.moe/v4/anime/{anime_id}/full"
        anime_data = api_client.get(anime_url)
        
        # Chercher l'adaptation manga
        if "data" in anime_data and "relations" in anime_data["data"]:
            for relation in anime_data["data"]["relations"]:
                if "relation" in relation and relation["relation"] in ["Adaptation", "Source"]:
                    if "entry" in relation:
                        for entry in relation["entry"]:
                            if "type" in entry and entry["type"].lower() in ["manga", "light novel", "novel"]:
                                manga_id = entry["mal_id"]
                                
                                # Récupérer les détails du manga pour trouver l'auteur
                                manga_url = f"https://api.jikan.moe/v4/manga/{manga_id}"
                                manga_data = api_client.get(manga_url)
                                
                                if "data" in manga_data and "authors" in manga_data["data"]:
                                    authors_array = [author["name"] for author in manga_data["data"]["authors"]]
                                    if authors_array:
                                        creator = ", ".join(authors_array)
                                        return creator
        
        # En cas de créateur non trouvé, essayer de chercher dans le personnel
        if creator == "Not specified":
            staff_url = f"https://api.jikan.moe/v4/anime/{anime_id}/staff"
            staff_data = api_client.get(staff_url)
            
            if "data" in staff_data:
                mangaka_array = []
                for staff in staff_data["data"]:
                    if "positions" in staff and isinstance(staff["positions"], list):
                        for position in staff["positions"]:
                            if any(keyword in position.lower() for keyword in ["original", "creator", "story", "mangaka"]):
                                if "person" in staff and "name" in staff["person"]:
                                    mangaka_array.append(staff["person"]["name"])
                
                if mangaka_array:
                    creator = ", ".join(mangaka_array)
                    
    except Exception as e:
        if logger:
            logger.log(f"⚠️ Erreur lors de la récupération des détails du créateur: {e}", logging.WARNING)
        
    return creator

# Fonction pour préparer un anime pour l'insertion
def prepare_anime_data(anime, category, creator, existing_ids):
    id = anime["mal_id"]
    
    # Vérifier si l'anime existe déjà dans la base de données
    if id in existing_ids:
        return None
        
    title = anime.get("title_english", anime["title"])  # Titre anglais ou titre original
    if title is None or title == "":
        title = anime.get("title", "Unknown")
        if title is None or title == "":
            title = "Unknown"
    
    # Extraire les genres
    genres = "Not specified"
    if "genres" in anime and isinstance(anime["genres"], list):
        genres_array = [genre["name"] for genre in anime["genres"]]
        genres = ", ".join(genres_array) if genres_array else "Not specified"
    
    broadcast = anime.get("broadcast", {}).get("string", "Unknown")
    if broadcast is None or broadcast == "":
        broadcast = "Unknown"
        
    episodes = anime.get("episodes", 0)
    if episodes is None or not isinstance(episodes, int) or episodes < 0:
        episodes = 0
        
    img = anime.get("images", {}).get("jpg", {}).get("image_url", "")
    synopsis = anime.get("synopsis", "No description available.")
    if not synopsis:
        synopsis = "No description available."
    
    # Extraire le studio
    studio = "Not specified"
    if "studios" in anime and isinstance(anime["studios"], list):
        studio_array = [studio["name"] for studio in anime["studios"]]
        studio = ", ".join(studio_array) if studio_array else "Not specified"
    
    # Styles CSS pour l'affichage de l'anime
    background_color = "#252525"
    border_color = "#333333"
    title_color = "#ffffff"
    label_color = "#ffffff"
    text_color = "#ffffff"
    
    style = f"""
        .img-infos {{
            display: flex;
            flex-direction: row;
            height: 40%;
            width: 100%;
            padding: 10px 0;
            background-color: {background_color};
        }}
        .img {{
            display: flex;
            justify-content: space-around;
            width: 30%;
            height: 100%;
        }}
        .img > img {{
            height: 100%;
            border-radius: 10px;
        }}
        .infos {{
            width: 70%;
            height: 100%;
            border-left: 1px solid {border_color};
            padding-left: 10px;
        }}
        .infos h2 {{
            color: {title_color};
        }}
        .infos strong {{
            color: {label_color};
        }}
        .infos li {{
            color: {text_color};
        }}
        .description {{
            color: {text_color};
            height: 60%;
            width: 100%;
            padding: 10px 0;
            background-color: {background_color};
        }}
    """
    
    return {
        'id': id,
        'title': title,
        'creator': creator,
        'broadcast': broadcast,
        'genres': genres,
        'episodes': episodes,
        'studio': studio,
        'img': img,
        'synopsis': synopsis,
        'category': category,
        'style': style,
        'background_color': background_color,
        'border_color': border_color,
        'title_color': title_color,
        'label_color': label_color,
        'text_color': text_color
    }

# Fonction pour insérer un anime avec logique de réessai
def process_anime_page(url, category, conn, api_client, existing_ids, logger, filter_adult=True, skip_details=False, batch_size=10):
    max_retries = 3
    attempts = 0
    success = False
    has_data = False
    
    # Liste des genres à filtrer si filter_adult est activé
    adult_genres = ["Ecchi", "Erotica", "Hentai"]
    
    # Tableau pour collecter les animes à insérer en lot
    anime_batch = []
    
    # Récupérer les IDs existants avant chaque page pour éviter les doublons
    existing_ids_set = get_existing_anime_ids(conn)

    while attempts < max_retries and not success:
        attempts += 1

        try:
            # Récupérer les données de la page d'anime
            data = api_client.get(url)
            
            if "data" not in data or not data["data"]:
                raise Exception("Aucune donnée trouvée.")
                
            # Vérifier si nous avons atteint la dernière page
            if len(data["data"]) == 0:
                logger.log("🏁 Dernière page atteinte, aucun anime supplémentaire disponible.", logging.INFO)
                return {'success': True, 'continue_next': False, 'has_data': False}

            has_data = True
            items_processed = 0
            
            # Traiter chaque anime de la page
            for anime in data["data"]:
                id = anime["mal_id"]
                
                # Vérifier si l'anime existe déjà - utiliser le set mis à jour
                if id in existing_ids_set:
                    logger.log(f"⚠️ L'anime ID: {id} existe déjà dans la base de données. Ignorer...", logging.INFO)
                    items_processed += 1
                    continue
                
                title = anime.get("title_english", anime["title"])  # Titre anglais ou titre original
                if title is None or title == "":
                    title = anime.get("title", "Unknown")
                
                # Extraire les genres
                genres = "Not specified"
                genres_array = []
                if "genres" in anime and isinstance(anime["genres"], list):
                    genres_array = [genre["name"] for genre in anime["genres"] if "name" in genre]
                    genres = ", ".join(genres_array) if genres_array else "Not specified"
                
                # Vérifier si l'anime doit être filtré sur la base des genres pour adultes
                if filter_adult and any(adult_genre in genres for adult_genre in adult_genres):
                    logger.log(f"⛔ L'anime '{title}' ignoré en raison du contenu pour adultes: {genres}", logging.INFO)
                    items_processed += 1
                    continue
                
                # Obtenir le créateur de l'anime
                creator = get_anime_creator(api_client, id, skip_details, logger)
                
                # Préparer les données pour l'insertion
                anime_data = prepare_anime_data(anime, category, creator, existing_ids_set)
                
                if anime_data:
                    # Ajouter l'ID à notre ensemble pour éviter les doublons même au sein du même lot
                    existing_ids_set.add(id)
                    
                    anime_batch.append(anime_data)
                    logger.log(f"🔹 L'anime '{title}' préparé pour l'insertion.", logging.INFO)
                    
                    # Si nous avons atteint la taille du lot, insérer les animes
                    if len(anime_batch) >= batch_size:
                        try:
                            batch_insert_animes(conn, anime_batch, logger)
                            anime_batch = []
                        except mysql.connector.Error as err:
                            if err.errno == 1062:  # Code d'erreur pour duplicate key
                                logger.log(f"⚠️ Erreur d'insertion en lot: {err}", logging.WARNING)
                                # Insérer un par un pour identifier les problèmes
                                for a in anime_batch:
                                    try:
                                        batch_insert_animes(conn, [a], logger)
                                    except mysql.connector.Error as err2:
                                        logger.log(f"⚠️ Impossible d'insérer l'anime ID {a['id']}: {err2}", logging.ERROR)
                                anime_batch = []
                            else:
                                raise
                
                items_processed += 1
            
            # Insérer les animes restants
            if anime_batch:
                try:
                    batch_insert_animes(conn, anime_batch, logger)
                except mysql.connector.Error as err:
                    if err.errno == 1062:  # Code d'erreur pour duplicate key
                        logger.log(f"⚠️ Erreur d'insertion en lot: {err}", logging.WARNING)
                        # Insérer un par un pour identifier les problèmes
                        for a in anime_batch:
                            try:
                                batch_insert_animes(conn, [a], logger)
                            except mysql.connector.Error as err2:
                                logger.log(f"⚠️ Impossible d'insérer l'anime ID {a['id']}: {err2}", logging.ERROR)
                    else:
                        raise
            
            # Si on a traité tous les items sans erreur, marquer comme un succès
            if items_processed > 0:
                success = True
                return {'success': True, 'continue_next': True, 'has_data': True}
            else:
                logger.log("🏁 Aucun anime à traiter dans cette page.", logging.INFO)
                return {'success': True, 'continue_next': False, 'has_data': False}

        except Exception as e:
            logger.log(f"❌ Tentative {attempts} échouée: {e}", logging.ERROR)
            # Attendre avant de réessayer
            time.sleep(5)

    # Même après 3 tentatives échouées, on continue avec la page suivante
    logger.log(f"⚠️ Échec du traitement de la page après {max_retries} tentatives. Passage à la page suivante...", logging.WARNING)
    return {'success': False, 'continue_next': True, 'has_data': has_data}

def process_page(page, conn, api_client, existing_ids, logger, filter_adult=True, skip_details=False, batch_size=10):
    logger.log(f"\n🔄 Traitement de la page {page}", logging.INFO)
    
    # Variable pour suivre si on peut continuer avec la page suivante
    continue_next_page = True
    
    # Dictionnaire pour suivre les échecs consécutifs par catégorie
    category_failures = {
        "trending": 0,
        "upcoming": 0,
        "general": 0
    }
    
    # 🔥 Récupérer les anime tendance
    if continue_next_page:
        logger.log("\n🔥 Catégorie: TRENDING", logging.INFO)
        result = process_anime_page(
            f"https://api.jikan.moe/v4/top/anime?page={page}",
            "trending",
            conn,
            api_client,
            existing_ids,
            logger,
            filter_adult,
            skip_details,
            batch_size
        )
        if not result['success'] and result['has_data']:
            category_failures["trending"] += 1
        continue_next_page = result['continue_next']
    
    # 📅 Récupérer les anime à venir
    if continue_next_page and category_failures["upcoming"] < 3:
        logger.log("\n📅 Catégorie: UPCOMING", logging.INFO)
        result = process_anime_page(
            f"https://api.jikan.moe/v4/seasons/upcoming?page={page}",
            "upcoming",
            conn,
            api_client,
            existing_ids,
            logger,
            filter_adult,
            skip_details,
            batch_size
        )
        if not result['success'] and result['has_data']:
            category_failures["upcoming"] += 1
            if category_failures["upcoming"] >= 3:
                logger.log("\n⚠️ La catégorie UPCOMING a échoué 3 fois consécutives. Cette catégorie sera ignorée pour les pages suivantes.", logging.WARNING)
        continue_next_page = result['continue_next']
    
    # 🔍 Récupérer les anime généraux (non catégorisés)
    if continue_next_page:
        logger.log("\n🔍 Catégorie: GENERAL", logging.INFO)
        result = process_anime_page(
            f"https://api.jikan.moe/v4/anime?page={page}",
            "none",
            conn,
            api_client,
            existing_ids,
            logger,
            filter_adult,
            skip_details,
            batch_size
        )
        if not result['success'] and result['has_data']:
            category_failures["general"] += 1
        continue_next_page = result['continue_next']
    
    return continue_next_page

def main():
    # Analyser les arguments
    args = parse_arguments()
    
    # Initialiser le logger
    log_level = logging.DEBUG if args.verbose else logging.INFO
    logger = Logger(log_dir=args.log_dir, log_level=log_level)
    
    # Timestamp pour mesurer la durée d'exécution
    start_time = time.time()
    
    # Gérer les paramètres contradictoires
    if args.filter_adult and args.no_filter:
        logger.log("⚠️ Options contradictoires: --filter-adult et --no-filter. Le filtre adulte sera désactivé.", logging.WARNING)
        args.filter_adult = False
    
    # Établir la connexion à la base de données
    conn = connect_to_database(args)
    
    # Récupérer les IDs des animes existants dans la base de données
    existing_ids = get_existing_anime_ids(conn)
    logger.log(f"ℹ️ {len(existing_ids)} animés déjà présents dans la base de données.", logging.INFO)
    
    # Initialiser le gestionnaire de cache
    cache = ApiCache(args.cache_dir, logger)
    
    # Initialiser le client API
    api_client = ApiClient(cache, logger)
    
    # Définir la plage de pages
    start_page = args.start
    end_page = args.end
    
    # Afficher la configuration
    logger.log("\n📋 CONFIGURATION:", logging.INFO)
    logger.log(f"  - Page de départ: {start_page}", logging.INFO)
    logger.log(f"  - Page de fin: {('Non spécifiée (va jusqu\'à la fin)' if end_page is None else end_page)}", logging.INFO)
    logger.log(f"  - Filtre contenu adulte: {('Activé' if args.filter_adult else 'Désactivé')}", logging.INFO)
    logger.log(f"  - Recherche détaillée des créateurs: {('Désactivée' if args.skip_details else 'Activée')}", logging.INFO)
    logger.log(f"  - Taille des lots d'insertion: {args.batch_size}", logging.INFO)
    logger.log(f"  - Cache des requêtes API: {args.cache_dir}", logging.INFO)
    logger.log("\n🚀 Démarrage de l'ajout des animés...\n", logging.INFO)
    
    # Compteurs pour les statistiques
    page_count = 0
    current_page = start_page
    
    try:
        # Traiter les pages jusqu'à la fin ou jusqu'à la page spécifiée
        while True:
            # Traiter la page actuelle
            continue_next = process_page(
                current_page,
                conn,
                api_client,
                existing_ids,
                logger,
                args.filter_adult,
                args.skip_details,
                args.batch_size
            )
            page_count += 1
            
            # Mettre à jour les IDs existants pour éviter les doublons
            existing_ids = get_existing_anime_ids(conn)
            
            # Vérifier si on doit s'arrêter
            if not continue_next:
                logger.log(f"\n🏁 Arrêt: pas plus de données disponibles après la page {current_page}", logging.INFO)
                break
                
            if end_page is not None and current_page >= end_page:
                logger.log(f"\n🏁 Arrêt: atteinte de la page finale spécifiée ({end_page})", logging.INFO)
                break
                
            # Passer à la page suivante
            current_page += 1
            
    except KeyboardInterrupt:
        logger.log("\n⚠️ Opération interrompue par l'utilisateur", logging.WARNING)
    except Exception as e:
        logger.log(f"\n❌ Erreur inattendue: {e}", logging.ERROR)
    finally:
        # Calculer la durée d'exécution
        execution_time = time.time() - start_time
        
        # Fermer la connexion à la base de données
        if conn.is_connected():
            conn.close()
            logger.log("\n📊 RÉSUMÉ:", logging.INFO)
            logger.log(f"  - Pages traitées: {page_count}", logging.INFO)
            logger.log(f"  - Dernière page traitée: {current_page}", logging.INFO)
            logger.log(f"  - Durée d'exécution: {execution_time:.2f} secondes", logging.INFO)
            logger.log("\n✅ Connexion à la base de données fermée", logging.INFO)

if __name__ == "__main__":
    main()
