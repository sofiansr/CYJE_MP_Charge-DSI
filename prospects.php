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
                <!--
                    Bloc de FILTRAGE ciblé (champ + valeur):
                    - #filter-field: sélectionne la colonne à filtrer (enum/texte/date). Les valeurs correspondent aux colonnes SQL whitelistees côté API.
                    - #filter-value: champ texte libre (utilisé pour entreprise, secteur, etc. s'ils sont exposés dans la liste) -> la valeur est envoyée telle quelle.
                    - #filter-value-select: liste des valeurs prédéfinies (ENUM) pour status_prospect, type_acquisition, type_premier_contact, chaleur, offre_prestation.
                    - #filter-value-date: calendrier natif pour les champs date (relance_le, date_premier_contact). La valeur YYYY-MM-DD est convertie en DD-MM-YYYY avant envoi.
                    Règle: un seul contrôle de valeur est visible à la fois. La fonction updateFilterInputVisibility() décide lequel afficher selon le champ choisi.
                -->
                <div style="display:flex; gap:.5rem; align-items:center;">
                    <!-- sélecteur de champ de filtre -->
                    <select id="filter-field" class="select" aria-label="Filtrer par">
                        <option value="">Filtrer par...</option>
                        <option value="status_prospect">Statut</option>
                        <option value="type_acquisition">Type d'acquisition</option>
                        <option value="type_premier_contact">Type 1er contact</option>
                        <option value="chaleur">Chaleur</option>
                        <option value="offre_prestation">Offre prestation</option>
                        <option value="relance_le">Relancé le</option>
                        <option value="date_premier_contact">Date 1er contact (DD-MM-YYYY)</option>
                    </select>
                    <!-- saisie de la valeur de filtre : -->
                    <input id="filter-value" class="input" type="text" placeholder="Valeur du filtre">
                    <!-- Liste déroulante pour les valeurs prédéfinies du filtre : -->
                    <select id="filter-value-select" class="select" style="display:none"></select>
                    <!-- Calendrier (date) : visible pour relance_le et date_premier_contact -->
                    <input id="filter-value-date" class="input" type="date" style="display:none" aria-label="Date du filtre">
                </div>
                <div style="display:flex; gap:.5rem;">
                    <button id="apply-filter" class="btn btn-primary">Appliquer</button>
                    <button id="reset-filter" class="btn btn-ghost">Réinitialiser</button>
                    <!--
                        Bouton "Ajouter un prospect"
                        - Ouvre le panneau latéral d'ajout (id: add-panel)
                        - Le panneau contient un formulaire complet (prospect + contacts multiples)
                        - Soumission: POST JSON vers scripts/prospects_api.php { action: 'create', prospect:{...}, contacts:[...] }
                        - En cas de succès: ferme le panneau et recharge la liste (page 1)
                    -->
                    <button id="btn-add-prospect" class="btn btn-primary" title="Ajouter un prospect">Ajouter un prospect</button>
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
                            <th>Détails</th>
                        </tr>
                    </thead>
                    <!-- intérieur du tableau (les prospects ligne par ligne) -->
                    <tbody id="tbody">
                        <tr><td colspan="17" style="text-align:center; padding:1rem; color:var(--text-muted);">Chargement...</td></tr>
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
    <!-- Panneau latéral de détails (overlay) masqué par défaut -->
    <aside id="detail-panel" class="detail-panel" aria-hidden="true" style="position:fixed;top:0;right:0;height:100vh;width:380px;transform:translateX(100%);background:#eceff1;z-index:110;display:flex;flex-direction:column;">
        <div class="detail-panel-header">
            <h2 class="detail-panel-title">Détails du prospect</h2>
            <button type="button" id="detail-close" class="detail-close" aria-label="Fermer">×</button>
        </div>
        <div class="detail-panel-body">
            <dl class="detail-list">
                <dt>Adresse</dt>
                <dd id="detail-adresse">—</dd>
                <dt>Site web</dt>
                <dd id="detail-siteweb">—</dd>
                <dt>Commentaire</dt>
                <dd id="detail-commentaire">—</dd>
            </dl>
            <!--
                Action de suppression
                - Bouton rouge "Supprimer ce prospect" dans le panneau Détails
                - Enclenche un confirm() puis POST JSON { action:'delete', id:<prospectId> } vers scripts/prospects_api.php
                - Sur succès: ferme le panneau et recharge la liste
            -->
            <div class="detail-actions" style="margin-top:1rem; display:flex; justify-content:flex-end;">
                <button type="button" id="detail-delete" class="btn btn-danger">Supprimer ce prospect</button>
            </div>
        </div>
    </aside>
        <!--
                Panneau latéral d'ajout de prospect (overlay)
                - Présenté comme le panneau de détails, largeur fixe ~380px
                - Contient:
                    * Bloc principal .form-grid (carte blanche) avec les champs du prospect:
                        Entreprise (requis), Secteur, Adresse, Site web (URL), Statut, Type d'acquisition,
                        Type 1er contact, Chaleur, Offre prestation, Relancé le (date), Date 1er contact (date),
                        Chef de projet (ID, requis), Commentaire.
                    * Section Contacts (zéro, un ou plusieurs) — chaque contact est rendu dans une "contact-row"
                        verticale avec Nom, Prénom, Email, Téléphone, Poste + bouton supprimer.
                - Validation minimale côté client: Entreprise + Chef de projet (ID) requis.
                - Dates envoyées au backend au format natif de l'input: YYYY-MM-DD.
                - Les listes (ENUM) sont alimentées au moment de l'ouverture du panneau via fillSelect(...).
        -->
    <aside id="add-panel" class="detail-panel" aria-hidden="true">
        <div class="detail-panel-header">
            <h2 class="detail-panel-title">Ajouter un prospect</h2>
            <button type="button" id="add-close" class="detail-close" aria-label="Fermer">×</button>
        </div>
        <div class="detail-panel-body">
            <form id="add-form">
                <div class="form-grid">
                    <label>Entreprise
                        <input id="add-entreprise" class="input" type="text" required>
                    </label>
                    <label>Secteur
                        <input id="add-secteur" class="input" type="text">
                    </label>
                    <label>Adresse
                        <input id="add-adresse" class="input" type="text">
                    </label>
                    <label>Site web
                        <input id="add-site" class="input" type="url" placeholder="https://...">
                    </label>
                    <label>Statut
                        <select id="add-status" class="select"></select>
                    </label>
                    <label>Type d'acquisition
                        <select id="add-acq" class="select"></select>
                    </label>
                    <label>Type 1er contact
                        <select id="add-tpc" class="select"></select>
                    </label>
                    <label>Chaleur
                        <select id="add-chaleur" class="select"></select>
                    </label>
                    <label>Offre prestation
                        <select id="add-offre" class="select"></select>
                    </label>
                    <label>Relancé le
                        <input id="add-relance" class="input" type="date">
                    </label>
                    <label>Date 1er contact
                        <input id="add-datepc" class="input" type="date">
                    </label>
                    <label>Chef de projet (ID)
                        <input id="add-chef" class="input" type="number" min="1" required>
                    </label>
                    <label>Commentaire
                        <textarea id="add-comment" class="input" rows="3"></textarea>
                    </label>
                </div>
                <div class="form-section">
                    <h3>Contacts</h3>
                    <div class="contacts-head">
                        <span>Nom</span>
                        <span>Prénom</span>
                        <span>Email</span>
                        <span>Téléphone</span>
                        <span>Poste</span>
                        <span></span>
                    </div>
                    <div id="contacts-list" class="contacts-list"></div>
                    <button type="button" id="add-contact" class="btn">Ajouter un contact</button>
                </div>
                <div style="margin-top:1rem; display:flex; gap:.5rem;">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <button type="button" id="add-cancel" class="btn btn-ghost">Annuler</button>
                </div>
            </form>
        </div>
    </aside>
    
    <script>
        // Script IIFE pour éviter les variables globales et initialiser les listeners.
        // Responsabilités: gérer l'état (page, recherche, filtres), appeler l'API, rendre le tableau, piloter la pagination.
        (function(){
            // Récupération des références DOM (ciblage unique par id).
            const tbody = document.getElementById('tbody');          // Corps du tableau où les lignes seront injectées
            const searchInput = document.getElementById('search');    // Champ de recherche globale
            const filterField = document.getElementById('filter-field'); // Select: nom du champ à filtrer
            const filterValue = document.getElementById('filter-value'); // Input: valeur du filtre
            const filterValueSelect = document.getElementById('filter-value-select'); // Select: valeur prédéfinie
            const filterValueDate = document.getElementById('filter-value-date'); // Input date: valeur date du filtre
            const btnApply = document.getElementById('apply-filter'); // Bouton: appliquer le filtre
            const btnReset = document.getElementById('reset-filter'); // Bouton: réinitialiser le filtre
            const btnPrev = document.getElementById('prev');          // Bouton pagination: page précédente
            const btnNext = document.getElementById('next');          // Bouton pagination: page suivante
            const pageInfo = document.getElementById('page-info');    // Libellé "Page X / Y"
            // Références panneau détail
            const detailPanel = document.getElementById('detail-panel');
            const detailClose = document.getElementById('detail-close');
            const detailAdresse = document.getElementById('detail-adresse');
            const detailSiteweb = document.getElementById('detail-siteweb');
            const detailCommentaire = document.getElementById('detail-commentaire');
            const detailDelete = document.getElementById('detail-delete');
            let currentDetailId = null; // id du prospect affiché dans le panneau de détail
            // Références panneau ajout
            const addBtn = document.getElementById('btn-add-prospect');
            const addPanel = document.getElementById('add-panel');
            const addClose = document.getElementById('add-close');
            const addForm = document.getElementById('add-form');
            const addEntreprise = document.getElementById('add-entreprise');
            const addSecteur = document.getElementById('add-secteur');
            const addAdresse = document.getElementById('add-adresse');
            const addSite = document.getElementById('add-site');
            const addStatus = document.getElementById('add-status');
            const addAcq = document.getElementById('add-acq');
            const addTpc = document.getElementById('add-tpc');
            const addChaleur = document.getElementById('add-chaleur');
            const addOffre = document.getElementById('add-offre');
            const addRelance = document.getElementById('add-relance');
            const addDatePC = document.getElementById('add-datepc');
            const addChef = document.getElementById('add-chef');
            const addComment = document.getElementById('add-comment');
            const contactsList = document.getElementById('contacts-list');
            const addContactBtn = document.getElementById('add-contact');
            const addCancel = document.getElementById('add-cancel');
            
            // Etat interne de la liste (source de vérité pour l'URL de l'API)
            let page = 1;                 // numéro de page en cours (>=1)
            const pageSize = 20;          // taille de page constante côté front (doit matcher l'API)
            let totalPages = 1;           // nombre total de pages renvoyé par l'API
            let q = '';                   // terme de recherche globale
            let fField = '';              // nom du champ pour filtrage ciblé
            let fValue = '';              // valeur du filtre ciblé
            let debounceTimer;            // identifiant du timer de debounce pour la recherche
            
            // status prospect
            const STATUS_OPTIONS = ['A contacter', 'Contacté', 'A rappeler', 'Relancé', 'RDV', 'PC', 'Signé', 'PC refusée', 'Perdu'];
            // type_acquisition
            const ACQUISITION_OPTIONS = ['DE', "Appel d'offre", 'Web crawling', 'Porte à porte', 'IRL', 'Fidélisation', 'BaNCO', 'Partenariat'];
            // type_premier_contact
            const TYPE_PREMIER_CONTACT_OPTIONS = ['Porte à porte', 'Formulaire de contact', 'Event CY Entreprise', 'LinkedIn', 'Mail', "Appel d'offre", 'DE', 'Cold call', 'Salon'];
            // chaleur
            const CHALEUR_OPTIONS = ['Froid', 'Tiède', 'Chaud'];
            // offre_prestation
            const OFFRE_PRESTATION_OPTIONS = ['Informatique', 'Chimie', 'Biotechnologies', 'Génie civil'];
            // Remplit la liste déroulante de valeurs pour un champ donné
            function populateFilterSelect(options, placeholder = 'Choisir...') {
                filterValueSelect.innerHTML = '';
                const optPlaceholder = document.createElement('option');
                optPlaceholder.value = '';
                optPlaceholder.textContent = placeholder;
                filterValueSelect.appendChild(optPlaceholder);
                for (const v of options) {
                    const o = document.createElement('option');
                    o.value = v;
                    o.textContent = v;
                    filterValueSelect.appendChild(o);
                }
            }

            // Bascule entre input texte et liste déroulante en fonction du champ à filtrer
            function updateFilterInputVisibility() {
                const field = filterField.value;
                // reset valeurs
                filterValue.value = '';
                filterValueSelect.value = '';
                filterValueDate.value = '';
                if (field === 'status_prospect') {
                    populateFilterSelect(STATUS_OPTIONS, 'Sélectionner un statut');
                    filterValue.style.display = 'none';
                    filterValueSelect.style.display = '';
                    filterValueDate.style.display = 'none'; // on n'affiche pas le calendrier
                } else if (field === 'type_acquisition') {
                    populateFilterSelect(ACQUISITION_OPTIONS, "Sélectionner un type d'acquisition");
                    filterValue.style.display = 'none';
                    filterValueSelect.style.display = '';
                    filterValueDate.style.display = 'none';
                } else if (field === 'type_premier_contact') {
                    populateFilterSelect(TYPE_PREMIER_CONTACT_OPTIONS, "Sélectionner un type de 1er contact");
                    filterValue.style.display = 'none';
                    filterValueSelect.style.display = '';
                    filterValueDate.style.display = 'none';
                } else if (field === 'chaleur') {
                    populateFilterSelect(CHALEUR_OPTIONS, 'Sélectionner une chaleur');
                    filterValue.style.display = 'none';
                    filterValueSelect.style.display = '';
                    filterValueDate.style.display = 'none';
                } else if (field === 'offre_prestation') {
                    populateFilterSelect(OFFRE_PRESTATION_OPTIONS, 'Sélectionner une offre');
                    filterValue.style.display = 'none';
                    filterValueSelect.style.display = '';
                    filterValueDate.style.display = 'none';
                } else if (field === 'relance_le' || field === 'date_premier_contact') {
                    // on affiche le calendrier
                    filterValue.style.display = 'none';
                    filterValueSelect.style.display = 'none';
                    filterValueDate.style.display = '';
                } else {
                    // Cas par défaut: texte libre
                    filterValue.style.display = '';
                    filterValueSelect.style.display = 'none';
                    filterValueDate.style.display = 'none';
                }
            }
            
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
                tbody.innerHTML = '<tr><td colspan="17" style="text-align:center; padding:1rem; color:var(--text-muted);">Chargement...</td></tr>';
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
                        tbody.innerHTML = '<tr><td colspan="17" style="text-align:center; padding:1rem; color:var(--text-muted);">Aucun prospect</td></tr>';
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
                                    <td><button type="button" class="btn btn-detail" data-id="${p.id}" data-adresse="${p.adresse_entreprise ? escapeAttr(p.adresse_entreprise) : ''}" data-siteweb="${p.site_web_entreprise ? escapeAttr(p.site_web_entreprise) : ''}" data-commentaire="${p.commentaire ? escapeAttr(p.commentaire) : ''}">Détails...</button></td>
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
                    tbody.innerHTML = `<tr><td colspan="17" style="color:#c1121f; font-weight:700; text-align:center; padding:1rem;">${escapeHtml(e.message)}</td></tr>`;
                }
            }

            // Ouvre le panneau de détails avec les données fournies
            function openDetail(id, adresse, siteweb, commentaire) {
                currentDetailId = id ? Number(id) : null;
                detailAdresse.textContent = adresse || '—';
                // site web: lien cliquable si présent
                if (siteweb) {
                    const safe = escapeHtml(siteweb);
                    detailSiteweb.innerHTML = `<a href="${escapeAttr(siteweb)}" target="_blank" rel="noopener">${safe}</a>`;
                } else {
                    detailSiteweb.textContent = '—';
                }
                detailCommentaire.textContent = commentaire || '—';
                detailPanel.setAttribute('aria-hidden','false');
                detailPanel.classList.add('visible');
                // Fallback si CSS non chargé: on force la translation à 0
                detailPanel.style.transform = 'translateX(0)';
                document.body.classList.add('detail-panel-open');
            }
            function closeDetail(){
                detailPanel.classList.remove('visible');
                detailPanel.setAttribute('aria-hidden','true');
                // Fallback: refermer par translation
                detailPanel.style.transform = 'translateX(100%)';
                document.body.classList.remove('detail-panel-open');
                currentDetailId = null;
            }
            detailClose.addEventListener('click', closeDetail);
            // Event delegation pour les boutons détail
            // On transmet aussi l'ID du prospect via data-id pour pouvoir déclencher la suppression depuis le panneau
            tbody.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-detail');
                if (!btn) return;
                openDetail(btn.dataset.id, btn.dataset.adresse, btn.dataset.siteweb, btn.dataset.commentaire);
            });

            // Suppression du prospect courant depuis le panneau détails
            // Contrat API: POST scripts/prospects_api.php body { action:'delete', id:number }
            // Réponse: { success:true } ou { success:false, error }
            detailDelete.addEventListener('click', async () => {
                if (!currentDetailId) return;
                const ok = confirm('Supprimer définitivement ce prospect et ses contacts ?');
                if (!ok) return;
                try {
                    const res = await fetch('scripts/prospects_api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'delete', id: currentDetailId })
                    });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error || 'Suppression impossible');
                    closeDetail();
                    // Recharge la page courante (load() bornnera si dernière ligne supprimée)
                    load();
                } catch (err) {
                    alert('Erreur: ' + (err.message || 'inconnue'));
                }
            });

            // ------ Ajout de prospect: helpers ------
            /**
             * Ouvre le panneau d'ajout et prépare l'UI:
             * - Remplit les <select> d'énumérations (statut, acquisition, etc.)
             * - Ajoute une première "contact-row" vide si aucune n'existe
             * - Affiche l'overlay (classe .visible) et active le backdrop via body.detail-panel-open
             */
            function openAddPanel(){
                // peupler les selects ENUM à l'ouverture
                fillSelect(addStatus, STATUS_OPTIONS, 'Choisir...');
                fillSelect(addAcq, ACQUISITION_OPTIONS, 'Choisir...');
                fillSelect(addTpc, TYPE_PREMIER_CONTACT_OPTIONS, 'Choisir...');
                fillSelect(addChaleur, CHALEUR_OPTIONS, 'Choisir...');
                fillSelect(addOffre, OFFRE_PRESTATION_OPTIONS, 'Choisir...');
                // par défaut un bloc contact vide
                if (!contactsList.children.length) addContactRow();
                addPanel.setAttribute('aria-hidden','false');
                addPanel.classList.add('visible');
                document.body.classList.add('detail-panel-open');
            }
            function closeAddPanel(){
                addPanel.classList.remove('visible');
                addPanel.setAttribute('aria-hidden','true');
                document.body.classList.remove('detail-panel-open');
            }
            /** Remplit un <select> avec une option placeholder puis les valeurs d'un tableau. */
            function fillSelect(sel, options, placeholder='Choisir...'){
                sel.innerHTML = '';
                const ph = document.createElement('option'); ph.value=''; ph.textContent=placeholder; sel.appendChild(ph);
                for (const v of options){ const o=document.createElement('option'); o.value=v; o.textContent=v; sel.appendChild(o); }
            }
            /**
             * Ajoute un bloc visuel de contact (vertical), champs facultatifs.
             * values peut pré-remplir {nom, prenom, email, tel, poste}.
             */
            function addContactRow(values={}){
                const wrap = document.createElement('div');
                wrap.className = 'contact-row';
                wrap.innerHTML = `
                    <input class="input" type="text" placeholder="Nom" value="${values.nom||''}">
                    <input class="input" type="text" placeholder="Prénom" value="${values.prenom||''}">
                    <input class="input" type="email" placeholder="Email" value="${values.email||''}">
                    <input class="input" type="tel" placeholder="Téléphone" value="${values.tel||''}">
                    <input class="input" type="text" placeholder="Poste" value="${values.poste||''}">
                    <button type="button" class="btn btn-ghost remove-contact" title="Supprimer">✕</button>`;
                contactsList.appendChild(wrap);
            }
            addContactBtn.addEventListener('click', () => addContactRow());
            contactsList.addEventListener('click', (e)=>{
                const b = e.target.closest('.remove-contact');
                if (!b) return;
                const row = b.parentElement;
                row.remove();
            });
            addBtn.addEventListener('click', openAddPanel);
            addClose.addEventListener('click', closeAddPanel);
            addCancel.addEventListener('click', closeAddPanel);

            // Soumission du formulaire d'ajout
            /**
             * Construit le payload JSON pour la création et l'envoie au backend:
             *   { action:'create', prospect:{...}, contacts:[...] }
             * - Dates: on envoie la valeur brute des <input type="date"> (YYYY-MM-DD)
             * - Contacts: on ne conserve que les lignes ayant au moins un champ rempli
             * - Sur succès: fermeture du panneau, reset du formulaire et rechargement de la liste (page 1)
             */
            addForm.addEventListener('submit', async (e)=>{
                e.preventDefault();
                const contacts = Array.from(contactsList.children).map(row=>{
                    const [nom, prenom, email, tel, poste] = row.querySelectorAll('input');
                    return {
                        nom: nom.value.trim(),
                        prenom: prenom.value.trim(),
                        email: email.value.trim(),
                        tel: tel.value.trim(),
                        poste: poste.value.trim()
                    };
                }).filter(c => c.nom || c.prenom || c.email || c.tel || c.poste);

                const payload = {
                    action: 'create',
                    prospect: {
                        entreprise: addEntreprise.value.trim(),
                        secteur: addSecteur.value.trim(),
                        adresse_entreprise: addAdresse.value.trim(),
                        site_web_entreprise: addSite.value.trim(),
                        status_prospect: addStatus.value,
                        type_acquisition: addAcq.value,
                        type_premier_contact: addTpc.value,
                        chaleur: addChaleur.value,
                        offre_prestation: addOffre.value,
                        relance_le: addRelance.value || null, // YYYY-MM-DD
                        date_premier_contact: addDatePC.value || null, // YYYY-MM-DD
                        chef_de_projet_id: addChef.value ? Number(addChef.value) : null,
                        commentaire: addComment.value.trim()
                    },
                    contacts
                };
                // validations simples côté front
                if (!payload.prospect.entreprise){ alert('Entreprise est obligatoire'); return; }
                if (!payload.prospect.chef_de_projet_id){ alert('Chef de projet (ID) est obligatoire'); return; }

                try {
                    const res = await fetch('scripts/prospects_api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error || 'Echec de création');
                    // succès: fermer et rafraîchir
                    closeAddPanel();
                    // reset formulaire
                    addForm.reset(); contactsList.innerHTML = '';
                    page = 1; load();
                } catch(err){
                    alert('Erreur: ' + (err.message||'inconnue'));
                }
            });
            
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
            
            // Changement de champ de filtre -> met à jour l'UI du contrôle de valeur
            filterField.addEventListener('change', updateFilterInputVisibility);

            // Appliquer le filtre ciblé: lit la valeur dans le contrôle visible, adapte le format des dates, et recharge
            btnApply.addEventListener('click', () => {
                fField = filterField.value;
                // Choisit la source: select visible (statut) ou input texte
                let currentValue = '';
                if (filterValueDate.style.display !== 'none') {
                    // L'input date renvoie YYYY-MM-DD -> convertir en DD-MM-YYYY pour correspondre au DATE_FORMAT du backend
                    const ymd = filterValueDate.value;
                    currentValue = ymd ? fmtDate(ymd) : '';
                } else if (filterValueSelect.style.display !== 'none') {
                    currentValue = filterValueSelect.value;
                } else {
                    currentValue = filterValue.value;
                }
                fValue = String(currentValue || '').trim();
                page = 1;
                load();
            });
            
            // Réinitialiser le filtre: efface sélecteur/valeur et recharge la première page
            btnReset.addEventListener('click', () => {
                filterField.value = '';
                filterValue.value = '';
                filterValueSelect.value = '';
                filterValue.style.display = '';
                filterValueSelect.style.display = 'none';
                filterValueDate.value = '';
                filterValueDate.style.display = 'none';
                fField = '';
                fValue = '';
                page = 1;
                load();
            });
            
            // Pagination: recule/avance d'une page si possible, puis recharge
            btnPrev.addEventListener('click', () => { if (page > 1) { page--; load(); } });
            btnNext.addEventListener('click', () => { if (page < totalPages) { page++; load(); } });
            
            // Premier rendu: prépare l'UI de filtre et charge la page initiale
            updateFilterInputVisibility();
            load();
        })();
    </script>
</body>
</html>