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
    const form = $('#user-form');
    const prenomEl = $('#user-prenom');
    const nomEl = $('#user-nom');
    const emailEl = $('#user-email');
    const roleEl = $('#user-role');
    const btnEdit = $('#user-edit');
    const btnSave = $('#user-save');
    const btnCancel = $('#user-cancel');
    
    let currentId = null;
    let LOCKED = true;
    
    function setLocked(locked) {
        LOCKED = locked;
        [prenomEl, nomEl, emailEl, roleEl].forEach(el => el.disabled = locked);
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
    
    btnReload?.addEventListener('click', async () => {
        try {
            await loadUsers();
        } catch (e) {
            alert(e.message);
        }
    });
    
    userSelect?.addEventListener('change', async () => {
        try {
            const id = userSelect.value ? parseInt(userSelect.value, 10) : null;
            await loadDetail(id);
        } catch (e) {
            alert(e.message);
        }
    });
    
    btnEdit?.addEventListener('click', () => {
        if (!currentId) return;
        setLocked(false);
    });
    
    btnCancel?.addEventListener('click', async () => {
        try {
            if (!currentId) return;
            await loadDetail(currentId);
        } catch (e) {
            alert(e.message);
        }
    });
    
    form?.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        if (!currentId) return;
        const payload = {
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
            await fetchJSON('scripts/users_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            setLocked(true);
            // Rafraîchir l'étiquette dans la liste pour refléter un éventuel changement de nom/role
            const selIdx = userSelect.selectedIndex;
            if (selIdx > 0) {
                const label = [payload.user.prenom, payload.user.nom].filter(Boolean).join(' ');
                userSelect.options[selIdx].textContent = (label ? label : payload.user.email) + ` (${String(payload.user.role).toUpperCase()})`;
            }
            alert('Utilisateur mis à jour avec succès');
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
