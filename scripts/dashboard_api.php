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
    }

    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Action inconnue']);
?>
