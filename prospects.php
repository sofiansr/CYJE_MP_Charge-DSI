<?php
    // Démarrage de session et protection de la page
    // Si l'utilisateur n'est pas authentifié, on le redirige vers la page de connexion
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: connexion.html');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recherche de prospects</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/prospects.css">
</head>
<body>
    <!-- Barre de navigation (logo, liens des différentes pages) -->
    <nav class="app-nav">
        <div class="nav-inner">
            <div class="nav-left">
                <a href="prospects.php" aria-label="Accueil">
                    <img src="assets/logo_cyje.png" class="nav-logo" alt="Logo CYJE">
                </a>
            </div>
            <div class="nav-center">
                <ul class="nav-links">
                    <li><a href="dashboard.php">Tableau de bord</a></li>
                    <li><a href="prospects.php" class="active">Recherche de prospects</a></li>
                    <li><a href="profile.php">Profil</a></li>
                    <li><a href="scripts/logout.php">Se déconnecter</a></li>
                </ul>
            </div>
            <div class="nav-right"></div>
        </div>
    </nav>
    
    <!-- Contenu principal de la page -->
    <main class="page">
        <h1>Recherche de prospects</h1>
        
        <!-- Barre d'outils: recherche globale + filtre champ/valeur -->
        <section class="card" style="padding:1rem; margin-bottom:1rem;">
            <div class="toolbar">
                <!-- Recherche texte sur plusieurs colonnes côté API -->
                <input id="search" class="search-input" type="search" placeholder="Rechercher... (entreprise, secteur, adresse, ...)">
                <div style="display:flex; gap:.5rem; align-items:center;">
                    <!-- sélecteur de champ de filtre -->
                    <select id="filter-field" class="select" aria-label="Filtrer par">
                        <option value="">Filtrer par...</option>
                        <option value="entreprise">Entreprise</option>
                        <option value="secteur">Secteur</option>
                        <option value="status">Statut</option>
                        <option value="type_acquisition">Type d'acquisition</option>
                        <option value="type_premier_contact">Type 1er contact</option>
                        <option value="chaleur">Chaleur</option>
                        <option value="offre_prestation">Offre prestation</option>
                        <option value="date_premier_contact">Date 1er contact (DD-MM-YYYY)</option>
                    </select>
                    <!-- Saisie de la valeur de filtre -->
                    <input id="filter-value" class="input" type="text" placeholder="Valeur du filtre">
                </div>
                <div style="display:flex; gap:.5rem;">
                    <button id="apply-filter" class="btn btn-primary">Appliquer</button>
                    <button id="reset-filter" class="btn btn-ghost">Réinitialiser</button>
                </div>
            </div>
        </section>
        
        <!-- tableau des prospects + pagination -->
        <section class="card">
            <div class="table-wrapper">
                <table class="table" aria-label="Tableau des prospects">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Entreprise</th>
                            <th>Secteur</th>
                            <th>Status</th>
                            <th>Nom(s)</th>
                            <th>Prénom(s)</th>
                            <th>Email(s)</th>
                            <th>Tel(s)</th>
                            <th>Poste(s)</th>
                            <th>Relancé le</th>
                            <th>Type acquisition</th>
                            <th>Date 1er contact</th>
                            <th>Type 1er contact</th>
                            <th>Chaleur</th>
                            <th>Offre prestation</th>
                            <th>Chef de projet</th>
                        </tr>
                    </thead>
                    <!-- intérieur du tableau (les prospects ligne par ligne) -->
                    <tbody id="tbody">
                        <tr><td colspan="16" style="text-align:center; padding:1rem; color:var(--text-muted);">Chargement...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="pagination" id="pagination">
                <!-- Boutons de pagination: -->
                <button class="btn" id="prev" style="display:none">Précédent</button>
                <span class="page-info" id="page-info">Page 1 / 1</span>
                <button class="btn" id="next">Suivant</button>
            </div>
        </section>
        
        <!-- footer inspiré de https://formation.cyje.fr/ - tous droits réservés à son créateur -->
        <footer class="site-footer" role="contentinfo">
            <div class="footer-top">
                <div class="footer-container">
                    <div class="footer-col footer-brand">
                        <img src="assets/logo_cyje.png" alt="CY Junior Engineering" class="footer-logo">
                    </div>
                    <div class="footer-col">
                        <h3 class="footer-title">Découvrir CYJE</h3>
                        <ul class="footer-links">
                            <li><a href="https://formation.cyje.fr/notre-structure/" target="_blank" rel="noopener">Notre structure</a></li>
                            <li><a href="https://formation.cyje.fr/formations" target="_blank" rel="noopener">Formations</a></li>
                            <li><a href="https://formation.cyje.fr/conferences" target="_blank" rel="noopener">Conférences</a></li>
                            <li><a href="https://formation.cyje.fr/lives/" target="_blank" rel="noopener">Lives</a></li>
                            <li><a href="https://formation.cyje.fr/mentions-legales/" target="_blank" rel="noopener">Mentions légales</a></li>
                        </ul>
                    </div>
                    <div class="footer-col">
                        <h3 class="footer-title">Nous suivre</h3>
                        <ul class="footer-links">
                            <li><a href="https://fr.linkedin.com/company/cyjuniorengineering" target="_blank" rel="noopener">LinkedIn</a></li>
                            <li><a href="https://www.instagram.com/juniorcyje/#" target="_blank" rel="noopener">Instagram</a></li>
                            <li><a href="https://www.facebook.com/CYJuniorEngineering/" target="_blank" rel="noopener">Facebook</a></li>
                        </ul>
                    </div>
                    <div class="footer-col">
                        <h3 class="footer-title">Nous rejoindre</h3>
                        <ul class="footer-links">
                            <li><a href="https://formation.cyje.fr/inscription/" target="_blank" rel="noopener">S'inscrire</a></li>
                            <li><a href="https://docs.google.com/forms/d/e/1FAIpQLSehX2QiWabQ0yVeliaiz9AqzzTx4TDPHCROZ7PLiTrpQJ9bAg/viewform?usp=sf_link" target="_blank" rel="noopener">Devenir intervenant</a></li>
                            <li><a href="https://web.telegram.org/a/#-4919318791" target="_blank" rel="noopener">Devenir administrateur</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="footer-container">
                    <div class="copyright">@CY Junior Engineering | Développé par CY Junior Engineering</div>
                </div>
            </div>
        </footer>
    </main>
    
    <script>
        // Script IIFE pour éviter les variables globales et initialiser les listeners.
        // Responsabilités: gérer l'état (page, recherche, filtres), appeler l'API, rendre le tableau, piloter la pagination.
        (function(){
            // Récupération des références DOM (ciblage unique par id).
            const tbody = document.getElementById('tbody');          // Corps du tableau où les lignes seront injectées
            const searchInput = document.getElementById('search');    // Champ de recherche globale
            const filterField = document.getElementById('filter-field'); // Select: nom du champ à filtrer
            const filterValue = document.getElementById('filter-value'); // Input: valeur du filtre
            const btnApply = document.getElementById('apply-filter'); // Bouton: appliquer le filtre
            const btnReset = document.getElementById('reset-filter'); // Bouton: réinitialiser le filtre
            const btnPrev = document.getElementById('prev');          // Bouton pagination: page précédente
            const btnNext = document.getElementById('next');          // Bouton pagination: page suivante
            const pageInfo = document.getElementById('page-info');    // Libellé "Page X / Y"
            
            // Etat interne de la liste (source de vérité pour l'URL de l'API)
            let page = 1;                 // numéro de page en cours (>=1)
            const pageSize = 20;          // taille de page constante côté front (doit matcher l'API)
            let totalPages = 1;           // nombre total de pages renvoyé par l'API
            let q = '';                   // terme de recherche globale
            let fField = '';              // nom du champ pour filtrage ciblé
            let fValue = '';              // valeur du filtre ciblé
            let debounceTimer;            // identifiant du timer de debounce pour la recherche
            
            /**
             * Formate une date MySQL/ISO en DD-MM-YYYY pour l'affichage utilisateur.
             * - Ignore les dates nulles type 0000-00-00
             * - Gère "YYYY-MM-DD" et "YYYY-MM-DD HH:MM:SS" en réordonnant les composants
             * - Fallback: tentative de parse avec Date(), sinon retourne la valeur brute
             */
            function fmtDate(raw){
                if (!raw) return '';
                const s = String(raw).trim();
                if (s === '0000-00-00' || s === '0000-00-00 00:00:00') return '';
                // Cas MySQL/ISO standard
                const m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
                if (m) {
                    const [, y, mo, d] = m;
                    return `${d}-${mo}-${y}`; // DD-MM-YYYY
                }
                // Cas restant: on tente un parse natif puis on reformate
                try {
                    const d = new Date(s);
                    if (Number.isNaN(d.getTime())) return s;
                    const dd = String(d.getDate()).padStart(2,'0');
                    const mm = String(d.getMonth()+1).padStart(2,'0');
                    const yyyy = String(d.getFullYear());
                    return `${dd}-${mm}-${yyyy}`;
                } catch {
                    return s;
                }
            }
            
            /**
             * Charge une page de données via l'API (scripts/prospects_api.php) et met à jour le DOM.
             * Contrat API attendu: JSON { success, data:[], total, page, pageSize, totalPages }.
             */
            async function load(){
                // Construit la query string à partir de l'état courant
                const params = new URLSearchParams({ page: String(page), pageSize: String(pageSize) });
                if (q) params.set('q', q); // recherche globale
                if (fField && fValue) {    // filtre ciblé uniquement si les deux sont fournis
                    params.set('filter_field', fField);
                    params.set('filter_value', fValue);
                }
                
                // Affiche un état "chargement" dans le tableau (une seule ligne)
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:1rem; color:var(--text-muted);">Chargement...</td></tr>';
                try {
                    // Appel à l'API (GET) avec un header informatif
                    const res = await fetch('scripts/prospects_api.php?' + params.toString(), { headers: { 'X-Requested-With': 'fetch' } });
                    // Parse la réponse JSON (peut lever si JSON invalide)
                    const data = await res.json();
                    // L'API encode la réussite dans success; si false -> lève une erreur
                    if (!data.success) throw new Error(data.error || 'Erreur inconnue');
                    
                    // Rendu des lignes du tableau
                    if (data.data.length === 0) {
                        // Aucun résultat: affiche une ligne vide informative sur 16 colonnes
                        tbody.innerHTML = '<tr><td colspan="16" style="text-align:center; padding:1rem; color:var(--text-muted);">Aucun prospect</td></tr>';
                    } else {
                        // Transforme chaque objet prospect en <tr> avec <td> alignés sur l'en-tête
                        tbody.innerHTML = data.data.map(p => {
                            // Les contacts sont agrégés côté API par lignes séparées par "\n": on split puis on filtre les vides
                            const nomLignes = (p.contacts_noms||'').split('\n').filter(Boolean);
                            const prenomLignes = (p.contacts_prenoms||'').split('\n').filter(Boolean);
                            const emailLignes = (p.contacts_emails||'').split('\n').filter(Boolean);
                            const telLignes = (p.contacts_tels||'').split('\n').filter(Boolean);
                            const posteLignes = (p.contacts_postes||'').split('\n').filter(Boolean);
                            // Helper pour rendre plusieurs valeurs sur plusieurs lignes HTML avec échappement
                            const multi = arr => arr.map(x=>escapeHtml(x)).join('<br>');
                            // Détermine la classe de chip pour la chaleur
                            const heat = (p.chaleur||'').toLowerCase();
                            const heatCls = heat === 'chaud' ? 'chip-heat-chaud'
                                : (heat === 'tiède' || heat === 'tiede' ? 'chip-heat-tiede'
                                : (heat === 'froid' ? 'chip-heat-froid' : ''));
                            // Construit la ligne du tableau avec tous les champs affichés
                            return `
                                <tr>
                                    <td>${p.id}</td>
                                    <td>${p.entreprise ? escapeHtml(p.entreprise) : ''}</td>
                                    <td>${p.secteur ? escapeHtml(p.secteur) : ''}</td>
                                    <td><span class="status">${p.status_prospect ? escapeHtml(p.status_prospect) : ''}</span></td>
                                    <td>${multi(nomLignes)}</td>
                                    <td>${multi(prenomLignes)}</td>
                                    <td>${emailLignes.map(e => e ? `<a href=\"mailto:${escapeAttr(e)}\">${escapeHtml(e)}<\/a>` : '').join('<br>')}</td>
                                    <td>${multi(telLignes)}</td>
                                    <td>${multi(posteLignes)}</td>
                                    <td>${fmtDate(p.relance_le)}</td>
                                    <td><span class="badge">${p.type_acquisition ? escapeHtml(p.type_acquisition) : ''}</span></td>
                                    <td>${fmtDate(p.date_premier_contact)}</td>
                                    <td>${p.type_premier_contact ? `<span class=\"chip\">${escapeHtml(p.type_premier_contact)}<\/span>` : ''}</td>
                                    <td>${p.chaleur ? `<span class=\"chip ${heatCls}\">${escapeHtml(p.chaleur)}<\/span>` : ''}</td>
                                    <td>${p.offre_prestation ? escapeHtml(p.offre_prestation) : ''}</td>
                                    <td>${p.chef_projet ? escapeHtml(p.chef_projet) : ''}</td>
                                </tr>`;}).join('');
                    }
                    
                    // Mise à jour de la pagination d'après la réponse API
                    totalPages = Math.max(1, data.totalPages || 1);  // minimum 1 pour l'affichage
                    page = Math.min(page, totalPages);               // on borne la page courante si nécessaire
                    // Affiche/masque les boutons selon la position dans la pagination
                    btnPrev.style.display = page <= 1 ? 'none' : '';
                    btnNext.style.display = page >= totalPages ? 'none' : '';
                    pageInfo.textContent = `Page ${page} / ${totalPages}`; // libellé central
                } catch (e) {
                    // En cas d'erreur (réseau/JSON/erreur applicative), on informe dans le tableau
                    tbody.innerHTML = `<tr><td colspan="16" style="color:#c1121f; font-weight:700; text-align:center; padding:1rem;">${escapeHtml(e.message)}</td></tr>`;
                }
            }
            
            /**
             * Echappe le HTML pour prévenir les injections (XSS) dans le contenu.
             * Remplace & < > " ' par leurs entités HTML.
             */
            function escapeHtml(str){
                return String(str)
                .replaceAll('&','&amp;')
                .replaceAll('<','&lt;')
                .replaceAll('>','&gt;')
                .replaceAll('"','&quot;')
                .replaceAll("'",'&#39;');
            }
            
            /**
             * Echappe les guillemets pour les attributs HTML (ex: href="mailto:...").
             * Moins généraliste qu'escapeHtml, mais adapté aux attributs.
             */
            function escapeAttr(str){
                return String(str).replace(/"/g,'&quot;');
            }
            
            // Ecouteurs d'événements UI (recherche, filtres, pagination)
            // Recherche: debounce 300ms pour éviter un appel API à chaque frappe
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    q = searchInput.value.trim(); // met à jour l'état "q"
                    page = 1;                     // on repart de la première page sur nouveau critère
                    load();                        // recharge la liste
                }, 300);
            });
            
            // Appliquer le filtre ciblé: prend la valeur sélectionnée + saisie et recharge
            btnApply.addEventListener('click', () => {
                fField = filterField.value;
                fValue = filterValue.value.trim();
                page = 1;
                load();
            });
            
            // Réinitialiser le filtre: efface sélecteur/valeur et recharge la première page
            btnReset.addEventListener('click', () => {
                filterField.value = '';
                filterValue.value = '';
                fField = '';
                fValue = '';
                page = 1;
                load();
            });
            
            // Pagination: recule/avance d'une page si possible, puis recharge
            btnPrev.addEventListener('click', () => { if (page > 1) { page--; load(); } });
            btnNext.addEventListener('click', () => { if (page < totalPages) { page++; load(); } });
            
            // Premier rendu: charge la page initiale avec l'état par défaut
            load();
        })();
    </script>
</body>
</html>