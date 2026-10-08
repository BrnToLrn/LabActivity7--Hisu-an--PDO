<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require 'db.php';

$comment_id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

if ($comment_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT post_id FROM comments WHERE id = ? AND user_id = ?");
        $stmt->execute([$comment_id, $_SESSION['user_id']]);
        $comment = $stmt->fetch();

        if ($comment) {
            $del = $pdo->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
            $del->execute([$comment_id, $_SESSION['user_id']]);

            header("Location: post.php?id=" . $comment['post_id']);
            exit;
        }
    } catch (Exception $e) {
        die("Failed to delete comment: " . $e->getMessage());
    }
}

header("Location: index.php");
exit;