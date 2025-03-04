# Test Symfony - Poisson Soluble

Projet réalisé dans le cadre d'un test technique pour le poste de développeur chez Poisson Soluble

## Contraintes techniques
- Langage et version: PHP 8.4
- Framework: Symfony 6.4
- Persistance: [Doelia/sql-migrations-bundle](https://github.com/Doelia/sql-migrations-bundle)
- Base de données: Postegresql

## Installer, configurer et lancer le projet localement

### 📌 Cloner et installer les dépendances
```{shell}
git clone <URL_DU_REPO>
cd nom-du-projet
composer install
```

### 📌 Configurer la base de données
```{shell}
DATABASE_URL="postgresql://<USER>:<PASSWORD@127.0.0.1:5432/<DATABASE>?serverVersion=16&charset=utf8"
```

### 📌 Base de données, migrations et Symfony Messenger

```{shell}
# Création de la base de données
php bin/console doctrine:database:create

# Migrations
php bin/console sql:migrations:status
php bin/console sql:migrations:migrate

# Configuration de Symfony Messenger
php bin/console messenger:setup-transports
```


### 📌 Lancer le serveur en local

```{shell}
symfony serve -d
```

### 📌 Les tests unitaires

```{shell}
./vendor/bin/phpunit
```

## La commande app:csv-import

Cette commande permet de charger un **fichier CSV** en mémoire, le **parcourir** et en **extraire** les couples (code `INSEE`, `numéro de téléphone`) afin de les **persister** en base de données.

| Paramètre  | Type   | Obligatoire | Description |
|------------|--------|-------------|--------------|
| `filePath`    | string | ✅ Oui       | Chemin relatif au fichier CSV |


### 📌 Utilisation

Par exemple, pour mon fichier [data/test.csv](data/test.csv), je lance la commande suivante:
```{shell}
php bin/console app:csv-import data/test.csv
```

Le code source de l'implémentation de la commande se trouve dans le fichier [src/Command/ImportCsvCommand.php](src/Command/ImportCsvCommand.php)

## Route `/alerter`

Cette route permet d'envoyer une **alerte météo** par SMS aux destinataires associés à un **code INSEE**.  
Pour cela, effectuez une requête **POST** vers `/alerter` avec les paramètres suivants :

| Paramètre  | Type   | Obligatoire | Description |
|------------|--------|-------------|--------------|
| `insee`    | string | ✅ Oui       | Code INSEE permettant de récupérer les numéros de téléphone associés |
| `message`  | string | ✅ Oui       | Message d'alerte météo à envoyer aux destinataires |

### 🔒 Authentification API
L'accès à cette route est **protégé par une clé d'API**, qui doit être incluse dans le **header** de la requête (`X-API-KEY`).

### 📌 Exemple de requête cURL
```shell
curl -X POST "http://127.0.0.1:8000/alerter" \
     -H "Content-Type: application/json" \
     -H "X-API-KEY: 638a58a1-343e-4aa7-89b4-2d133307587f" \
     -d '{"insee": "75006", "message": "Alerte météo !"}'
```

### 🚨 Gestion des erreurs

Les erreurs suivantes sont gérées automatiquement par les Event Listeners :

- **Clé d'API** manquante ou invalide → [src/EventListener/AuthentificationListener.php](src/EventListener/AuthentificationListener.php)
- Paramètre `insee` manquant ou invalide → [src/EventListener/ExceptionListener.php](src/EventListener/ExceptionListener.php)
- Paramètre `message` manquant → [src/EventListener/ExceptionListener.php](src/EventListener/ExceptionListener.php)
- Erreur interne du serveur → [src/EventListener/ExceptionListener.php](src/EventListener/ExceptionListener.php)