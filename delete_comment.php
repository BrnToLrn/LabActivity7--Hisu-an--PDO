<?php
require_once 'db.php';
require_once 'auth_middleware.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$comment_id = intval($_POST['id'] ?? 0);

if ($comment_id <= 0) {
    http_response_code(400);
    exit('Invalid comment.');
}

try {
    $delete_comment = $pdo->prepare('DELETE FROM comments WHERE id = ? AND user_id = ?');
    $delete_comment->execute([$comment_id, $_SESSION['user_id']]);

    if ($delete_comment->rowCount() !== 1) {
        http_response_code(404);
        exit('Unauthorized access or comment not found.');
    }

    header('Location: index.php');
    exit;
} catch (Exception $e) {
    http_response_code(500);
    exit('Failed to delete comment.');
}