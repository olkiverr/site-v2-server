import requests
import mysql.connector
import time
import json

# Configurer la connexion à la base de données
conn = mysql.connector.connect(
    host='localhost',  # Remplacez par votre hôte de base de données
    user='root',       # Remplacez par votre utilisateur MySQL
    password='',  # Remplacez par votre mot de passe
    database='mangamuse'   # Remplacez par votre base de données
)

cursor = conn.cursor()

# Augmenter le délai d'exécution
# En Python, il n'y a pas de limite par défaut comme dans PHP, donc pas besoin de la fonction set_time_limit.

# Fonction pour insérer un anime avec logique de réessai
# Fonction pour insérer un anime avec logique de réessai
def insert_anime(url, category, conn):
    max_retries = 3
    attempts = 0
    success = False

    while attempts < max_retries and not success:
        attempts += 1

        try:
            response = requests.get(url)
            if not response.ok:
                raise Exception("Erreur lors de la récupération des données API.")

            data = response.json()
            if "data" not in data or not data["data"]:
                raise Exception("Aucune donnée trouvée.")

            for anime in data["data"]:
                id = anime["mal_id"]
                title = anime.get("title_english", anime["title"])  # Titre anglais ou titre original
                if title is None or title == "":
                    title = anime.get("title", "Unknown")
                    if title is None or title == "":
                        title = "Unknown"

                broadcast = anime.get("broadcast", {}).get("string", "Unknown")
                # Assurez-vous que broadcast n'est pas None
                if broadcast is None or broadcast == "":
                    broadcast = "Unknown"
                    
                episodes = anime.get("episodes", 0)
                if episodes is None or not isinstance(episodes, int) or episodes < 0:
                    episodes = 0  # ou une autre valeur par défaut
                    
                img = anime.get("images", {}).get("jpg", {}).get("image_url", "")
                synopsis = anime.get("synopsis", "No description available.")
                if not synopsis:
                    synopsis = "No description available."

                # Extraire les genres
                genres = "Not specified"
                if "genres" in anime and isinstance(anime["genres"], list):
                    genres_array = [genre["name"] for genre in anime["genres"]]
                    genres = ", ".join(genres_array) if genres_array else "Not specified"

                # Extraire le studio
                studio = "Not specified"
                if "studios" in anime and isinstance(anime["studios"], list):
                    studio_array = [studio["name"] for studio in anime["studios"]]
                    studio = ", ".join(studio_array) if studio_array else "Not specified"

                # SIMPLIFIÉ : Obtenez le mangaka (créateur) en utilisant l'ID de l'anime pour trouver l'ID du manga
                creator = "Not specified"
                anime_url = f"https://api.jikan.moe/v4/anime/{id}/full"
                anime_response = requests.get(anime_url)

                if anime_response.ok:
                    anime_data = anime_response.json()

                    # Chercher l'adaptation manga
                    if "data" in anime_data and "relations" in anime_data["data"]:
                        for relation in anime_data["data"]["relations"]:
                            if "relation" in relation and relation["relation"] in ["Adaptation", "Source"]:
                                if "entry" in relation:
                                    for entry in relation["entry"]:
                                        if "type" in entry and entry["type"].lower() in ["manga", "light novel", "novel"]:
                                            manga_id = entry["mal_id"]

                                            # Ajouter un délai pour éviter la limitation du débit
                                            time.sleep(2)

                                            # Récupérer les détails du manga pour trouver l'auteur
                                            manga_url = f"https://api.jikan.moe/v4/manga/{manga_id}"
                                            manga_response = requests.get(manga_url)

                                            if manga_response.ok:
                                                manga_data = manga_response.json()

                                                if "data" in manga_data and "authors" in manga_data["data"]:
                                                    authors_array = [author["name"] for author in manga_data["data"]["authors"]]
                                                    if authors_array:
                                                        creator = ", ".join(authors_array)
                                                        break

                # En cas de créateur non trouvé, essayer de chercher dans le personnel
                if creator == "Not specified":
                    time.sleep(1)
                    staff_url = f"https://api.jikan.moe/v4/anime/{id}/staff"
                    staff_response = requests.get(staff_url)

                    if staff_response.ok:
                        staff_data = staff_response.json()

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

                # Vérifier si l'anime existe déjà dans la base de données
                cursor.execute("SELECT id FROM pages WHERE id = %s", (id,))
                if cursor.fetchone():
                    print(f"⚠️ L'anime '{title}' (ID: {id}) existe déjà dans la base de données. Ignorer...")
                else:
                    # Insérer l'anime dans la base de données
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

                    cursor.execute("""
                        INSERT INTO pages (id, title, creator, broadcast, genres, episodes, studio, img, category, description, style, background_color, border_color, title_color, label_color, text_color)
                        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                    """, (id, title, creator, broadcast, genres, episodes, studio, img, category, synopsis, style, background_color, border_color, title_color, label_color, text_color))

                    conn.commit()
                    print(f"✅ L'anime '{title}' a été ajouté à la base de données.")

                # Sortir après avoir traité un anime avec succès
                success = True

        except Exception as e:
            print(f"❌ Tentative {attempts} échouée: {e}")

            # Attendre avant de réessayer (facultatif)
            time.sleep(5)

    if not success:
        print(f"❌ Échec du traitement de l'anime après 3 tentatives. Ignorer...")

def all_anime_insert(page, conn):
    # 🔥 Récupérer les anime tendance
    insert_anime(f"https://api.jikan.moe/v4/top/anime?page={page}", "trending", conn)

    # 📅 Récupérer les anime à venir
    insert_anime(f"https://api.jikan.moe/v4/seasons/upcoming?page={page}", "upcoming", conn)

    # 🔍 Récupérer les anime généraux (non catégorisés)
    insert_anime(f"https://api.jikan.moe/v4/anime?page={page}", "none", conn)


start = 21
end = 30

for i in range(start, end + 1):
    print(f"🔄 Page {i}")
    all_anime_insert(i, conn)
    

# Fermer la connexion à la base de données
cursor.close()
conn.close()
