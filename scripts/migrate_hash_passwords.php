<?php
/**
 * Script de migration pour convertir les mots de passe en clair
 * vers des hash sécurisés (PASSWORD_DEFAULT).
 *
 * Utilisation temporaire : accéder via navigateur ou CLI.
 * Sécuriser l'accès (supprimer le fichier après exécution).
 */
session_start();
header('Content-Type: text/plain; charset=utf-8');

// Optionnel : restreindre l'accès aux admins connectés
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'ADMIN') {
    http_response_code(403);
    echo "Accès refusé. Connectez-vous en tant qu'ADMIN avant migration.";
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
    echo "Erreur connexion base.";
    exit;
}

$stmt = $pdo->query('SELECT id, password FROM users');
$users = $stmt->fetchAll();
$updated = 0; $skipped = 0; $already = 0;

foreach ($users as $u) {
    $id = (int)$u['id'];
    $pwd = (string)$u['password'];

    // Détection basique d'un hash: commence par $2y$, $2a$, $argon2i$, $argon2id$
    if (preg_match('/^(\$2[ayb]\$|\$argon2id\$|\$argon2i\$)/', $pwd)) {
        $already++;
        continue; // déjà hashé
    }
    // Si vide on ignore
    if ($pwd === '') { $skipped++; continue; }

    $newHash = password_hash($pwd, PASSWORD_DEFAULT);
    $up = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    $up->execute([$newHash, $id]);
    $updated++;
}

echo "Migration terminée\n";
echo "Total comptes: " . count($users) . "\n";
echo "Déjà hashés: $already\n";
echo "Convertis: $updated\n";
echo "Ignorés (vides): $skipped\n";
echo "IMPORTANT: Supprimez ce fichier une fois la migration effectuée.";
?>