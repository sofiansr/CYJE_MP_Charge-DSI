<?php
    session_start();
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Méthode invalide']);
        exit;
    }

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($email === '' || $password === '') {
        echo json_encode(['success' => false, 'error' => 'Champs requis manquants']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Format email invalide']);
        exit;
    }

    try {
        $pdo = new PDO(
            'mysql:host=localhost;port=3306;dbname=cyje;charset=utf8mb4',
            'root',
            '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, email, password, role, nom, prenom FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    //if (!$user || !password_verify($password, $user['password'])) {
    if (!$user || ($password != $user['password'])) {
        echo json_encode(['success' => false, 'error' => 'Email ou mot de passe incorrect']);
        exit;
    }


    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_nom'] = $user['nom'] ?? '';
    $_SESSION['user_prenom'] = $user['prenom'] ?? '';

    echo json_encode(['success' => true, 'redirect' => 'dashboard.php']);
?>