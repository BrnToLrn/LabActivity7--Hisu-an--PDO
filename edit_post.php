<?php
require_once 'db.php';
require_once 'auth_middleware.php';
require_auth();

$post_id = intval($_GET['id'] ?? 0);
$errors = [];

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post || $post['user_id'] != $_SESSION['user_id']) {
    die("Unauthorized access or post not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');
    if (empty($content)) {
        $errors[] = "Content cannot be empty.";
    } else {
        try {
            $pdo->beginTransaction();

            $update_stmt = $pdo->prepare("UPDATE posts SET content = ?, is_edited = 1 WHERE id = ? AND user_id = ?");
            $update_stmt->execute([$content, $post_id, $_SESSION['user_id']]);

            $pdo->commit();

            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Failed to update post: " . $e->getMessage();
        }
    }
}
?>
Edit Post

<?php if (count($errors) > 0): ?>
    <?php foreach ($errors as $error): ?>
        Error: <?php echo htmlspecialchars($error); ?>
    <?php endforeach; ?>
<?php endif; ?>

<form method="POST" action="edit_post.php?id=<?php echo $post_id; ?>">
    <textarea name="content" required><?php echo htmlspecialchars($post['content']); ?></textarea>
    <button type="submit">Update Post</button>
    <a href="index.php">Cancel</a>
</form>