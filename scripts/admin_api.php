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

    // POST: create/update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Récupère le corps JSON et l'action demandée
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $input['action'] ?? '';

        if ($action === 'create') {
            // Extraction et normalisation des champs utilisateur
            $u = $input['user'] ?? [];
            $prenom = trim((string)($u['prenom'] ?? ''));
            $nom = trim((string)($u['nom'] ?? ''));
            $email = trim((string)($u['email'] ?? ''));
            $role = trim((string)($u['role'] ?? ''));

            // Validations de base: champs requis
            if ($prenom === '' || $nom === '' || $email === '' || $role === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Champs requis manquants']);
                exit;
            }
            // Validation de l'email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Email invalide']);
                exit;
            }
            // Validation du rôle
            if (!in_array($role, ['admin', 'user'], true)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Rôle invalide']);
                exit;
            }

            // Vérifie l'unicité de l'email (empêche les doublons)
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Email déjà utilisé']);
                exit;
            }

            // Génère un mot de passe (affiché à l'admin) puis hashage avant stockage
            $tempPassword = bin2hex(random_bytes(6)); // 12 caractères hexadécimaux
            $hashed = password_hash($tempPassword, PASSWORD_DEFAULT);

            // Insertion du nouvel utilisateur (mot de passe hashé)
            $stmt = $pdo->prepare('INSERT INTO users (prenom, nom, email, role, password) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$prenom, $nom, $email, $role, $hashed]);
            $newId = (int)$pdo->lastInsertId(); // Récupère l'ID auto-incrémenté

            // Retourne l'ID et le mot de passe au client (admin.js l'affichera)
            echo json_encode(['success' => true, 'id' => $newId, 'temp_password' => $tempPassword]);
            exit;
        }

        if ($action === 'update') {
            // Récupération de l'ID et des champs à mettre à jour
            $id = isset($input['id']) ? (int)$input['id'] : 0;
            $u = $input['user'] ?? [];

            // ID requis et valide
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID invalide']);
                exit;
            }

            // Normalisation des champs
            $prenom = trim((string)($u['prenom'] ?? ''));
            $nom = trim((string)($u['nom'] ?? ''));
            $email = trim((string)($u['email'] ?? ''));
            $role = trim((string)($u['role'] ?? ''));

            // Validations de base
            if ($prenom === '' || $nom === '' || $email === '' || $role === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Champs requis manquants']);
                exit;
            }
            // Email valide
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Email invalide']);
                exit;
            }
            // Rôle valide
            if (!in_array($role, ['admin', 'user'], true)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Rôle invalide']);
                exit;
            }

            // Mise à jour des champs
            $stmt = $pdo->prepare('UPDATE users SET prenom = ?, nom = ?, email = ?, role = ? WHERE id = ?');
            $stmt->execute([$prenom, $nom, $email, $role, $id]);

            // Accusé de réception
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'delete') {
            // Suppression d'un utilisateur par ID
            $id = isset($input['id']) ? (int)$input['id'] : 0;
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID invalide']);
                exit;
            }
            // Empêche la suppression de son propre compte (sécurité minimale)
            if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Impossible de supprimer votre propre compte']);
                exit;
            }
            try {
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                $stmt->execute([$id]);
            } catch (PDOException $e) {
                // Erreur d'intégrité référentielle probable (FK)
                if ($e->getCode() === '23000') {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'error' => 'Suppression impossible: utilisateur référencé']);
                    exit;
                }
                throw $e;
            }
            echo json_encode(['success' => true]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Action inconnue']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode invalide']);
?>