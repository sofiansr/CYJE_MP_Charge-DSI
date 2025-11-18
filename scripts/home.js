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
        fetch('scripts/stats_api.php?action=prospects_total', { credentials: 'same-origin' })
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

    document.addEventListener('DOMContentLoaded', () => {
        fetchTotalProspects();
    });
})();
