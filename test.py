import requests
import json

# Les données du jeu à envoyer
payload = {
  "score": 160000,
  "upgrades": {
    "grandma": {
      "count": 0,
      "price": 10,
      "production": 1,
      "interval": 5
    },
    "farm": {
      "count": 0,
      "price": 50,
      "production": 5,
      "interval": 10
    },
    "factory": {
      "count": 0,
      "price": 100,
      "production": 2,
      "interval": 1
    }
  }
}

# URL du serveur cible
base_url = "http://172.16.20.219/4TTJ/Daszkowski%20Daniel/Site/DanielDaszkowskiSite/site/public"
url = f"{base_url}/php/games/save_score.php"

print("TENTATIVE DE SAUVEGARDE DE SCORE - COOKIE CLICKER")
print("=" * 50)

# Approche 1: Envoi simple avec user_id et game_id
print("\nApproche 1: POST avec user_id et game_id")
try:
    # Cette approche suppose que save_score.php attend un user_id, game_id et les données de score
    data = {
        "user_id": 1,  # Essayez différentes valeurs si nécessaire
        "game_id": "cookie_clicker",  # ou autre identifiant potentiel
        "score": payload["score"],
        "game_data": json.dumps(payload)
    }
    
    response = requests.post(url, data=data)
    print(f"Statut: {response.status_code}")
    print(f"Réponse: {response.text}")
except Exception as e:
    print(f"Erreur: {e}")

# Approche 2: Envoi avec token d'authentification
print("\nApproche 2: POST avec token d'authentification")
try:
    # Certains systèmes utilisent un token dans l'en-tête pour l'authentification
    headers = {
        "Content-Type": "application/json",
        "User-Agent": "Mozilla/5.0",
        "Authorization": "Bearer olivier@gmail.com"  # Utiliser l'email comme token simple
    }
    
    # Ajoutons des identifiants au payload
    data_with_auth = payload.copy()
    data_with_auth["user"] = "olivier@gmail.com"
    
    response = requests.post(url, json=data_with_auth, headers=headers)
    print(f"Statut: {response.status_code}")
    print(f"Réponse: {response.text}")
except Exception as e:
    print(f"Erreur: {e}")

# Approche 3: Simuler un formulaire comme dans un navigateur
print("\nApproche 3: Simulation d'un formulaire de navigateur")
try:
    # Cette approche simule ce qu'un formulaire HTML enverrait
    form_data = {
        "email": "olivier@gmail.com",
        "password": "olivier@gmail.com",
        "score": payload["score"],
        "game_data": json.dumps(payload)
    }
    
    headers = {
        "User-Agent": "Mozilla/5.0",
        "Content-Type": "application/x-www-form-urlencoded",
        "Referer": f"{base_url}/cookie_clicker.php"  # Simuler que la requête vient du jeu
    }
    
    response = requests.post(url, data=form_data, headers=headers)
    print(f"Statut: {response.status_code}")
    print(f"Réponse: {response.text}")
except Exception as e:
    print(f"Erreur: {e}")

# Approche 4: Tester avec une session PHP active
print("\nApproche 4: Utilisation d'une session PHP")
try:
    # Créons une session et connectons-nous d'abord
    session = requests.Session()
    
    # Tentative de connexion
    login_url = f"{base_url}/php/auth.php" # ou autre URL de login probable
    login_data = {
        "email": "olivier@gmail.com",
        "password": "olivier@gmail.com",
        "action": "login"
    }
    
    login_response = session.post(login_url, data=login_data)
    print(f"Login Statut: {login_response.status_code}")
    
    # Maintenant envoyons le score avec la session active
    game_data = {
        "score": payload["score"],
        "game_data": json.dumps(payload),
        "game": "cookie_clicker"
    }
    
    save_response = session.post(url, data=game_data)
    print(f"Save Statut: {save_response.status_code}")
    print(f"Save Réponse: {save_response.text}")
except Exception as e:
    print(f"Erreur: {e}")
