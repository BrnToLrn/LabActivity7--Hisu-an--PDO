<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require 'db.php';

$post_id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

if ($post_id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$post_id, $_SESSION['user_id']]);
    } catch (Exception $e) {
        die("Failed to delete post: " . $e->getMessage());
    }
}

header("Location: index.php");
exit;