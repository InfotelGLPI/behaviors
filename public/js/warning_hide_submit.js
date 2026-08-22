/**
 * LICENSE
 *
 * This file is part of Behaviors plugin for GLPI.
 *
 * Behaviors is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Behaviors is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Behaviors. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Infotel, Remi Collet, Nelly Mahu-Lasson
 * @copyright Copyright (c) 2018-2026 Behaviors plugin team
 * @license   AGPL License 3.0 or (at your option) any later version
 * @link      https://github.com/InfotelGLPI/behaviors/
 * @link      http://www.glpi-project.org/
 * @package   behaviors
 * @since     2010
 * http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * --------------------------------------------------------------------------
 */

/* global MutationObserver */
(function () {
    'use strict';

    var MARKER_ID = 'behaviors-hide-solution-submit';

    function hideSolutionSubmit() {
        var marker = document.getElementById(MARKER_ID);
        if (!marker) {
            return false;
        }
        var submits = document.querySelectorAll('.itilsolution :submit');
        for (var i = 0; i < submits.length; i++) {
            submits[i].style.display = 'none';
        }
        return true;
    }

    function init() {
        if (hideSolutionSubmit()) {
            return;
        }
        if (typeof MutationObserver === 'undefined') {
            return;
        }
        // The solution form can be injected after page load; watch for the
        // marker and stop observing as soon as it is handled.
        var observer = new MutationObserver(function () {
            if (hideSolutionSubmit()) {
                observer.disconnect();
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
