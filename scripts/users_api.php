<?php
    /**
     * API admin
     * -------------------------------------------------------------
     * Accès: réservé aux sessions admin (\$_SESSION['user_role'] === 'admin')
     * Objet: fournir les endpoints nécessaires à la page admin pour lister,
     *        consulter et modifier des utilisateurs
     *
     * Endpoints:
     * - GET  users_api.php?action=list
     *     Réponse: { success:true, users:[ { id, prenom, nom, email, role } ] }
     *
     * - GET  users_api.php?action=detail&id=<id>
     *     Réponse: { success:true, user:{ id, prenom, nom, email, role } }
     *     Codes: 400 si id invalide, 404 si introuvable
     *
     * - POST users_api.php (JSON): { action:'update', id:number, user:{ prenom, nom, email, role } }
     *     Réponse: { success:true }
     *     Validations: champs requis, email valide, role ∈ { 'admin','user' }
     *
     * Les erreurs renvoient un code HTTP (400/403/404/405/500) et { success:false, error }.
     */
    session_start();
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'ADMIN') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Accès refusé']);
        exit;
    }

    try {
        $pdo = new PDO(
            'mysql:host=localhost;port=3306;dbname=cyje;charset=utf8mb4',
            'root',
            '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
        exit;
    }

    // GET: list/detail
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'list') {
            $stmt = $pdo->query('SELECT id, prenom, nom, email, role FROM users ORDER BY prenom, nom');
            $users = $stmt->fetchAll();
            echo json_encode(['success' => true, 'users' => $users]);
            exit;
        }

        if ($action === 'detail') {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID invalide']);
                exit;
            }
            $stmt = $pdo->prepare('SELECT id, prenom, nom, email, role FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $u = $stmt->fetch();
            if (!$u) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Utilisateur introuvable']);
                exit;
            }
            echo json_encode(['success' => true, 'user' => $u]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Action inconnue']);
        exit;
    }

    // POST: update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $input['action'] ?? '';

        if ($action !== 'update') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action inconnue']);
            exit;
        }

        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $u = $input['user'] ?? [];

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID invalide']);
            exit;
        }

        $prenom = trim((string)($u['prenom'] ?? ''));
        $nom = trim((string)($u['nom'] ?? ''));
        $email = trim((string)($u['email'] ?? ''));
        $role = trim((string)($u['role'] ?? ''));

        if ($prenom === '' || $nom === '' || $email === '' || $role === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Champs requis manquants']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email invalide']);
            exit;
        }
        if (!in_array($role, ['admin', 'user'], true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Rôle invalide']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE users SET prenom = ?, nom = ?, email = ?, role = ? WHERE id = ?');
        $stmt->execute([$prenom, $nom, $email, $role, $id]);

        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode invalide']);
?>