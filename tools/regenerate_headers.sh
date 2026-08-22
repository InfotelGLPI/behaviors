#!/usr/bin/env bash

#
# LICENSE
#
# This file is part of Behaviors plugin for GLPI.
#
# Behaviors is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Behaviors is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with Behaviors. If not, see <http://www.gnu.org/licenses/>.
#
# @author    Infotel, Remi Collet, Nelly Mahu-Lasson
# @copyright Copyright (c) 2018-2026 Behaviors plugin team
# @license   AGPL License 3.0 or (at your option) any later version
# @link      https://github.com/InfotelGLPI/behaviors/
# @link      http://www.glpi-project.org/
# @package   behaviors
# @since     2010
# http://www.gnu.org/licenses/agpl-3.0-standalone.html
# --------------------------------------------------------------------------
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"
HEADER_FILE="$SCRIPT_DIR/HEADER"

if [[ ! -f "$HEADER_FILE" ]]; then
    echo "Error: header file not found: $HEADER_FILE"
    exit 1
fi

# Single raw header file for every language (PHP + Twig), mirroring glpi/tools.
php "$SCRIPT_DIR/regenerate_headers.php" "$PLUGIN_DIR" "$HEADER_FILE" "$@"
