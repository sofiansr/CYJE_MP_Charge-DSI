(function () {
    'use strict';

    function renderTotalProspects(total) {
        const el = document.getElementById('total-prospects-value');
        if (el) {
            el.textContent = total.toString();
        }
        // Crée un simple doughnut Chart.js pour illustrer l'utilisation de la lib
        const ctx = document.getElementById('chart-total-prospects');
        if (ctx && window.Chart) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Prospects'],
                    datasets: [{
                        data: [total],
                        backgroundColor: ['#0d47a1'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    },
                    cutout: '70%'
                }
            });
        }
    }

    function fetchTotalProspects() {
        fetch('scripts/dashboard_api.php?action=prospects_total', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(json => {
                if (json && json.success) {
                    renderTotalProspects(json.total || 0);
                } else {
                    renderTotalProspects(0);
                }
            })
            .catch(() => renderTotalProspects(0));
    }

    function fetchProspectsContactesMois() {
        fetch('scripts/dashboard_api.php?action=prospects_contactes_mois', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(json => {
                const el = document.getElementById('prospects-contactes-mois-value');
                if (el) {
                    el.textContent = (json && json.success) ? (json.total || 0) : '0';
                }
            })
            .catch(() => {
                const el = document.getElementById('prospects-contactes-mois-value');
                if (el) el.textContent = '0';
            });
    }

    function fetchProspectsContacteParUser() {
        fetch('scripts/dashboard_api.php?action=prospects_contacte_par_user', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(json => {
                const el = document.getElementById('prospects-contacte-par-user-value');
                if (el) {
                    if (json && json.success) {
                        el.textContent = json.value.toString();
                        el.title = `Prospects contacté: ${json.prospects_contacte} / Utilisateurs: ${json.users_total}`;
                    } else {
                        el.textContent = '0';
                    }
                }
            })
            .catch(() => {
                const el = document.getElementById('prospects-contacte-par-user-value');
                if (el) el.textContent = '0';
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        fetchTotalProspects();
        fetchProspectsContactesMois();
        fetchProspectsContacteParUser();
    });
})();
