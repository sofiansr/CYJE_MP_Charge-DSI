<?php
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
        <title>Tableau de bord</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@400;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="style/prospects.css">
        <link rel="stylesheet" href="style/dashboard.css">
        <script>document.documentElement.classList.add('js-enabled');</script>
    </head>
    <body>
        <nav class="app-nav">
            <div class="nav-inner">
                <div class="nav-left">
                    <a href="dashboard.php" aria-label="Accueil">
                        <img src="assets/logo_cyje.png" class="nav-logo" alt="Logo CYJE">
                    </a>
                </div>
                <div class="nav-center">
                    <ul class="nav-links">
                        <li><a href="dashboard.php" class="active">Tableau de bord</a></li>
                        <li><a href="prospects.php">Recherche de prospects</a></li>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'ADMIN'): ?>
                            <li><a href="admin.php">Page administrateur</a></li>
                        <?php endif; ?>
                        <li><a href="scripts/logout.php">Se déconnecter</a></li>
                    </ul>
                </div>
                <div class="nav-right"></div>
            </div>
        </nav>

        <main class="page">
            <h1>Tableau de bord</h1>
            <div class="center-wrap">
                <section class="card stat-card" aria-label="Carte statistiques">
                    <div class="subcards-grid" id="stats-grid">
                        <!-- Sous-carte statistique: nombre total de prospects -->
                            <div class="subcard stat" id="subcard-total-prospects">
                                <div class="stat-inner">
                                    <p class="stat-number" id="total-prospects-value">--</p>
                                    <p class="stat-label">Prospects total</p>
                                </div>
                            </div>
                            <div class="subcard stat" id="subcard-prospects-contactes-mois">
                                <div class="stat-inner">
                                    <p class="stat-number" id="prospects-contactes-mois-value">--</p>
                                    <p class="stat-label">Prospects contactés ce mois</p>
                                </div>
                            </div>
                            <div class="subcard stat" id="subcard-prospects-contacte-par-user">
                                <div class="stat-inner">
                                    <p class="stat-number" id="prospects-contacte-par-user-value">--</p>
                                    <p class="stat-label">Prospects contacté / utilisateur en moyenne</p>
                                </div>
                            </div>
                            <div class="subcard chart" id="subcard-tpc">
                                <div class="chart-inner">
                                    <p class="subcard-title">Type 1er contact</p>
                                    <div class="chart-wrapper chart-wrapper--wide">
                                        <canvas id="chart-tpc" aria-label="Répartition par type de premier contact" role="img"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="subcard chart" id="subcard-chaleur">
                                <div class="chart-inner">
                                    <p class="subcard-title">Répartition chaleur</p>
                                    <div class="chart-wrapper">
                                        <canvas id="chart-chaleur" aria-label="Répartition Froid / Tiède / Chaud" role="img"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="subcard chart" id="subcard-offre">
                                <div class="chart-inner">
                                    <p class="subcard-title">Offre prestation</p>
                                    <div class="chart-wrapper">
                                        <canvas id="chart-offre" aria-label="Répartition par offre de prestation" role="img"></canvas>
                                    </div>
                                </div>
                            </div>
                    </div>
                </section>
            </div>


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

        <script src="https://cdn.jsdelivr.net/npm/chart.js" crossorigin="anonymous"></script>
        <script src="scripts/home.js"></script>
    </body>
</html>
