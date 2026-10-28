#!/usr/bin/env bash
# Quita el prefijo /Sistema-del--CEM--JEHOVA-RAFA de todo el runtime,
# para que la app funcione servida en la raiz (/).
#
# Uso (desde la raiz del proyecto):
#   bash scripts/quitar-prefijo-rutas.sh
#
# Es idempotente y no toca codigo de terceros (library, Plugins) ni tests/selenium.
# Pensado para correr DESPUES de cada "git pull"/"git merge".
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PREFIJO='/Sistema-del--CEM--JEHOVA-RAFA'
CHANGED=0

while IFS= read -r -d '' f; do
    if grep -qF -- "$PREFIJO" "$f"; then
        sed -i "s|${PREFIJO}||g" "$f"
        echo " - ${f#"$ROOT"/}"
        CHANGED=$((CHANGED + 1))
    fi
done < <(
    find "$ROOT/src" -type f \( -name '*.js' -o -name '*.php' \) \
        ! -path '*/library/*' ! -path '*/Plugins/*' -print0
    for x in index.php .htaccess webSocket.php; do
        if [ -f "$ROOT/$x" ]; then printf '%s\0' "$ROOT/$x"; fi
    done
)

if [ "$CHANGED" -eq 0 ]; then
    echo "OK: no habia prefijo que quitar."
fi
