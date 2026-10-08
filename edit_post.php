<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'db.php';

$post_id = intval($_GET['id'] ?? 0);
$errors = [];

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post || $post['user_id'] != $_SESSION['user_id']) {
    die("Unauthorized access or post not found. <a href='index.php'>Back to Feed</a>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($title)) {
        $errors[] = "Title cannot be empty.";
    }
    if (empty($content)) {
        $errors[] = "Content cannot be empty.";
    }

    if (empty($errors)) {
        try {
            $update_stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
            $update_stmt->execute([$title, $content, $post_id, $_SESSION['user_id']]);

            header("Location: post.php?id=" . $post_id);
            exit;
        } catch (Exception $e) {
            $errors[] = "Failed to update post: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Post - Blog Site</title>
</head>
<body>
    <h2>Edit Post</h2>

    <?php if (!empty($errors)): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" action="edit_post.php?id=<?php echo $post_id; ?>">
        <p>
            <label>Title:</label><br>
            <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required style="width: 320px;">
        </p>
        <p>
            <label>Content:</label><br>
            <textarea name="content" rows="6" cols="45" required><?php echo htmlspecialchars($post['content']); ?></textarea>
        </p>
        <button type="submit">Update Post</button>
        <a href="post.php?id=<?php echo $post_id; ?>">Cancel</a>
    </form>
</body>
</html>