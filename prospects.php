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
                    <div class="copyright">@CY Junior Engineering</div>
                </div>
            </div>
        </footer>
    </main>
    <!-- Panneau latéral de détails (overlay) masqué par défaut -->
    <aside id="detail-panel" class="detail-panel" aria-hidden="true" style="position:fixed;top:0;right:0;height:100vh;width:420px;transform:translateX(100%);background:#eceff1;z-index:110;display:flex;flex-direction:column;">
        <div class="detail-panel-header">
            <h2 class="detail-panel-title">Détails du prospect</h2>
            <button type="button" id="detail-close" class="detail-close" aria-label="Fermer">×</button>
        </div>
        <div class="detail-panel-body">
            <!--
                Formulaire de consultation/édition (verrouillé par défaut)
                - Bouton "Modifier" déverrouille et affiche les contrôles d'ajout/suppression de contacts
                - Bouton "Enregistrer" soumet les changements via POST { action:'update', id, prospect:{...}, contacts:[...] }
                - Bouton "Annuler" rétablit les valeurs et reverrouille
                Rôle:
                - Afficher tous les champs d'un prospect + ses contacts.
                - Passer en mode édition sur clic "Modifier" (les champs deviennent actifs et on peut ajouter/supprimer des contacts).
                - Soumettre les modifications via POST JSON vers scripts/prospects_api.php (action 'update').

                Contacts (#detail-contacts): chaque .contact-row produit un objet { nom, prenom, email, tel, poste }.
                Politique: remplacement complet (DELETE puis réinsert) côté API 

                Boutons d'action:
                - #detail-edit   : déverrouille le formulaire
                - #detail-save   : soumet et reverrouille si succès
                - #detail-cancel : recharge les valeurs d'origine (GET action=detail) et reverrouille
            -->
            <form id="detail-form">
                <div class="form-grid">
                    <label>Entreprise
                        <input id="detail-entreprise" class="input" type="text" disabled>
                    </label>
                    <label>Secteur
                        <input id="detail-secteur" class="input" type="text" disabled>
                    </label>
                    <label>Adresse
                        <input id="detail-adresse" class="input" type="text" disabled>
                    </label>
                    <label>Site web
                        <input id="detail-site" class="input" type="url" placeholder="https://..." disabled>
                    </label>
                    <label>Statut
                        <select id="detail-status" class="select" disabled></select>
                    </label>
                    <label>Type d'acquisition
                        <select id="detail-acq" class="select" disabled></select>
                    </label>
                    <label>Type 1er contact
                        <select id="detail-tpc" class="select" disabled></select>
                    </label>
                    <label>Chaleur
                        <select id="detail-chaleur" class="select" disabled></select>
                    </label>
                    <label>Offre prestation
                        <select id="detail-offre" class="select" disabled></select>
                    </label>
                    <label>Relancé le
                        <input id="detail-relance" class="input" type="date" disabled>
                    </label>
                    <label>Date 1er contact
                        <input id="detail-datepc" class="input" type="date" disabled>
                    </label>
                    <label>Chef de projet
                        <select id="detail-chef" class="select" disabled>
                            <option value="">Choisir un chef de projet...</option>
                        </select>
                    </label>
                    <label>Commentaire
                        <textarea id="detail-comment" class="input" rows="3" disabled></textarea>
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
                    <div id="detail-contacts" class="contacts-list"></div>
                    <button type="button" id="detail-add-contact" class="btn" style="display:none">Ajouter un contact</button>
                </div>
                <div style="margin-top:1rem; display:flex; gap:.5rem; justify-content:flex-end;">
                    <button type="button" id="detail-edit" class="btn">Modifier</button>
                    <button type="submit" id="detail-save" class="btn btn-primary" style="display:none">Enregistrer</button>
                    <button type="button" id="detail-cancel" class="btn btn-ghost" style="display:none">Annuler</button>
                    <button type="button" id="detail-delete" class="btn btn-danger" style="margin-left:auto">Supprimer ce prospect</button>
                </div>
            </form>
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
                    <label>Chef de projet
                        <select id="add-chef" class="select" required>
                            <option value="">Choisir un chef de projet...</option>
                        </select>
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
    
    <script src="scripts/prospects.js"></script>
</body>
</html>