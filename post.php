<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'db.php';

$post_id = intval($_GET['id'] ?? 0);

if ($post_id <= 0) {
    header("Location: index.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_comment') {
    $content = trim($_POST['content'] ?? '');

    if (empty($content)) {
        $errors[] = 'Comment content cannot be empty.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $post_id, $content]);
            header("Location: post.php?id=" . $post_id);
            exit;
        } catch (Exception $e) {
            $errors[] = 'Failed to post comment. Please try again.';
        }
    }
}

$post_stmt = $pdo->prepare("
    SELECT posts.*, users.name AS author_name, users.email AS author_email 
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    WHERE posts.id = ?
");
$post_stmt->execute([$post_id]);
$post = $post_stmt->fetch();

if (!$post) {
    die("Post not found. <a href='index.php'>Back to Home</a>");
}

$comments_stmt = $pdo->prepare("
    SELECT comments.*, users.name AS author_name, users.email AS author_email 
    FROM comments 
    JOIN users ON comments.user_id = users.id 
    WHERE comments.post_id = ? 
    ORDER BY comments.created_at ASC
");
$comments_stmt->execute([$post_id]);
$comments = $comments_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($post['title']); ?> - Blog Site</title>
</head>
<body>
    <header>
        <p>
            <a href="index.php">&larr; Back to Feed</a> | 
            Logged in as: <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email']); ?></strong> | 
            <a href="logout.php">Logout</a>
        </p>
    </header>
    <hr>

    <article>
        <h2><?php echo htmlspecialchars($post['title']); ?></h2>
        <p>
            By: <strong><?php echo htmlspecialchars($post['author_name'] ?? $post['author_email']); ?></strong> | 
            Posted on: <?php echo $post['created_at']; ?>
            <?php if (!empty($post['updated_at'])): ?>
                <em>(edited)</em>
            <?php endif; ?>
        </p>
        <div style="font-size: 1.1em; line-height: 1.5; margin: 15px 0;">
            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
        </div>

        <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
            <p>
                <a href="edit_post.php?id=<?php echo $post['id']; ?>">Edit Post</a> | 
                <a href="delete_post.php?id=<?php echo $post['id']; ?>" onclick="return confirm('Delete this post?');" style="color: red;">Delete Post</a>
            </p>
        <?php endif; ?>
    </article>

    <hr>
    <h3>Comments</h3>

    <?php if (!empty($errors)): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" action="post.php?id=<?php echo $post_id; ?>">
        <input type="hidden" name="action" value="create_comment">
        <p>
            <textarea name="content" rows="3" cols="45" placeholder="Write a comment..." required></textarea><br>
            <button type="submit">Submit Comment</button>
        </p>
    </form>

    <?php if (empty($comments)): ?>
        <p><em>No comments yet.</em></p>
    <?php else: ?>
        <ul style="padding-left: 20px;">
            <?php foreach ($comments as $comment): ?>
                <li style="margin-bottom: 12px;">
                    <strong><?php echo htmlspecialchars($comment['author_name'] ?? $comment['author_email']); ?></strong>: 
                    <?php echo htmlspecialchars($comment['content']); ?>
                    <br>
                    <small style="color: #666;">
                        <?php echo $comment['created_at']; ?>
                        <?php if (!empty($comment['updated_at'])): ?>
                            <em>(edited)</em>
                        <?php endif; ?>
                    </small>
                    <?php if ($comment['user_id'] == $_SESSION['user_id']): ?>
                        <br>
                        <small>
                            <a href="edit_comment.php?id=<?php echo $comment['id']; ?>">Edit</a> | 
                            <a href="delete_comment.php?id=<?php echo $comment['id']; ?>" onclick="return confirm('Delete this comment?');" style="color: red;">Delete</a>
                        </small>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>
</html>
