<?php
    /**
     * API Prospects (JSON)
     *
     * Rôle: fournir une liste paginée de prospects avec recherche globale, filtre par champ,
     * agrégation des contacts et informations du chef de projet.
     *
     * Points importants:
     * - Auth obligatoire via session; sinon 401 + JSON { success:false, error }.
     * - Entrées (GET):
     *   - page (int>=1)               : numéro de page (1 par défaut)
     *   - pageSize (1..100)          : taille page (20 par défaut)
     *   - q (string)                 : recherche globale (LIKE sur plusieurs colonnes)
     *   - filter_field, filter_value : filtre ciblé (whitelist de champs)
     * - Sécurité: whitelist des champs filtrables + requêtes préparées (placeholders positionnels).
     * - Dates: recherche/filtre en affichage DD-MM-YYYY (DATE_FORMAT dans SQL) pour correspondre au front.
     * - Agrégations contacts: GROUP_CONCAT DISTINCT avec '\n' pour affichage multi-lignes côté client.
     * - Pagination: LIMIT ? OFFSET ? calculé à partir de page/pageSize.
     */
    
    // démarre la session et force la réponse JSON API en utf-8
    session_start();
    header('Content-Type: application/json; charset=utf-8');

    // refuse l'accès si l'utilisateur n'est pas authentifié
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Non authentifié']);
        exit;
    }

    // Si requête POST: création/suppression de prospect
    /**
     * Actions POST supportées
     * - create: crée un prospect + 0..n contacts (transactionnel)
     *   Body: { action:'create', prospect:{...}, contacts:[...] }
     *   Réponse: { success:true, id }
     * - delete: supprime un prospect et ses contacts liés (transactionnel)
     *   Body: { action:'delete', id:number }
     *   Réponse: { success:true } ou { success:false, error }
     */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode(['success'=>false,'error'=>'JSON invalide']);
            exit;
        }
        $action = $input['action'] ?? '';

        if ($action === 'delete') {
            // Suppression transactionnelle: contacts -> prospect
            $id = isset($input['id']) ? (int)$input['id'] : 0;
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success'=>false,'error'=>'ID invalide']);
                exit;
            }
            try {
                $pdo = new PDO(
                    'mysql:host=localhost;port=3306;dbname=CYJE;charset=utf8mb4',
                    'root',
                    '',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
                $pdo->beginTransaction();
                // Supprime d'abord les contacts liés (au cas où pas de cascade)
                $pdo->prepare('DELETE FROM contact WHERE prospect_id = ?')->execute([$id]);
                // Puis le prospect
                $del = $pdo->prepare('DELETE FROM prospect WHERE id = ?');
                $del->execute([$id]);
                if ($del->rowCount() === 0) {
                    $pdo->rollBack();
                    http_response_code(404);
                    echo json_encode(['success'=>false,'error'=>'Prospect introuvable']);
                    exit;
                }
                $pdo->commit();
                echo json_encode(['success'=>true]);
                exit;
            } catch (Throwable $e) {
                if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
                http_response_code(500);
                echo json_encode(['success'=>false,'error'=>'Erreur serveur']);
                exit;
            }
        } elseif ($action === 'create') {
            $p = $input['prospect'] ?? [];
            $contacts = $input['contacts'] ?? [];

            // Normalisation champs (trim)
            $entreprise = trim($p['entreprise'] ?? '');
            $secteur = trim($p['secteur'] ?? '');
            $adresse = trim($p['adresse_entreprise'] ?? '');
            $site = trim($p['site_web_entreprise'] ?? '');
            $status = trim($p['status_prospect'] ?? '');
            $acq = trim($p['type_acquisition'] ?? '');
            $tpc = trim($p['type_premier_contact'] ?? '');
            $chaleur = trim($p['chaleur'] ?? '');
            $offre = trim($p['offre_prestation'] ?? '');
            $relance = trim($p['relance_le'] ?? ''); // attendu YYYY-MM-DD
            $datepc = trim($p['date_premier_contact'] ?? ''); // attendu YYYY-MM-DD
            $chefId = $p['chef_de_projet_id'] ?? null;
            $comment = trim($p['commentaire'] ?? '');

            if ($entreprise === '') {
                http_response_code(400);
                echo json_encode(['success'=>false,'error'=>'Le champ entreprise est requis']);
                exit;
            }
            if (!is_int($chefId)) {
                http_response_code(400);
                echo json_encode(['success'=>false,'error'=>'chef_de_projet_id invalide']);
                exit;
            }

            // Enums autorisés (doivent être cohérents avec le schéma SQL)
            $enumStatus = ['A contacter','Contacté','A rappeler','Relancé','RDV','PC','Signé','PC refusée','Perdu'];
            $enumAcq = ['DE',"Appel d'offre",'Web crawling','Porte à porte','IRL','Fidélisation','BaNCO','Partenariat'];
            $enumTPC = ['Porte à porte','Formulaire de contact','Event CY Entreprise','LinkedIn','Mail',"Appel d'offre",'DE','Cold call','Salon'];
            $enumChaleur = ['Froid','Tiède','Chaud'];
            $enumOffre = ['Informatique','Chimie','Biotechnologies','Génie civil'];
            $checkEnum = function($v,$list){ return $v==='' || in_array($v,$list,true); };
            if (!$checkEnum($status,$enumStatus) || !$checkEnum($acq,$enumAcq) || !$checkEnum($tpc,$enumTPC)
                || !$checkEnum($chaleur,$enumChaleur) || !$checkEnum($offre,$enumOffre)) {
                http_response_code(400);
                echo json_encode(['success'=>false,'error'=>'Valeur ENUM invalide']);
                exit;
            }
            $checkDate = function($d){ return $d==='' || preg_match('/^\d{4}-\d{2}-\d{2}$/',$d); };
            if (!$checkDate($relance) || !$checkDate($datepc)){
                http_response_code(400);
                echo json_encode(['success'=>false,'error'=>'Format de date invalide (YYYY-MM-DD)']);
                exit;
            }

            try {
                $pdo = new PDO(
                    'mysql:host=localhost;port=3306;dbname=CYJE;charset=utf8mb4',
                    'root',
                    '',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );

                // Valide l'existence du chef de projet
                $chk = $pdo->prepare('SELECT id FROM users WHERE id = ?');
                $chk->execute([$chefId]);
                if (!$chk->fetchColumn()) {
                    http_response_code(400);
                    echo json_encode(['success'=>false,'error'=>'Chef de projet inexistant']);
                    exit;
                }

                $pdo->beginTransaction();
                $stmt = $pdo->prepare('INSERT INTO prospect (
                    entreprise, secteur, adresse_entreprise, site_web_entreprise,
                    status_prospect, type_acquisition, type_premier_contact, chaleur, offre_prestation,
                    relance_le, date_premier_contact, chef_de_projet_id, commentaire
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([
                    $entreprise ?: null,
                    $secteur ?: null,
                    $adresse ?: null,
                    $site ?: null,
                    $status ?: null,
                    $acq ?: null,
                    $tpc ?: null,
                    $chaleur ?: null,
                    $offre ?: null,
                    $relance !== '' ? $relance : null,
                    $datepc !== '' ? $datepc : null,
                    $chefId,
                    $comment ?: null,
                ]);
                $newId = (int)$pdo->lastInsertId();

                if (is_array($contacts)){
                    $cins = $pdo->prepare('INSERT INTO contact (prospect_id, nom, prenom, email, tel, poste) VALUES (?,?,?,?,?,?)');
                    foreach ($contacts as $c){
                        $nom = trim($c['nom'] ?? '');
                        $prenom = trim($c['prenom'] ?? '');
                        $email = trim($c['email'] ?? '');
                        $tel = trim($c['tel'] ?? '');
                        $poste = trim($c['poste'] ?? '');
                        if ($nom === '' && $prenom === '' && $email === '' && $tel === '' && $poste === '') continue;
                        $cins->execute([$newId, $nom ?: null, $prenom ?: null, $email ?: null, $tel ?: null, $poste ?: null]);
                    }
                }
                $pdo->commit();

                echo json_encode(['success'=>true,'id'=>$newId]);
                exit;
            } catch (Throwable $e) {
                if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
                http_response_code(500);
                echo json_encode(['success'=>false,'error'=>'Erreur serveur']);
                exit;
            }
        } else {
            http_response_code(400);
            echo json_encode(['success'=>false,'error'=>'Action non supportée']);
            exit;
        }
    }

    // Endpoint GET auxiliaire: liste des utilisateurs (id, nom, prenom)
    if (($_GET['action'] ?? '') === 'users') {
        try {
            $pdo = new PDO(
                'mysql:host=localhost;port=3306;dbname=CYJE;charset=utf8mb4',
                'root',
                '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            $stmt = $pdo->query('SELECT id, nom, prenom FROM users ORDER BY nom, prenom');
            $users = $stmt->fetchAll();
            echo json_encode(['success' => true, 'users' => $users]);
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
            exit;
        }
    }

    // read des paramètres de pagination et de recherche
    // page: au minimum 1; si valeur invalide, on retombe sur 1
    $page = max(1, intval($_GET['page'] ?? 1));
    // pageSize: borné entre 1 et 100; sinon on force 20
    $pageSize = intval($_GET['pageSize'] ?? 20);
    if ($pageSize <= 0 || $pageSize > 100) {
        $pageSize = 20;
    }
    // q: recherche globale (trim pour éviter espaces)
    $q = trim($_GET['q'] ?? '');
    // filter_field / filter_value: filtrage ciblé (si field autorisé)
    $filterField = trim($_GET['filter_field'] ?? '');
    $filterValue = trim($_GET['filter_value'] ?? '');

    // whitelist des champs autorisés pour le filtrage
    // évite l'injection SQL via le nom de colonne
    $allowedFields = [
        'id', 'entreprise', 'secteur', 'adresse_entreprise', 'site_web_entreprise', 'status_prospect',
        'type_acquisition', 'type_premier_contact', 'chaleur', 'offre_prestation', 'date_premier_contact', 'relance_le'
    ];

    // where: accumulateur de fragments SQL (strings)
    // params: valeurs correspondantes pour les placeholders positionnels
    $where = [];
    $params = [];

    if ($q !== '') {
        // Recherche globale (en SQL : cherche dans les tables "prospects" et "contacts")
        // NB: pour les dates on se cale sur l'affichage DD-MM-YYYY pour permettre la recherche par fragments
        $where[] = "(p.entreprise LIKE ? OR p.secteur LIKE ? OR p.adresse_entreprise LIKE ? OR p.site_web_entreprise LIKE ?
                OR p.status_prospect LIKE ? OR p.type_acquisition LIKE ? OR p.type_premier_contact LIKE ? OR p.chaleur LIKE ?
                OR p.offre_prestation LIKE ? OR p.commentaire LIKE ? OR DATE_FORMAT(p.date_premier_contact, '%d-%m-%Y') LIKE ? OR DATE_FORMAT(p.relance_le, '%d-%m-%Y') LIKE ?
                OR c.nom LIKE ? OR c.prenom LIKE ? OR c.email LIKE ? OR c.tel LIKE ? OR c.poste LIKE ?)";
        // 17 "LIKE ?" ci dessus
        // donc on pousse 17 fois la même valeur paramétrée (pour filtrer tout ça après)
        for ($i = 0; $i < 17; $i++) {
            $params[] = "%{$q}%";
        }
    }

    // Filtre par champ ciblé:
    // - texte: LIKE sur p.<field>
    // - date: on formate via DATE_FORMAT(..., '%d-%m-%Y') pour rester aligné avec le front qui envoie DD-MM-YYYY
    // Si le champ n'est pas dans la whitelist, le filtre est ignoré
    if ($filterField !== '' && in_array($filterField, $allowedFields, true) && $filterValue !== '') {
        if (in_array($filterField, ['date_premier_contact', 'relance_le'], true)) {
            // Pour les dates, on compare via DATE_FORMAT en DD-MM-YYYY pour être aligné avec le front
            $where[] = "DATE_FORMAT(p.$filterField, '%d-%m-%Y') LIKE ?";
            $params[] = "%{$filterValue}%";
        } else {
            // Pour les autres champs, LIKE direct
            $where[] = "p.$filterField LIKE ?";
            $params[] = "%{$filterValue}%";
        }
    }

    // Concatène toutes les clauses WHERE construites dynamiquement
    $wSql = '';
    if (!empty($where)) {
        $wSql = 'WHERE ' . implode(' AND ', $where);
    }

    // Calcul de l'offset pour LIMIT/OFFSET (permet d'afficher 20 par 20 résultats dans le tableau)
    // le SQL nous retournera donc 20 résultats max par requete
    // exemple : 
    // on est a la page 1, et le max de lignes est 20 => OFFSET = (1-1) * 20 = 0 (on commence a lire au début, on saute les 0 premiers résultats)
    $offset = ($page - 1) * $pageSize;

    try {
        // Connexion PDO à MySQL (mode exception + fetch assoc par défaut)
        $pdo = new PDO(
            'mysql:host=localhost;port=3306;dbname=CYJE;charset=utf8mb4',
            'root',
            '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        // compte le total de prospects distincts correspondant aux filtres/recherches
        // COUNT(DISTINCT p.id) est nécessaire car on LEFT JOIN contact: un prospect avec plusieurs contacts serait sinon compté plusieurs fois
        $countSql = "SELECT COUNT(DISTINCT p.id) AS c
                    FROM prospect p
                    LEFT JOIN contact c ON c.prospect_id = p.id
                    $wSql";
        $countStmt = $pdo->prepare($countSql);
        // on réutilise $params car $wSql vient des mêmes conditions que la requête principale
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Récupération des données paginées:
        // - Champs principaux de prospect
        // - Contacts agrégés via GROUP_CONCAT (séparateur "\n"), pour affichage multi-lignes côté client
        // - Chef de projet: priorise "prenom nom" si existants, sinon email
        // - Tri: relance la plus récente d'abord, puis id décroissant
        $sql = "SELECT
                p.id,
                p.entreprise,
                p.secteur,
                p.status_prospect,
                p.relance_le,
                p.type_acquisition,
                p.date_premier_contact,
                p.type_premier_contact,
                p.chaleur,
                p.offre_prestation,
                p.adresse_entreprise,
                p.site_web_entreprise,
                p.commentaire,
                    GROUP_CONCAT(DISTINCT c.nom ORDER BY c.nom SEPARATOR '\n') AS contacts_noms,
                    GROUP_CONCAT(DISTINCT c.prenom ORDER BY c.prenom SEPARATOR '\n') AS contacts_prenoms,
                    GROUP_CONCAT(DISTINCT c.email ORDER BY c.email SEPARATOR '\n') AS contacts_emails,
                    GROUP_CONCAT(DISTINCT c.tel ORDER BY c.tel SEPARATOR '\n') AS contacts_tels,
                    GROUP_CONCAT(DISTINCT c.poste ORDER BY c.poste SEPARATOR '\n') AS contacts_postes,
                    CASE
                        WHEN TRIM(CONCAT(COALESCE(u.prenom, ''), ' ', COALESCE(u.nom, ''))) <> ''
                            THEN TRIM(CONCAT(COALESCE(u.prenom, ''), ' ', COALESCE(u.nom, '')))
                        ELSE COALESCE(u.email, '')
                    END AS chef_projet
                FROM prospect p
                LEFT JOIN contact c ON c.prospect_id = p.id
                LEFT JOIN users u ON u.id = p.chef_de_projet_id
                $wSql
                GROUP BY p.id
                ORDER BY p.relance_le DESC, p.id DESC
                LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);

        // réapplique les paramètres WHERE (placeholders positionnels), puis LIMIT et OFFSET
        $i = 1;
        foreach ($params as $p) {
            // Tous les filtres/recherches utilisent LIKE: bind en string
            $stmt->bindValue($i++, $p, PDO::PARAM_STR);
        }
        // Nombre de lignes à renvoyer (int)
        $stmt->bindValue($i++, (int) $pageSize, PDO::PARAM_INT);
        // Décalage initial (int)
        $stmt->bindValue($i++, (int) $offset, PDO::PARAM_INT);

        $stmt->execute();
        $rows = $stmt->fetchAll();

        // Réponse d'API JSON standard
        echo json_encode([
            // Indique la réussite côté client
            'success' => true,
            // Données (tableau d'objets)
            'data' => $rows,
            // Total d'éléments correspondant aux filtres/recherche
            'total' => $total,
            // Numéro de page renvoyée
            'page' => $page,
            // Taille de page utilisée
            'pageSize' => $pageSize,
            // Nombre total de pages (arrondi supérieur)
            'totalPages' => (int) ceil($total / $pageSize),
        ]);
    } catch (Throwable $e) {
        // En production, on ne divulgue pas le détail de l'exception pour des raisons de sécurité
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
    }
?>