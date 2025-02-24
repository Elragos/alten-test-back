# alten-test-back

Ceci est le projet d'API backoffice pour mon test d'entrée chez [Alten](https://www.alten.fr/). 
Elle utilise un système d'authentification via JWT.

L'API exploite les technologies suivantes pour fonctionner :

![Static Badge](https://img.shields.io/badge/OpenSSL-3.4.0-green?style=flat)
![Static Badge](https://img.shields.io/badge/PHP-8.4.3-green?style=flat)
![Static Badge](https://img.shields.io/badge/Symfony-7.2.3-green?style=flat)
![Static Badge](https://img.shields.io/badge/lexik_jwt_authentication_bundle-3.1-green?style=flat)

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