/*
 * Behaviors plugin for GLPI.
 *
 * Hide the "Add solution" submit button when the plugin flags missing mandatory
 * fields. The logic used to live in an inline <script> emitted by
 * warning_hide_submit.html.twig; it is externalised here so no inline script is
 * needed (Content-Security-Policy friendly, per GLPI 11 conventions).
 *
 * The Twig template only emits a hidden marker element. This script is loaded
 * globally through the add_javascript hook (footer), while the ITIL solution
 * form may be injected asynchronously (helpdesk timeline), so we try once on
 * load and otherwise watch the DOM until the marker appears.
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
