# Deploiement en production

## Stack technique

| Composant       | Version / Detail                        |
|-----------------|-----------------------------------------|
| Symfony         | 7.4                                     |
| PHP             | 8.4                                     |
| FrankenPHP      | 1.x                                     |
| Caddy           | Embarque dans FrankenPHP                |
| Base de donnees | MySQL 8                                 |
| Mercure         | Integre via Caddy                       |

---

## Commandes de demarrage (prod vs dev)

Le projet utilise trois fichiers Compose :

- `compose.yaml` : la base (services `php` et `database`). Le service `php` n'y a aucune cible de build.
- `compose.override.yaml` : surcharges et outils **uniquement dev** (`mailer`/Mailpit, `phpmyadmin`, `selenium`), build `frankenphp_dev`.
- `compose.prod.yaml` : surcharge **production** : build `frankenphp_prod` + secrets (`APP_SECRET`, cles Mercure).

Docker Compose fusionne automatiquement `compose.override.yaml` quand il est present. En production il faut donc charger explicitement `compose.prod.yaml` a la place de l'override.

### Production

```bash
docker compose -f compose.yaml -f compose.prod.yaml up -d --build
```

Cette commande ignore `compose.override.yaml` et applique la surcharge prod : seuls `php` (build `frankenphp_prod`) et `database` sont demarres. Sans `-f compose.prod.yaml`, le service `php` n'a aucune cible de build et l'image de production ne peut pas etre construite.

### Developpement

```bash
docker compose up -d
```

Sans option `-f`, Compose fusionne `compose.yaml` + `compose.override.yaml` : on obtient `php` (build `frankenphp_dev` + Xdebug), `database`, `mailer`, `phpmyadmin` et `selenium`.

> **Attention Makefile** : les cibles `make start`, `make build` et `make deploy` utilisent `docker compose` sans option `-f`. Telles quelles, elles fusionnent l'override dev et n'appliquent pas `compose.prod.yaml`. Pour la prod, utiliser la commande explicite ci-dessus ou adapter le Makefile (`DOCKER_COMP = docker compose -f compose.yaml -f compose.prod.yaml`).

---

## Prerequis serveur

Le serveur doit avoir Git, Docker (avec Docker Compose v2), Make et un acces SSH. Les ports 80 et 443 (TCP et UDP) doivent etre ouverts, avec un nom de domaine pointant vers le serveur. Si le serveur est sur un reseau prive, un acces VPN est necessaire pour le runner GitHub Actions.

```bash
git --version
docker --version
docker compose version
make --version
```

---

## Premier deploiement

### 1. Recuperation du projet

```bash
git clone <url-du-depot> livret-alternant
cd livret-alternant
```

### 2. Variables d'environnement

Creer un fichier `.env.local` a la racine :

```bash
APP_ENV=prod
APP_DEBUG=false
APP_SECRET=<generer-avec-openssl-rand-hex-32>
DATABASE_URL=mysql://<USER>:<PASSWORD>@database:3306/<DB_NAME>?serverVersion=8&charset=utf8mb4
SERVER_NAME=<votre-domaine.fr>
MAILER_DSN=smtp://<host-smtp>:<port>
CORS_ALLOW_ORIGIN='^https?://<votre-domaine\.fr>$'

MYSQL_DATABASE=<DB_NAME>
MYSQL_USER=<USER>
MYSQL_PASSWORD=<PASSWORD>
```

### 3. Build et lancement

```bash
make build
make start
```

Le Dockerfile cible le stage `frankenphp` via `compose.yaml`. Ce stage :
- Installe les dependances Composer sans les packages de dev
- Compile `.env.local.php` via `composer dump-env prod`

Les migrations sont executees automatiquement au demarrage via `docker-entrypoint.sh`.

---

## Deploiement automatise (GitHub Actions)

Le deploiement est declenche automatiquement a chaque merge sur la branche `prod`, apres validation du CI.

### Flux complet

```
push/PR sur dev  →  CI (build + tests)  →  merge sur prod  →  CI prod  →  deploiement
```

### Fonctionnement du workflow

1. Le CI (`ci.yml`) se declenche sur `dev` et `prod`
2. Le workflow de deploiement (`deployment.yml`) se declenche uniquement quand le CI de `prod` passe
3. Il se connecte au serveur via VPN (OpenVPN) puis SSH
4. Il execute `git pull origin prod` puis `make deploy`

### Secrets GitHub requis

| Secret            | Description                              |
|-------------------|------------------------------------------|
| `VPN_CONFIG`      | Contenu du fichier `.ovpn`               |
| `VPN_USER`        | Identifiant VPN                          |
| `VPN_PASSWORD`    | Mot de passe VPN                         |
| `SSH_HOST`        | Adresse IP ou hostname du serveur        |
| `SSH_USER`        | Utilisateur SSH                          |
| `SSH_PASSWORD`    | Mot de passe SSH                         |
| `SSH_APP_PATH`    | Chemin absolu du projet sur le serveur   |
| `APP_SECRET`      | Cle secrete Symfony                      |
| `JWT_PASSPHRASE`  | Passphrase des cles JWT                  |

### Variables GitHub requises

| Variable               | Description               |
|------------------------|---------------------------|
| `SENDER_EMAIL_ADDRESS` | Adresse email d'envoi     |

---

## Migrations de base de donnees

Les migrations sont executees automatiquement au demarrage du container. Le script `docker-entrypoint.sh` :

1. Attend que la base de donnees soit accessible (timeout 60 secondes)
2. Execute `doctrine:migrations:migrate`

Pour executer manuellement :

```bash
docker compose exec php php bin/console doctrine:migrations:migrate
```

---

## TLS / HTTPS

En production, un reverse proxy Nginx se charge de la terminaison TLS. Le container Caddy/FrankenPHP sert uniquement en HTTP.

Adapter le Caddyfile (`frankenphp/Caddyfile`) avec le domaine reel :

```caddyfile
http://votre-domaine.fr {
    root * /app/public
    # ...
}
```

---

## Volumes persistants

| Volume          | Contenu                                  |
|-----------------|------------------------------------------|
| `caddy_data`    | Certificats TLS et donnees Caddy         |
| `caddy_config`  | Configuration runtime Caddy              |
| `database_data` | Donnees MySQL                            |
| `public/uploads`| Fichiers uploades (calendriers classes)  |
