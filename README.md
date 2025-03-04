# Test Symfony - Poisson Soluble

Projet réalisé dans le cadre d'un test technique pour le poste de développeur chez Poisson Soluble

## Contraintes techniques
- Langage et version: PHP 8.4
- Framework: Symfony 6.4
- Persistance: [Doelia/sql-migrations-bundle](https://github.com/Doelia/sql-migrations-bundle)
- Base de données: Postegresql

## Installer, configurer et lancer le projet localement
Pour commencer, il suffit de cloner le projet depuis ce dépôt et installer les dépendances:
```{shell}
git clone <URL_DU_REPO>
cd nom-du-projet
composer install
```

Configurer les variables d'environnements (connexion à la base de données):
```{shell}
cp .env .env.local
```
```{shell}
DATABASE_URL="postgresql://<USER>:<PASSWORD@127.0.0.1:5432/test_symfony?serverVersion=16&charset=utf8"
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
X_API_KEY="choisir_une_clé_API_arbitraire"
```
Créer la base de données: 
```{shell}
php bin/console doctrine:database:create
```

Exécuter les migrations avec swouters/sql-migrations-bundle:
```{shell}
php bin/console sql:migrations:status
php bin/console sql:migrations:migrate
```

Configurer Symfony Messenger:
```{shell}
php bin/console messenger:setup-transports
```

Et pour finir, lancer le serveur Symfony

```{shell}
symfony serve -d
```
Pour lancer les tests unitaires, faites:
```{shell}
./vendor/bin/phpunit
```

## La commande app:csv-import

Cette commande permet de charger un fichier CSV en mémoire, le parcourir et en extraire les couples (code INSEE, numéro de téléphone) afin de les persister en base de données. 

Pour utiliser la commande, il suffit de faire appel à la console comme tel:
```{shell}
php bin/console app:csv-import data/test.csv
```

Le code source de l'implémentation de la commande se trouve dans le fichier [src/Command/ImportCsvCommand.php](src/Command/ImportCsvCommand.php)