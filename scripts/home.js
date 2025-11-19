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

    function fetchChaleurDistribution() {
        const cvs = document.getElementById('chart-chaleur');
        if (!cvs) return;
        fetch('scripts/dashboard_api.php?action=chaleur_distribution', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(json => {
                if (!json || !json.success) return;
                const dist = json.distribution || { Froid:0, Tiède:0, Chaud:0 };
                const total = json.total || 0;
                const dataVals = [dist['Froid'], dist['Tiède'], dist['Chaud']];
                const labels = ['Froid','Tiède','Chaud'];
                const bgColors = ['#38bdf8','#fb923c','#f87171'];
                if (window.Chart) {
                    new Chart(cvs, {
                        type: 'doughnut',
                        data: {
                            labels,
                            datasets: [{
                                data: dataVals,
                                backgroundColor: bgColors,
                                borderWidth: 0,
                                hoverOffset: 6
                            }]
                        },
                        options: {
                            plugins: {
                                legend: { position: 'bottom', labels:{ font:{ family: 'Barlow Semi Condensed' } } },
                                tooltip: {
                                    callbacks: {
                                        label: ctx => {
                                            const val = ctx.parsed; const pct = total>0 ? ((val/total)*100).toFixed(1) : '0.0';
                                            return `${ctx.label}: ${val} (${pct}%)`;
                                        }
                                    }
                                }
                            },
                            cutout: '55%'
                        }
                    });
                }
            })
            .catch(() => {});
    }

    function fetchTPCDistribution() {
        const cvs = document.getElementById('chart-tpc');
        if (!cvs) return;
        fetch('scripts/dashboard_api.php?action=tpc_distribution', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(json => {
                if (!json || !json.success) return;
                const labels = json.labels || [];
                const counts = json.counts || [];
                const total = json.total || 0;
                if (window.Chart) {
                    new Chart(cvs, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Nombre',
                                data: counts,
                                backgroundColor: '#0d47a1',
                                borderRadius: 6,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: ctx => {
                                            const val = ctx.parsed.y;
                                            const pct = total>0 ? ((val/total)*100).toFixed(1) : '0.0';
                                            return `${val} (${pct}%)`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    ticks: { font: { family: 'Barlow Semi Condensed' } },
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: { stepSize: 1 },
                                }
                            }
                        }
                    });
                }
            })
            .catch(() => {});
    }

    function fetchOffreDistribution() {
        // Récupère la balise <canvas> qui accueillera le graphique (camembert des offres)
        const cvs = document.getElementById('chart-offre');
        // Si le canvas n'existe pas dans le DOM, on quitte sans faire la requête
        if (!cvs) return;
        // Appel de l'endpoint backend pour obtenir la distribution des offres
        fetch('scripts/dashboard_api.php?action=offre_distribution', { credentials: 'same-origin' })
            // Convertit la réponse HTTP en JSON
            .then(r => r.json())
            // Traite le JSON retourné
            .then(json => {
                // Vérifie la structure attendue (succès). Si échec, on ne trace pas de graphique
                if (!json || !json.success) return;
                // Objet de distribution retourné (clef = offre, valeur = nombre); on fournit des valeurs par défaut si absent
                const dist = json.distribution || { Informatique:0, Chimie:0, Biotechnologies:0, 'Génie civil':0 };
                // Total des entrées (utilisé pour calculer les pourcentages dans les tooltips)
                const total = json.total || 0;
                // Ordre fixe des libellés pour garantir stabilité visuelle du graphique
                const labels = ['Informatique','Chimie','Biotechnologies','Génie civil'];
                // Tableau des valeurs (compte par offre) en respectant l'ordre des labels
                const dataVals = labels.map(l => dist[l] || 0);
                // Palette de couleurs
                const colors = ['#0d47a1','#10b981','#8b5cf6','#f59e0b'];
                // Vérifie que Chart.js est bien chargé
                if (window.Chart) {
                    // Instancie un nouveau graphique Chart.js associé au canvas
                    new Chart(cvs, {
                        // type: détermine le type de graphique
                        type: 'doughnut',
                        // data: ensemble des données et libellés utilisés par le rendu
                        data: {
                            // labels: tableau de chaînes affichées dans la légende et associées aux segments
                            labels,
                            // datasets: liste de jeux de données
                            datasets: [{
                                // data: valeurs numériques pour chaque segment (correspond à labels par index)
                                data: dataVals,
                                // backgroundColor: couleur de remplissage des segments
                                backgroundColor: colors,
                                // borderWidth: épaisseur de la bordure
                                borderWidth: 0,
                                // hoverOffset: déplacement du segment au survol pour feedback visuel
                                hoverOffset: 6
                            }]
                        },
                        // options: configuration fine (plugins, interaction, apparence)
                        options: {
                            // plugins: réglages spécifiques aux plugins intégrés (légende, tooltip, etc.)
                            plugins: {
                                // legend: configuration de la légende du graphique
                                legend: {
                                    // position: placement de la legende
                                    position:'bottom',
                                    // labels: style des libellés de légende
                                    labels:{
                                        // font: personnalisation de la police 
                                        font:{ family:'Barlow Semi Condensed' }
                                    }
                                },
                                // tooltip: info-bulle affichée au survol d'un segment
                                tooltip: {
                                    // callbacks: fonctions permettant de personnaliser le contenu des tooltips
                                    callbacks: {
                                        // label: retourne le texte affiché pour un segment survolé
                                        label: ctx => {
                                            // ctx.parsed = valeur numérique du segment
                                            const val = ctx.parsed;
                                            // Calcul pourcentage (val/total * 100) avec protection si total=0
                                            const pct = total>0 ? ((val/total)*100).toFixed(1) : '0.0';
                                            // Chaîne finale: "Libellé: valeur (xx.x%)"
                                            return `${ctx.label}: ${val} (${pct}%)`;
                                        }
                                    }
                                }
                            },
                            // cutout: taille du trou central (exprimé en pourcentage ou pixels) => style "donut"
                            cutout: '15%'
                        }
                    });
                }
            })
            // Gestion silencieuse des erreurs réseau/JSON (on ne bloque pas l'affichage global du dashboard)
            .catch(() => {});
    }

    function fetchConversionRate() {
        const el = document.getElementById('conversion-rate-value');
        if (!el) return;
        fetch('scripts/dashboard_api.php?action=conversion_rate', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(json => {
                if (json && json.success) {
                    el.textContent = json.rate_percent + '%';
                    el.title = `Signés: ${json.signed_prospects} / Total: ${json.total_prospects}`;
                } else {
                    el.textContent = '0%';
                }
            })
            .catch(() => { if (el) el.textContent = '0%'; });
    }

    document.addEventListener('DOMContentLoaded', () => {
        fetchTotalProspects();
        fetchProspectsContactesMois();
        fetchProspectsContacteParUser();
        fetchChaleurDistribution();
        fetchTPCDistribution();
        fetchOffreDistribution();
        fetchConversionRate();
    });
})();
