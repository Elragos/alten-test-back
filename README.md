# alten-test-back

Ceci est le projet d'API backoffice pour mon test d'entrée chez [Alten](https://www.alten.fr/). 
Elle utilise un système d'authentification via JWT.

L'API exploite les technologies suivantes pour fonctionner :

![Static Badge](https://img.shields.io/badge/OpenSSL-3.4.0-green?style=flat)
![Static Badge](https://img.shields.io/badge/PHP-8.4.3-green?style=flat)
![Static Badge](https://img.shields.io/badge/Symfony-7.2.3-green?style=flat)
![Static Badge](https://img.shields.io/badge/lexik_jwt_authentication_bundle-3.1-green?style=flat)

Elle utilise également un système de traductions afin d'avoir une version personnalisée selon la langue désirée 
(langues prises en compte : français, anglais)

## Prérequis

* [Composer >= 2.8.5](https://getcomposer.org/) pour installer le projet
* OpenSSL >= 3.4.0 (requis pour JWT)
* [PHP >= 8.4.0](https://www.php.net/)

## Installation

Récupérer le repo github : 
```
git clone https://github.com/Elragos/alten-test-back.git
```
Dupliquer le .env.example dans .env et mettez le à jour selon votre configuration :
```
cp .env.example .env 
```
Lancer l'installation via composer
```
composer install
```
Générer les clés pour JWT
```
mkdir config/jwt
php bin/console lexik:jwt:generate-keypair
```
Générer la base de données :
* si non créé dans le SGBD
```
php bin/console doctrine:database:create
```
* si déjà créé dans le SGBD
```
php bin/console doctrine:schema:create
```

Ensuite pour rajouter les données par défaut :
```
php bin/console doctrine:fixtures:load
```
Cela va créer 2 comptes utilisateurs avec le mdp 123456 :
* admin@admin.com &rarr; compte admin
* test@test.com &rarr; compte utilisateur
Cela va aussi créer 3 produits pour tester les 3 types de stocks :
* Test code &rarr; INSTOCK
* Test code 2 &rarr; LOWSTOCK
* Test code 3 &rarr; OUTOFSTOCK

## Exploiter l'API

Utiliser le fichier Postman pour avoir toutes les URL disponibles pour communiquer avec l'API

## Tester l'API

Pour lancer les tests de l'API, il faut d'abord créer et alimenter la BDD de test :
```
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:create --env=test
php bin/console doctrine:fixtures:load --env=test
```

Ensuite on peut lancer les tests via la commande suivante :
```
php bin/phpunit
```