<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require 'db.php';

$comment_id = intval($_GET['id'] ?? 0);
$errors = [];

$stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ?");
$stmt->execute([$comment_id]);
$comment = $stmt->fetch();

if (!$comment || $comment['user_id'] != $_SESSION['user_id']) {
    die("Unauthorized access or comment not found. <a href='index.php'>Back to Feed</a>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');
    if (empty($content)) {
        $errors[] = "Comment content cannot be empty.";
    } else {
        try {
            $update_stmt = $pdo->prepare("UPDATE comments SET content = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
            $update_stmt->execute([$content, $comment_id, $_SESSION['user_id']]);

            header("Location: post.php?id=" . $comment['post_id']);
            exit;
        } catch (Exception $e) {
            $errors[] = "Failed to update comment: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Comment - Blog Site</title>
</head>
<body>
    <h2>Edit Comment</h2>

    <?php if (!empty($errors)): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" action="edit_comment.php?id=<?php echo $comment_id; ?>">
        <p>
            <label>Comment:</label><br>
            <textarea name="content" rows="4" cols="40" required><?php echo htmlspecialchars($comment['content']); ?></textarea>
        </p>
        <button type="submit">Update Comment</button>
        <a href="post.php?id=<?php echo $comment['post_id']; ?>">Cancel</a>
    </form>
</body>
</html>