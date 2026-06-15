#!/bin/bash
set -e

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
COMPOSE_FILE="$PROJECT_ROOT/compose.override.yaml"
ENTRYPOINT_FILE="$PROJECT_ROOT/frankenphp/docker-entrypoint.sh"

# Check if already in test mode
if grep -q 'APP_ENV: "test"' "$COMPOSE_FILE"; then
    echo "[test-on] Déjà en mode test."
else
    python3 - "$PROJECT_ROOT" << 'PYEOF'
import sys

project_root = sys.argv[1]

# compose.override.yaml : activer APP_ENV=test
f = f"{project_root}/compose.override.yaml"
c = open(f).read()
if '# APP_ENV: "${APP_ENV:-dev}"' in c:
    c = c.replace('# APP_ENV: "${APP_ENV:-dev}"', 'APP_ENV: "test"')
    open(f, "w").write(c)
    print("[test-on] compose.override.yaml → APP_ENV: \"test\"")
else:
    print("[test-on] WARN: ligne APP_ENV non trouvée dans compose.override.yaml", file=sys.stderr)
    sys.exit(1)

# docker-entrypoint.sh : ajouter le guard APP_ENV != test pour les migrations
f = f"{project_root}/frankenphp/docker-entrypoint.sh"
c = open(f).read()
old = 'if [ "$(find ./migrations -iname'
new = 'if [ "$APP_ENV" != "test" ] && [ "$(find ./migrations -iname'
if old in c and new not in c:
    c = c.replace(old, new)
    open(f, "w").write(c)
    print("[test-on] docker-entrypoint.sh → guard migrations ajouté")
elif new in c:
    print("[test-on] docker-entrypoint.sh → guard déjà présent")
else:
    print("[test-on] WARN: ligne migrations non trouvée dans docker-entrypoint.sh", file=sys.stderr)
    sys.exit(1)
PYEOF
fi

echo "[test-on] Redémarrage des conteneurs..."
docker compose -f "$PROJECT_ROOT/compose.yaml" -f "$COMPOSE_FILE" up --detach --wait

echo "[test-on] Lancement des tests d'acceptation..."
docker compose -f "$PROJECT_ROOT/compose.yaml" -f "$COMPOSE_FILE" exec php vendor/bin/codecept run Acceptance --steps
