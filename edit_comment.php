<?php
require_once 'db.php';
require_once 'auth_middleware.php';
require_auth();

$comment_id = intval($_GET['id'] ?? 0);
$errors = [];

$stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ?");
$stmt->execute([$comment_id]);
$comment = $stmt->fetch();

if (!$comment || $comment['user_id'] != $_SESSION['user_id']) {
    die("Unauthorized access or comment not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');
    if (empty($content)) {
        $errors[] = "Comment content cannot be empty.";
    } else {
        try {
            $pdo->beginTransaction();

            $update_stmt = $pdo->prepare("UPDATE comments SET content = ?, is_edited = 1 WHERE id = ? AND user_id = ?");
            $update_stmt->execute([$content, $comment_id, $_SESSION['user_id']]);

            $pdo->commit();

            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Failed to update comment: " . $e->getMessage();
        }
    }
}
?>
Edit Comment

<?php if (count($errors) > 0): ?>
    <?php foreach ($errors as $error): ?>
        Error: <?php echo htmlspecialchars($error); ?>
    <?php endforeach; ?>
<?php endif; ?>

<form method="POST" action="edit_comment.php?id=<?php echo $comment_id; ?>">
    <input type="text" name="content" value="<?php echo htmlspecialchars($comment['content']); ?>" required>
    <button type="submit">Update Comment</button>
    <a href="index.php">Cancel</a>
</form>