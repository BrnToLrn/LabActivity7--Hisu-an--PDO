<?php
require_once 'db.php';
require_once 'auth_middleware.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$post_id = intval($_POST['id'] ?? 0);

if ($post_id <= 0) {
    http_response_code(400);
    exit('Invalid post.');
}

try {
    $pdo->beginTransaction();

    $delete_comments = $pdo->prepare('DELETE FROM comments WHERE post_id = ?');
    $delete_comments->execute([$post_id]);

    $delete_post = $pdo->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');
    $delete_post->execute([$post_id, $_SESSION['user_id']]);

    if ($delete_post->rowCount() !== 1) {
        $pdo->rollBack();
        http_response_code(404);
        exit('Unauthorized access or post not found.');
    }

    $pdo->commit();
    header('Location: index.php');
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    exit('Failed to delete post.');
}