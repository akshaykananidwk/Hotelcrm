/* HotelCRM ERP — front-end helpers */
(function () {
    'use strict';

    // Mobile sidebar toggle
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('appSidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function () { sidebar.classList.toggle('open'); });
    }

    // Attach CSRF token to fetch() requests automatically
    var meta = document.querySelector('meta[name="csrf-token"]');
    var csrf = meta ? meta.getAttribute('content') : null;
    window.apiFetch = function (url, options) {
        options = options || {};
        options.headers = Object.assign({ 'X-CSRF-Token': csrf, 'Content-Type': 'application/json' }, options.headers || {});
        return fetch(url, options).then(function (r) { return r.json(); });
    };

    // Confirm dangerous actions
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!window.confirm(el.getAttribute('data-confirm'))) { e.preventDefault(); }
        });
    });

    // Auto-calculate nights on reservation forms
    var ci = document.querySelector('[name="check_in"]');
    var co = document.querySelector('[name="check_out"]');
    var nights = document.getElementById('nightsDisplay');
    function calcNights() {
        if (ci && co && ci.value && co.value && nights) {
            var d = (new Date(co.value) - new Date(ci.value)) / 86400000;
            nights.textContent = d > 0 ? d + ' night(s)' : '';
        }
    }
    if (ci) ci.addEventListener('change', calcNights);
    if (co) co.addEventListener('change', calcNights);
})();
