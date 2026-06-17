# Production deployment

## Tech stack

| Component       | Version / Detail                        |
|-----------------|-----------------------------------------|
| Symfony         | 7.4                                     |
| PHP             | 8.4                                     |
| FrankenPHP      | 1.x                                     |
| Caddy           | Embedded in FrankenPHP                  |
| Database        | MySQL 8                                 |
| Mercure         | Integrated via Caddy                    |

---

## Startup commands (prod vs dev)

The project relies on two Compose files:

- `compose.yaml`: the base (services `php` and `database`)
- `compose.override.yaml`: overrides and **dev-only** tools (`mailer`/Mailpit, `phpmyadmin`, `selenium`)

Docker Compose automatically merges the override when it is present. It must therefore NOT be loaded in production.

### Production

```bash
docker compose -f compose.yaml up -d --build
```

The `-f compose.yaml` flag forces the use of the base file only and ignores `compose.override.yaml`. Only `php` and `database` are started.

### Development

```bash
docker compose up -d
```

Without the `-f` option, Compose merges `compose.yaml` + `compose.override.yaml`: you get `php` (dev build + Xdebug), `database`, `mailer`, `phpmyadmin` and `selenium`.

> **Makefile warning**: the `make start`, `make build` and `make deploy` targets call `docker compose` without `-f compose.yaml`. As-is, they merge the override and would start the dev services in production. For an isolated prod startup, use the explicit command above or adapt the Makefile (`DOCKER_COMP = docker compose -f compose.yaml`).

---

## Server prerequisites

The server must have Git, Docker (with Docker Compose v2), Make and SSH access. Ports 80 and 443 (TCP and UDP) must be open, with a domain name pointing to the server. If the server is on a private network, VPN access is required for the GitHub Actions runner.

```bash
git --version
docker --version
docker compose version
make --version
```

---

## First deployment

### 1. Fetch the project

```bash
git clone <repository-url> livret-alternant
cd livret-alternant
```

### 2. Environment variables

Create a `.env.local` file at the root:

```bash
APP_ENV=prod
APP_DEBUG=false
APP_SECRET=<generate-with-openssl-rand-hex-32>
DATABASE_URL=mysql://<USER>:<PASSWORD>@database:3306/<DB_NAME>?serverVersion=8&charset=utf8mb4
SERVER_NAME=<your-domain.com>
MAILER_DSN=smtp://<smtp-host>:<port>
CORS_ALLOW_ORIGIN='^https?://<your-domain\.com>$'

MYSQL_DATABASE=<DB_NAME>
MYSQL_USER=<USER>
MYSQL_PASSWORD=<PASSWORD>
```

### 3. Build and launch

```bash
make build
make start
```

The Dockerfile targets the `frankenphp` stage via `compose.yaml`. This stage:
- Installs Composer dependencies without dev packages
- Compiles `.env.local.php` via `composer dump-env prod`

Migrations run automatically at startup via `docker-entrypoint.sh`.

---

## Automated deployment (GitHub Actions)

Deployment is triggered automatically on every merge to the `prod` branch, after the CI passes.

### Full flow

```
push/PR on dev  →  CI (build + tests)  →  merge to prod  →  prod CI  →  deployment
```

### Workflow behaviour

1. The CI (`ci.yml`) runs on `dev` and `prod`
2. The deployment workflow (`deployment.yml`) runs only when the `prod` CI passes
3. It connects to the server over VPN (OpenVPN) then SSH
4. It runs `git pull origin prod` then `make deploy`

### Required GitHub secrets

| Secret            | Description                              |
|-------------------|------------------------------------------|
| `VPN_CONFIG`      | Contents of the `.ovpn` file             |
| `VPN_USER`        | VPN username                             |
| `VPN_PASSWORD`    | VPN password                             |
| `SSH_HOST`        | Server IP address or hostname            |
| `SSH_USER`        | SSH user                                 |
| `SSH_PASSWORD`    | SSH password                             |
| `SSH_APP_PATH`    | Absolute path of the project on server   |
| `APP_SECRET`      | Symfony secret key                       |
| `JWT_PASSPHRASE`  | JWT keys passphrase                      |

### Required GitHub variables

| Variable               | Description               |
|------------------------|---------------------------|
| `SENDER_EMAIL_ADDRESS` | Sender email address      |

---

## Database migrations

Migrations run automatically at container startup. The `docker-entrypoint.sh` script:

1. Waits until the database is reachable (60 second timeout)
2. Runs `doctrine:migrations:migrate`

To run them manually:

```bash
docker compose exec php php bin/console doctrine:migrations:migrate
```

---

## TLS / HTTPS

In production, an Nginx reverse proxy handles TLS termination. The Caddy/FrankenPHP container only serves HTTP.

Adapt the Caddyfile (`frankenphp/Caddyfile`) with the real domain:

```caddyfile
http://your-domain.com {
    root * /app/public
    # ...
}
```

---

## Persistent volumes

| Volume          | Contents                                 |
|-----------------|------------------------------------------|
| `caddy_data`    | TLS certificates and Caddy data          |
| `caddy_config`  | Caddy runtime configuration              |
| `database_data` | MySQL data                               |
| `public/uploads`| Uploaded files (class calendars)         |
