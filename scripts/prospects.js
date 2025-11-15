// Responsabilités: gérer l'état (page, recherche, filtres), appeler l'API, rendre le tableau, piloter la pagination.
//
// Edition de prospect — logique front
//
// Ce module gère:
// 1) L'ouverture du panneau Détails, le chargement des données complètes d'un prospect (GET action=detail)
// 2) Le rendu d'un formulaire en lecture seule puis son déverrouillage (Modifier/Annuler)
// 3) La mise à jour (POST action=update) incluant le remplacement complet des contacts
// 4) Le select "Chef de projet" (libellé "prénom nom", valeur id) chargé via GET action=users
// 5) La suppression (POST action=delete) 
(function () {
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
    // Champs formulaire détails (prospect)
    const detailForm = document.getElementById('detail-form');
    const detailEntreprise = document.getElementById('detail-entreprise');
    const detailSecteur = document.getElementById('detail-secteur');
    const detailAdresse = document.getElementById('detail-adresse');
    const detailSite = document.getElementById('detail-site');
    const detailStatus = document.getElementById('detail-status');
    const detailAcq = document.getElementById('detail-acq');
    const detailTpc = document.getElementById('detail-tpc');
    const detailChaleur = document.getElementById('detail-chaleur');
    const detailOffre = document.getElementById('detail-offre');
    const detailRelance = document.getElementById('detail-relance');
    const detailDatePC = document.getElementById('detail-datepc');
    const detailChef = document.getElementById('detail-chef');
    const detailComment = document.getElementById('detail-comment');
    const detailContacts = document.getElementById('detail-contacts');
    const detailAddContact = document.getElementById('detail-add-contact');
    const detailEdit = document.getElementById('detail-edit');
    const detailSave = document.getElementById('detail-save');
    const detailCancel = document.getElementById('detail-cancel');
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
            // Remplit le select des valeurs de filtre (toolbar de la liste)
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
    function fmtDate(raw) {
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
            const dd = String(d.getDate()).padStart(2, '0');
            const mm = String(d.getMonth() + 1).padStart(2, '0');
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
    async function load() {
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
                    const nomLignes = (p.contacts_noms || '').split('\n').filter(Boolean);
                    const prenomLignes = (p.contacts_prenoms || '').split('\n').filter(Boolean);
                    const emailLignes = (p.contacts_emails || '').split('\n').filter(Boolean);
                    const telLignes = (p.contacts_tels || '').split('\n').filter(Boolean);
                    const posteLignes = (p.contacts_postes || '').split('\n').filter(Boolean);
                    // Helper pour rendre plusieurs valeurs sur plusieurs lignes HTML avec échappement
                    const multi = arr => arr.map(x => escapeHtml(x)).join('<br>');
                    // Détermine la classe de chip pour la chaleur
                    const heat = (p.chaleur || '').toLowerCase();
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
                                </tr>`;
                }).join('');
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

            // Helpers selects ENUM pour détail
            /**
             * Remplit un <select> avec un placeholder et un tableau de chaînes
             * Utilisé pour les champs ENUM (status, acquisition, etc.)
             */
            function fillSelect(sel, options, placeholder='Choisir...'){
                sel.innerHTML = '';
                const ph = document.createElement('option'); ph.value=''; ph.textContent=placeholder; sel.appendChild(ph);
                for (const v of options){ const o=document.createElement('option'); o.value=v; o.textContent=v; sel.appendChild(o); }
            }
            /**
             * Remplit un <select> avec des paires { value, label }
             * Utilisé pour le select des chefs de projet (value = id, label = "prénom nom")
             */
            function fillSelectPairs(sel, items, placeholder='Choisir...'){
                sel.innerHTML = '';
                const ph = document.createElement('option'); ph.value=''; ph.textContent=placeholder; sel.appendChild(ph);
                for (const it of items){ const o=document.createElement('option'); o.value=String(it.value); o.textContent=it.label; sel.appendChild(o); }
            }
            let DETAIL_USERS_CACHE = null;
            // Charge (une fois) la liste des utilisateurs pour le select Chef de projet
            async function loadUsersList(){
                if (DETAIL_USERS_CACHE) return DETAIL_USERS_CACHE;
                const res = await fetch('scripts/prospects_api.php?action=users', { headers: { 'X-Requested-With': 'fetch' } });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Erreur chargement utilisateurs');
                DETAIL_USERS_CACHE = Array.isArray(data.users) ? data.users : [];
                return DETAIL_USERS_CACHE;
            }

            // Ajoute une ligne contact dans le formulaire détail
            /**
             * Ajoute une ligne "contact" dans le formulaire détail
             * Mode lecture seule par défaut, le bouton supprimer contact est visible uniquement en mode édition
             */
            function detailAddContactRow(values={}){
                const wrap = document.createElement('div');
                wrap.className = 'contact-row';
                wrap.innerHTML = `
                    <input class="input" type="text" placeholder="Nom" value="${values.nom||''}" disabled>
                    <input class="input" type="text" placeholder="Prénom" value="${values.prenom||''}" disabled>
                    <input class="input" type="email" placeholder="Email" value="${values.email||''}" disabled>
                    <input class="input" type="tel" placeholder="Téléphone" value="${values.tel||''}" disabled>
                    <input class="input" type="text" placeholder="Poste" value="${values.poste||''}" disabled>
                    <button type="button" class="btn btn-ghost remove-contact" title="Supprimer" style="display:none">✕</button>`;
                detailContacts.appendChild(wrap);
            }

            // Verrouille/déverrouille tous les champs du formulaire détail
            /**
             * Verrouille/déverrouille tous les champs du formulaire détail
             * locked=true  => lecture seule (désactive inputs/selects, cache boutons remove & add)
             * locked=false => modif possible
             */
            function setDetailLocked(locked){
                const controls = detailForm.querySelectorAll('input, select, textarea, .remove-contact');
                controls.forEach(el => {
                    if (el.classList && el.classList.contains('remove-contact')){
                        el.style.display = locked ? 'none' : '';
                    } else {
                        el.disabled = locked;
                    }
                });
                detailAddContact.style.display = locked ? 'none' : '';
                detailEdit.style.display = locked ? '' : 'none';
                detailSave.style.display = locked ? 'none' : '';
                detailCancel.style.display = locked ? 'none' : '';
            }

            // Charge et ouvre le détail par ID (préremplit le formulaire en lecture seule)
            async function openDetail(id) {
                currentDetailId = id ? Number(id) : null;
                // peupler selects enum
                fillSelect(detailStatus, STATUS_OPTIONS, 'Choisir...');
                fillSelect(detailAcq, ACQUISITION_OPTIONS, 'Choisir...');
                fillSelect(detailTpc, TYPE_PREMIER_CONTACT_OPTIONS, 'Choisir...');
                fillSelect(detailChaleur, CHALEUR_OPTIONS, 'Choisir...');
                fillSelect(detailOffre, OFFRE_PRESTATION_OPTIONS, 'Choisir...');

                // état chargement simple
                detailContacts.innerHTML = '';
                setDetailLocked(true);

                try {
                    const res = await fetch(`scripts/prospects_api.php?action=detail&id=${encodeURIComponent(currentDetailId)}`, { headers: { 'X-Requested-With': 'fetch' } });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error || 'Introuvable');
                    const p = data.prospect || {};
                    // Charger la liste des chefs de projet, puis sélectionner la valeur
                    try {
                        const users = await loadUsersList();
                        const items = users.map(u => ({ value: u.id, label: `${u.prenom} ${u.nom}`.trim() }));
                        fillSelectPairs(detailChef, items, 'Choisir un chef de projet...');
                    } catch(_) {
                        // placeholder déjà présent
                    }
                    // Remplit les champs
                    detailEntreprise.value = p.entreprise || '';
                    detailSecteur.value = p.secteur || '';
                    detailAdresse.value = p.adresse_entreprise || '';
                    detailSite.value = p.site_web_entreprise || '';
                    detailStatus.value = p.status_prospect || '';
                    detailAcq.value = p.type_acquisition || '';
                    detailTpc.value = p.type_premier_contact || '';
                    detailChaleur.value = p.chaleur || '';
                    detailOffre.value = p.offre_prestation || '';
                    detailRelance.value = p.relance_le || '';
                    detailDatePC.value = p.date_premier_contact || '';
                    detailChef.value = (p.chef_de_projet_id != null ? String(p.chef_de_projet_id) : '');
                    detailComment.value = p.commentaire || '';
                    // contacts
                    detailContacts.innerHTML = '';
                    const contacts = Array.isArray(data.contacts) ? data.contacts : [];
                    if (contacts.length === 0) {
                        detailAddContactRow({});
                    } else {
                        contacts.forEach(c => detailAddContactRow(c));
                    }
                } catch (e) {
                    alert('Erreur: ' + (e.message || 'inconnue'));
                }

                detailPanel.setAttribute('aria-hidden','false');
                detailPanel.classList.add('visible');
                detailPanel.style.transform = 'translateX(0)';
                document.body.classList.add('detail-panel-open');
            }
    function closeDetail() {
        detailPanel.classList.remove('visible');
        detailPanel.setAttribute('aria-hidden', 'true');
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
                openDetail(btn.dataset.id);
    });

            // Ajout/suppression de contacts en mode édition (détails)
            detailAddContact.addEventListener('click', () => {
                detailAddContactRow({});
                setDetailLocked(false); // maintenir l'état
            });
            detailContacts.addEventListener('click', (e)=>{
                const b = e.target.closest('.remove-contact');
                if (!b) return;
                b.parentElement.remove();
            });

            // Edit/Cancel
            detailEdit.addEventListener('click', () => {
                setDetailLocked(false);
            });
            detailCancel.addEventListener('click', () => {
                if (!currentDetailId) return;
                openDetail(currentDetailId); // recharge et reverrouille
            });

            // Enregistrer modifications
            /**
             * Soumet les modifications (POST action=update)
             * - Construit payload { id, prospect:{...}, contacts:[...] }
             * - Contacts: stratégie de remplacement complet côté API
             * - Sur succès: relock + rafraîchit la liste
             */
            detailForm.addEventListener('submit', async (e)=>{
                e.preventDefault();
                if (!currentDetailId) return;
                const contacts = Array.from(detailContacts.children).map(row=>{
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
                    action: 'update',
                    id: currentDetailId,
                    prospect: {
                        entreprise: detailEntreprise.value.trim(),
                        secteur: detailSecteur.value.trim(),
                        adresse_entreprise: detailAdresse.value.trim(),
                        site_web_entreprise: detailSite.value.trim(),
                        status_prospect: detailStatus.value,
                        type_acquisition: detailAcq.value,
                        type_premier_contact: detailTpc.value,
                        chaleur: detailChaleur.value,
                        offre_prestation: detailOffre.value,
                        relance_le: detailRelance.value || null,
                        date_premier_contact: detailDatePC.value || null,
                        chef_de_projet_id: detailChef.value ? Number(detailChef.value) : null,
                        commentaire: detailComment.value.trim()
                    },
                    contacts
                };
                if (!payload.prospect.entreprise){ alert('Entreprise est obligatoire'); return; }
                if (!payload.prospect.chef_de_projet_id){ alert('Chef de projet est obligatoire'); return; }
                try {
                    const res = await fetch('scripts/prospects_api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error || 'Echec de mise à jour');
                    setDetailLocked(true);
                    load(); // rafraîchit la liste
                } catch(err){
                    alert('Erreur: ' + (err.message||'inconnue'));
                }
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
    async function openAddPanel() {
        // peupler les selects ENUM à l'ouverture
        fillSelect(addStatus, STATUS_OPTIONS, 'Choisir...');
        fillSelect(addAcq, ACQUISITION_OPTIONS, 'Choisir...');
        fillSelect(addTpc, TYPE_PREMIER_CONTACT_OPTIONS, 'Choisir...');
        fillSelect(addChaleur, CHALEUR_OPTIONS, 'Choisir...');
        fillSelect(addOffre, OFFRE_PRESTATION_OPTIONS, 'Choisir...');
        // peupler la liste des chefs de projet
        try {
            const users = await loadUsers();
            const items = users.map(u => ({ value: String(u.id), label: `${u.prenom} ${u.nom}`.trim() }));
            fillSelectPairs(addChef, items, 'Choisir un chef de projet...');
        } catch (_) {
            // en cas d'erreur, on laisse le select avec le placeholder
        }
        // par défaut un bloc contact vide
        if (!contactsList.children.length) addContactRow();
        addPanel.setAttribute('aria-hidden', 'false');
        addPanel.classList.add('visible');
        document.body.classList.add('detail-panel-open');
    }
    function closeAddPanel() {
        addPanel.classList.remove('visible');
        addPanel.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('detail-panel-open');
    }
    /** Remplit un <select> avec une option placeholder puis les valeurs d'un tableau. */
    function fillSelect(sel, options, placeholder = 'Choisir...') {
        sel.innerHTML = '';
        const ph = document.createElement('option'); ph.value = ''; ph.textContent = placeholder; sel.appendChild(ph);
        for (const v of options) { const o = document.createElement('option'); o.value = v; o.textContent = v; sel.appendChild(o); }
    }
    // Remplit un <select> avec des paires value/label
    function fillSelectPairs(sel, items, placeholder = 'Choisir...') {
        sel.innerHTML = '';
        const ph = document.createElement('option'); ph.value = ''; ph.textContent = placeholder; sel.appendChild(ph);
        for (const it of items) { const o = document.createElement('option'); o.value = it.value; o.textContent = it.label; sel.appendChild(o); }
    }
    // Cache simple pour la liste des utilisateurs
    let USERS_CACHE = null;
    async function loadUsers() {
        if (USERS_CACHE) return USERS_CACHE;
        const res = await fetch('scripts/prospects_api.php?action=users', { headers: { 'X-Requested-With': 'fetch' } });
        const data = await res.json();
        if (!data.success) throw new Error(data.error || 'Erreur chargement utilisateurs');
        USERS_CACHE = Array.isArray(data.users) ? data.users : [];
        return USERS_CACHE;
    }
    /**
    * Ajoute un bloc visuel de contact (vertical), champs facultatifs.
    * values peut pré-remplir {nom, prenom, email, tel, poste}.
    */
    function addContactRow(values = {}) {
        const wrap = document.createElement('div');
        wrap.className = 'contact-row';
        wrap.innerHTML = `
                    <input class="input" type="text" placeholder="Nom" value="${values.nom || ''}">
                    <input class="input" type="text" placeholder="Prénom" value="${values.prenom || ''}">
                    <input class="input" type="email" placeholder="Email" value="${values.email || ''}">
                    <input class="input" type="tel" placeholder="Téléphone" value="${values.tel || ''}">
                    <input class="input" type="text" placeholder="Poste" value="${values.poste || ''}">
                    <button type="button" class="btn btn-ghost remove-contact" title="Supprimer">✕</button>`;
        contactsList.appendChild(wrap);
    }
    addContactBtn.addEventListener('click', () => addContactRow());
    contactsList.addEventListener('click', (e) => {
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
    addForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const contacts = Array.from(contactsList.children).map(row => {
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
        if (!payload.prospect.entreprise) { alert('Entreprise est obligatoire'); return; }
        if (!payload.prospect.chef_de_projet_id) { alert('Chef de projet est obligatoire'); return; }

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
        } catch (err) {
            alert('Erreur: ' + (err.message || 'inconnue'));
        }
    });

    /**
    * Echappe le HTML pour prévenir les injections (XSS) dans le contenu.
    * Remplace & < > " ' par leurs entités HTML.
    */
    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    /**
    * Echappe les guillemets pour les attributs HTML (ex: href="mailto:...").
    * Moins généraliste qu'escapeHtml, mais adapté aux attributs.
    */
    function escapeAttr(str) {
        return String(str).replace(/"/g, '&quot;');
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