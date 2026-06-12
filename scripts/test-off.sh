#!/bin/bash
set -e

COMPOSE_FILE="compose.override.yaml"
ENTRYPOINT_FILE="frankenphp/docker-entrypoint.sh"

# Check if already in dev mode
if grep -q '# APP_ENV: "${APP_ENV:-dev}"' "$COMPOSE_FILE"; then
    echo "[test-off] Déjà en mode dev."
else
    python3 - << 'PYEOF'
import sys

# compose.override.yaml : revenir au commentaire dev
f = "compose.override.yaml"
c = open(f).read()
if 'APP_ENV: "test"' in c:
    c = c.replace('APP_ENV: "test"', '# APP_ENV: "${APP_ENV:-dev}"')
    open(f, "w").write(c)
    print('[test-off] compose.override.yaml → # APP_ENV: "${APP_ENV:-dev}"')
else:
    print("[test-off] WARN: APP_ENV: \"test\" non trouvé dans compose.override.yaml", file=sys.stderr)
    sys.exit(1)

# docker-entrypoint.sh : retirer le guard APP_ENV != test
f = "frankenphp/docker-entrypoint.sh"
c = open(f).read()
old = 'if [ "$APP_ENV" != "test" ] && [ "$(find ./migrations -iname'
new = 'if [ "$(find ./migrations -iname'
if old in c:
    c = c.replace(old, new)
    open(f, "w").write(c)
    print("[test-off] docker-entrypoint.sh → guard migrations retiré")
else:
    print("[test-off] WARN: guard APP_ENV non trouvé dans docker-entrypoint.sh", file=sys.stderr)
    sys.exit(1)
PYEOF
fi

echo "[test-off] Redémarrage des conteneurs..."
docker compose up --detach --wait

echo "[test-off] Retour en mode dev."
