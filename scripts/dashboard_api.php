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
    }

    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Action inconnue']);
?>
