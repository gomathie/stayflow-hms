#!/usr/bin/env bash
# Runs the rule tests inside the local app container. Usage: bash tests/php/run.sh
set -euo pipefail
cd "$(dirname "$0")/../.."
export MSYS_NO_PATHCONV=1
docker compose exec -T app rm -rf /tmp/sb-rule-tests
docker compose cp tests/php app:/tmp/sb-rule-tests >/dev/null
docker compose exec -T -u www-data app php /tmp/sb-rule-tests/run.php
