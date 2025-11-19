<?php
    /**
     * API Statistiques simples
     * Endpoint initial: nombre total de prospects.
     * Usage: GET stats_api.php?action=prospects_total
     * Réponse: { success:true, total:number }
     */
    session_start();
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success'=>false,'error'=>'Non authentifié']);
        exit;
    }

    $action = $_GET['action'] ?? 'prospects_total';

    if ($action === 'prospects_total') {
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
            $stmt = $pdo->query('SELECT COUNT(*) AS c FROM prospect');
            $total = (int) $stmt->fetchColumn();
            echo json_encode(['success'=>true,'total'=>$total]);
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success'=>false,'error'=>'Erreur serveur']);
            exit;
        }
    } elseif ($action === 'prospects_contactes_mois') {
        // Nombre de prospects dont la date de premier contact est dans le mois courant
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
            $sql = "SELECT COUNT(*) FROM prospect WHERE date_premier_contact IS NOT NULL AND YEAR(date_premier_contact)=YEAR(CURRENT_DATE()) AND MONTH(date_premier_contact)=MONTH(CURRENT_DATE())";
            $totalMonth = (int) $pdo->query($sql)->fetchColumn();
            echo json_encode(['success'=>true,'total'=>$totalMonth]);
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success'=>false,'error'=>'Erreur serveur']);
            exit;
        }
    } elseif ($action === 'prospects_contacte_par_user') {
        // Ratio: nombre de prospects avec status "Contacté" divisé par le nombre total d'utilisateurs (admin + user)
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
            $prospectsContacte = (int) $pdo->query("SELECT COUNT(*) FROM prospect WHERE status_prospect='Contacté'")->fetchColumn();
            // Compte tous les utilisateurs dont le rôle (quel que soit la casse) est admin ou user
            $usersTotal = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE LOWER(role) IN ('admin','user')")->fetchColumn();
            $ratio = $usersTotal > 0 ? $prospectsContacte / $usersTotal : 0;
            // Valeur arrondie à 2 décimales pour l'affichage
            echo json_encode([
                'success'=>true,
                'value'=>round($ratio,2),
                'prospects_contacte'=>$prospectsContacte,
                'users_total'=>$usersTotal
            ]);
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success'=>false,'error'=>'Erreur serveur']);
            exit;
        }
    } elseif ($action === 'chaleur_distribution') {
        // Distribution des prospects par chaleur (Froid/Tiède/Chaud)
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
            $stmt = $pdo->query("SELECT chaleur, COUNT(*) AS c FROM prospect WHERE chaleur IN ('Froid','Tiède','Chaud') GROUP BY chaleur");
            $rows = $stmt->fetchAll();
            $dist = ['Froid'=>0,'Tiède'=>0,'Chaud'=>0];
            $total = 0;
            foreach($rows as $r){
                $val = $r['chaleur'];
                $count = (int)$r['c'];
                if(isset($dist[$val])){ $dist[$val] = $count; $total += $count; }
            }
            echo json_encode(['success'=>true,'distribution'=>$dist,'total'=>$total]);
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success'=>false,'error'=>'Erreur serveur']);
            exit;
        }
    } elseif ($action === 'tpc_distribution') {
        // Distribution par type de premier contact (bar chart)
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
            $order = [
                'Porte à porte',
                'Formulaire de contact',
                'Event CY Entreprise',
                'LinkedIn',
                'Mail',
                "Appel d'offre",
                'DE',
                'Cold call',
                'Salon'
            ];
            $base = array_fill_keys($order, 0);
            $stmt = $pdo->query("SELECT type_premier_contact AS tpc, COUNT(*) AS c FROM prospect WHERE type_premier_contact IS NOT NULL GROUP BY type_premier_contact");
            $rows = $stmt->fetchAll();
            $total = 0;
            foreach ($rows as $r) {
                $tpc = $r['tpc'];   // nom du type premier contact
                $count = (int)$r['c'];  // nombre de ce type de premier contact
                if (isset($base[$tpc])) { $base[$tpc] = $count; }
                $total += $count;
            }
            echo json_encode(['success'=>true,'labels'=>array_keys($base),'counts'=>array_values($base),'total'=>$total]); // noms, valeurs, total
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success'=>false,'error'=>'Erreur serveur']);
            exit;
        }
    } elseif ($action === 'offre_distribution') {
        // Distribution par offre de prestation
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
            $order = ['Informatique','Chimie','Biotechnologies','Génie civil'];
            $base = array_fill_keys($order, 0);
            $stmt = $pdo->query("SELECT offre_prestation AS off, COUNT(*) AS c FROM prospect WHERE offre_prestation IS NOT NULL GROUP BY offre_prestation");
            $rows = $stmt->fetchAll();
            $total = 0;
            foreach ($rows as $r) {
                $off = $r['off'];
                $count = (int)$r['c'];
                if (isset($base[$off])) { $base[$off] = $count; $total += $count; }
            }
            echo json_encode(['success'=>true,'distribution'=>$base,'total'=>$total]);
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success'=>false,'error'=>'Erreur serveur']);
            exit;
        }
    } elseif ($action === 'conversion_rate') {
        // Taux de conversion = prospects signés / total prospects
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
            $total = (int)$pdo->query('SELECT COUNT(*) FROM prospect')->fetchColumn();
            $signed = (int)$pdo->query("SELECT COUNT(*) FROM prospect WHERE status_prospect='Signé'")->fetchColumn();
            $rate = $total > 0 ? ($signed / $total) : 0; // ratio 0..1
            echo json_encode([
                'success'=>true,
                'total_prospects'=>$total,
                'signed_prospects'=>$signed,
                'rate'=>$rate,
                'rate_percent'=>round($rate*100,2)
            ]);
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success'=>false,'error'=>'Erreur serveur']);
            exit;
        }
    } elseif ($action === 'status_distribution') {
        // Distribution par status_prospect (bar chart)
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
            $order = [
                'A contacter',
                'Contacté',
                'A rappeler',
                'Relancé',
                'RDV',
                'PC',
                'Signé',
                'PC refusée',
                'Perdu'
            ];
            $base = array_fill_keys($order, 0);
            $stmt = $pdo->query("SELECT status_prospect AS sp, COUNT(*) AS c FROM prospect WHERE status_prospect IS NOT NULL GROUP BY status_prospect");
            $rows = $stmt->fetchAll();
            $total = 0;
            foreach ($rows as $r) {
                $sp = $r['sp'];
                $count = (int)$r['c'];
                if (isset($base[$sp])) { $base[$sp] = $count; }
                $total += $count;
            }
            echo json_encode([
                'success'=>true,
                'labels'=>array_keys($base),
                'counts'=>array_values($base),
                'total'=>$total
            ]);
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success'=>false,'error'=>'Erreur serveur']);
            exit;
        }
    } elseif ($action === 'last_contacted') {
        // Retourne les 5 derniers prospects contactés (date_premier_contact non NULL) triés par date_premier_contact DESC
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
            // $sql = "SELECT entreprise, status_prospect, offre_prestation, relance_le, date_premier_contact FROM prospect WHERE date_premier_contact IS NOT NULL ORDER BY date_premier_contact DESC LIMIT 5";
            $sql = "SELECT entreprise, status_prospect, offre_prestation, relance_le, date_premier_contact FROM prospect WHERE relance_le IS NOT NULL ORDER BY relance_le DESC LIMIT 5";
            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll();
            // Normalise valeurs nulles -> chaîne vide
            $normalized = [];
            foreach ($rows as $r) {
                $normalized[] = [
                    'entreprise' => $r['entreprise'] ?? '',
                    'status_prospect' => $r['status_prospect'] ?? '',
                    'offre_prestation' => $r['offre_prestation'] ?? '',
                    'relance_le' => $r['relance_le'] ?? '',
                    'date_premier_contact' => $r['date_premier_contact'] ?? ''
                ];
            }
            echo json_encode(['success'=>true,'items'=>$normalized]);
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success'=>false,'error'=>'Erreur serveur']);
            exit;
        }
    }

    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Action inconnue']);
?>
