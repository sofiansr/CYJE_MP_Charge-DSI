/**
* Administration UI (users)
* -------------------------------------------------------------
* Charge la liste des utilisateurs (GET users_api.php?action=list)
* Charge le détail d'un utilisateur sélectionné (GET ...action=detail&id)
* Verrouille/déverrouille le formulaire (lecture vs édition)
* Enregistre les modifications (POST JSON { action:'update', id, user:{...} })
*
* Structure DOM attendue (admin.php):
* #user-select: <select> des utilisateurs, options générées dynamiquement
* #reload-users: bouton pour recharger la liste
* #user-form: formulaire avec champs #user-prenom, #user-nom, #user-email, #user-role
* Boutons: #user-edit, #user-save, #user-cancel
*
* Les rôles dans l'API sont 'admin' ou 'user' (minuscules). Dans l'UI liste, on affiche en MAJ.
* Le formulaire est verrouillé par défaut; l'édition nécessite un clic sur « Modifier ».
*/
(() => {
    const $ = (sel, root = document) => root.querySelector(sel);
    
    const userSelect = $('#user-select');
    const btnReload = $('#reload-users');
    const btnAdd = $('#user-add');
    const form = $('#user-form');
    const prenomEl = $('#user-prenom');
    const nomEl = $('#user-nom');
    const emailEl = $('#user-email');
    const roleEl = $('#user-role');
    const btnEdit = $('#user-edit');
    const btnSave = $('#user-save');
    const btnCancel = $('#user-cancel');
    
    let currentId = null; // ID de l'utilisateur actuellement chargé (null en création)
    let IS_NEW = false;   // Indique si on est en mode création (nouvel utilisateur)
    let LOCKED = true;
    
    function setLocked(locked) {
        // Bascule le formulaire en lecture seule (locked) ou en édition
        LOCKED = locked;
        [prenomEl, nomEl, emailEl, roleEl].forEach(el => el.disabled = locked);
        // Affiche/masque les bons boutons selon l'état
        btnEdit.style.display = locked ? '' : 'none';
        btnSave.style.display = locked ? 'none' : '';
        btnCancel.style.display = locked ? 'none' : '';
    }
    
    /**
    * fetchJSON
    * Enveloppe fetch avec parsing JSON et gestion d'erreurs cohérente
    */
    async function fetchJSON(url, options) {
        const res = await fetch(url, options);
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) {
            const msg = data.error || `Erreur ${res.status}`;
            throw new Error(msg);
        }
        return data;
    }
    
    /**
    * loadUsers
    * Récupère la liste des utilisateurs et peuple le select
    */
    async function loadUsers() {
        // Charge la liste des utilisateurs pour alimenter le <select>
        const data = await fetchJSON('scripts/users_api.php?action=list');
        userSelect.innerHTML = '<option value="">Choisir un utilisateur...</option>';
        data.users.forEach(u => {
            const opt = document.createElement('option');
            opt.value = String(u.id);
            const label = [u.prenom, u.nom].filter(Boolean).join(' ');
            const roleLabel = String(u.role || '').toUpperCase();
            opt.textContent = label ? `${label} (${roleLabel})` : `${u.email} (${roleLabel})`;
            userSelect.appendChild(opt);
        });
    }
    
    /**
    * loadDetail
    * Charge les données d'un utilisateur et remplit le formulaire (puis le verrouille)
    */
    async function loadDetail(id) {
        // Charge le détail d'un utilisateur par son ID et remplit le formulaire
        if (!id) return;
        const data = await fetchJSON(`scripts/users_api.php?action=detail&id=${encodeURIComponent(id)}`);
        const u = data.user;
        currentId = u.id;
        prenomEl.value = u.prenom || '';
        nomEl.value = u.nom || '';
        emailEl.value = u.email || '';
        roleEl.value = String(u.role || 'user').toLowerCase();
        setLocked(true);
    }

    // Prépare le formulaire pour création d'un nouvel utilisateur
    function startCreate() {
        // Prépare le formulaire pour la création d'un nouvel utilisateur
        IS_NEW = true;           // active le mode création
        currentId = null;        // aucun ID tant que non créé côté serveur
        userSelect.selectedIndex = 0; // réinitialise la sélection
        // Vide les champs et positionne un rôle par défaut
        prenomEl.value = '';
        nomEl.value = '';
        emailEl.value = '';
        roleEl.value = 'user';
        // Passe en mode édition
        setLocked(false);
    }
    
    btnReload?.addEventListener('click', async () => {
        // Recharge la liste des utilisateurs
        try {
            await loadUsers();
        } catch (e) {
            alert(e.message);
        }
    });
    
    userSelect?.addEventListener('change', async () => {
        // Sur changement de sélection, charge le détail et sort du mode création
        try {
            const id = userSelect.value ? parseInt(userSelect.value, 10) : null;
            await loadDetail(id);
            IS_NEW = false;
        } catch (e) {
            alert(e.message);
        }
    });
    
    btnEdit?.addEventListener('click', () => {
        // Permet d'éditer l'utilisateur actuellement chargé
        if (!currentId) return;
        setLocked(false);
    });
    
    btnCancel?.addEventListener('click', async () => {
        // Annule les modifications
        try {
            if (IS_NEW || !currentId) {
                // En mode création: on réinitialise et on reverrouille
                prenomEl.value = '';
                nomEl.value = '';
                emailEl.value = '';
                roleEl.value = 'user';
                setLocked(true);
                IS_NEW = false;
                return;
            }
            // Sinon: relit l'état serveur de l'utilisateur en cours
            await loadDetail(currentId);
        } catch (e) {
            alert(e.message);
        }
    });

    btnAdd?.addEventListener('click', () => {
        // Démarre le flux de création d'utilisateur
        startCreate();
    });
    
    form?.addEventListener('submit', async (ev) => {
        // Soumission du formulaire: création si IS_NEW ou currentId null, sinon mise à jour
        ev.preventDefault();
        const isCreate = !currentId || IS_NEW;
        const payload = isCreate
            ? {
                    action: 'create',
                    user: {
                        prenom: prenomEl.value.trim(),
                        nom: nomEl.value.trim(),
                        email: emailEl.value.trim(),
                        role: roleEl.value,
                    }
                }
            : {
                    action: 'update',
                    id: currentId,
                    user: {
                        prenom: prenomEl.value.trim(),
                        nom: nomEl.value.trim(),
                        email: emailEl.value.trim(),
                        role: roleEl.value,
                    }
                };
        try {
            const resp = await fetchJSON('scripts/users_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            setLocked(true);
            if (isCreate) {
                // Après création: ajoute la nouvelle option dans le select et la sélectionne
                const newId = resp.id;
                const label = [payload.user.prenom, payload.user.nom].filter(Boolean).join(' ');
                const opt = document.createElement('option');
                const roleUp = String(payload.user.role).toUpperCase();
                opt.value = String(newId);
                opt.textContent = (label ? label : payload.user.email) + ` (${roleUp})`;
                userSelect.appendChild(opt);
                userSelect.value = String(newId);
                currentId = newId;
                IS_NEW = false;
                // Affiche le mot de passe temporaire si retourné par l'API
                alert(resp.temp_password ? `Utilisateur créé. Mot de passe temporaire: ${resp.temp_password}` : 'Utilisateur créé.');
            } else {
                // Après mise à jour: rafraîchit l'étiquette dans la liste si nécessaire
                const selIdx = userSelect.selectedIndex;
                if (selIdx > 0) {
                    const label = [payload.user.prenom, payload.user.nom].filter(Boolean).join(' ');
                    userSelect.options[selIdx].textContent = (label ? label : payload.user.email) + ` (${String(payload.user.role).toUpperCase()})`;
                }
                alert('Utilisateur mis à jour avec succès');
            }
        } catch (e) {
            alert(e.message);
        }
    });
    
    // Init
    (async () => {
        try {
            setLocked(true);
            await loadUsers();
        } catch (e) {
            alert(e.message);
        }
    })();
})();
