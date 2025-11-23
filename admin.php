<?php
    /**
    * Page admin réservé aux comptes dont $_SESSION['user_role'] === 'admin'
    *  permettre à un administrateur de consulter la liste des utilisateurs
    *  (ADMIN et USER), de sélectionner un utilisateur et de modifier ses
    *  informations de base (prenom, nom, email, role) via un formulaire
    *  verrouillé par défaut qui s'ouvre en édition après clic sur
    *  "Modifier"
    *
    * Données & API:
    * - Chargement de la liste et du détail via scripts/users_api.php (GET)
    * - Mise à jour via scripts/users_api.php (POST JSON action=update)
    */
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: connexion.html');
        exit;
    }
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'ADMIN') {
        header('Location: prospects.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Administration des utilisateurs</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@400;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="style/prospects.css">
        <style>
            /* Ajustements légers pour le centrage du contenu */
            .admin-wrap {
                max-width: 760px;
                margin: 0 auto;
            }
        </style>
        <script>
            // Empêche le flash d'un contenu non stylé si JS est lent
            document.documentElement.classList.add('js-enabled');
        </script>
    </head>

    <body>
        <!-- Barre de navigation identique à prospects.php -->
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
                        <li><a href="prospects.php">Recherche de prospects</a></li>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'ADMIN'): ?>
                            <li><a href="admin.php" class="active">Page administrateur</a></li>
                        <?php endif; ?>
                        <li><a href="scripts/logout.php">Se déconnecter</a></li>
                    </ul>
                </div>
                <div class="nav-right"></div>
            </div>
        </nav>

        <main class="page">
            <h1>Administration des utilisateurs</h1>

            <!--
                Zone principale d'administration
                - Select #user-select: liste des utilisateurs chargée par admin.js
                - Bouton #reload-users: force un rechargement de la liste
                - Formulaire #user-form: champs de l'utilisateur sélectionné (readonly par défaut)
            -->
            <section class="card admin-wrap" style="padding:1rem;">
                <div style="display:flex; gap:.75rem; align-items:flex-end; flex-wrap:wrap;">
                    <label style="flex:1; min-width:220px;">
                        Utilisateur
                        <select id="user-select" class="select">
                            <option value="">Choisir un utilisateur...</option>
                        </select>
                    </label>
                    <div style="display:flex; gap:.5rem;">
                        <button id="reload-users" type="button" class="btn" title="Recharger la liste des utilisateurs">Rafraîchir</button>
                        <button id="user-add" type="button" class="btn btn-primary" title="Créer un nouvel utilisateur">Ajouter</button>
                    </div>
                </div>

                <form id="user-form" style="margin-top:1rem;">
                    <div class="form-grid">
                        <label>Prénom
                            <input id="user-prenom" class="input" type="text">
                        </label>
                        <label>Nom
                            <input id="user-nom" class="input" type="text">
                        </label>
                        <label>Email
                            <input id="user-email" class="input" type="email">
                        </label>
                        <label>Rôle
                            <select id="user-role" class="select">
                                <option value="user">USER</option>
                                <option value="admin">ADMIN</option>
                            </select>
                        </label>
                    </div>
                    <div style="margin-top:1rem; display:flex; gap:.5rem; justify-content:flex-end;">
                        <button type="button" id="user-delete" class="btn btn-danger">Supprimer</button>
                        <button type="button" id="user-edit" class="btn">Modifier</button>
                        <button type="submit" id="user-save" class="btn btn-primary" style="display:none">Enregistrer</button>
                        <button type="button" id="user-cancel" class="btn btn-ghost" style="display:none">Annuler</button>
                    </div>
                </form>
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

        <script src="scripts/admin.js"></script>
    </body>
</html>