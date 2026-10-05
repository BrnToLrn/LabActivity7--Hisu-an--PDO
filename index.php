<?php
require_once 'db.php';
require_once 'auth_middleware.php';
require_auth();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_post') {
    $content = trim($_POST['content'] ?? '');
    if (empty($content)) {
        $errors[] = "Post content cannot be empty.";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $content]);
            $pdo->commit();

            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Failed to create post: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_comment') {
    $post_id = intval($_POST['post_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    if (empty($content) || $post_id <= 0) {
        $errors[] = "Comment content cannot be empty.";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$post_id, $_SESSION['user_id'], $content]);
            $pdo->commit();

            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Failed to add comment: " . $e->getMessage();
        }
    }
}

$posts_stmt = $pdo->query("
    SELECT posts.*, users.email AS author_email 
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    ORDER BY posts.created_at DESC
");
$posts = $posts_stmt->fetchAll();
?>
Logged in as: <?php echo htmlspecialchars($_SESSION['user_email']); ?> | <a href="logout.php">Logout</a>

<?php if (count($errors) > 0): ?>
    <?php foreach ($errors as $error): ?>
        Error: <?php echo htmlspecialchars($error); ?>
    <?php endforeach; ?>
<?php endif; ?>

<form method="POST" action="index.php">
    <input type="hidden" name="action" value="create_post">
    Post Content: <textarea name="content" required></textarea>
    <button type="submit">Publish</button>
</form>

<?php if (empty($posts)): ?>
    No posts yet.
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        Author: <?php echo htmlspecialchars($post['author_email']); ?>
        Time: <?php echo $post['created_at']; ?>
        <?php if ($post['is_edited']): ?>
            [Edited]
        <?php endif; ?>
        Content: <?php echo htmlspecialchars($post['content']); ?>

        <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
            <a href="edit_post.php?id=<?php echo $post['id']; ?>">Edit Post</a>
            <form method="POST" action="delete_post.php">
                <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                <button type="submit">Delete Post</button>
            </form>
        <?php endif; ?>

        <?php
        $comments_stmt = $pdo->prepare("
            SELECT comments.*, users.email AS author_email 
            FROM comments 
            JOIN users ON comments.user_id = users.id 
            WHERE comments.post_id = ? 
            ORDER BY comments.created_at ASC
        ");
        $comments_stmt->execute([$post['id']]);
        $comments = $comments_stmt->fetchAll();
        ?>

        <?php foreach ($comments as $comment): ?>
            Comment Author: <?php echo htmlspecialchars($comment['author_email']); ?>
            Comment Content: <?php echo htmlspecialchars($comment['content']); ?>
            Comment Time: <?php echo $comment['created_at']; ?>
            <?php if ($comment['is_edited']): ?>
                [Edited]
            <?php endif; ?>
            <?php if ($comment['user_id'] == $_SESSION['user_id']): ?>
                <a href="edit_comment.php?id=<?php echo $comment['id']; ?>">Edit Comment</a>
                <form method="POST" action="delete_comment.php">
                    <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                    <button type="submit">Delete Comment</button>
                </form>
            <?php endif; ?>
        <?php endforeach; ?>

        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="create_comment">
            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
            Comment: <input type="text" name="content" required>
            <button type="submit">Comment</button>
        </form>
    <?php endforeach; ?>
<?php endif; ?>