<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_post') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($title)) {
        $errors[] = 'Post title cannot be empty.';
    }
    if (empty($content)) {
        $errors[] = 'Post content cannot be empty.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $content]);
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $errors[] = 'Failed to create post. Please try again.';
        }
    }
}

$stmt = $pdo->query("
    SELECT posts.*, users.name AS author_name, users.email AS author_email 
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    ORDER BY posts.created_at DESC
");
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home Feed - Blog Site</title>
</head>
<body>
    <header>
        <p>
            Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email']); ?></strong>! 
            | <a href="logout.php">Logout</a>
        </p>
    </header>
    <hr>

    <h2>Create New Blog Post</h2>
    <?php if (!empty($errors)): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" action="index.php">
        <input type="hidden" name="action" value="create_post">
        <p>
            <label>Title:</label><br>
            <input type="text" name="title" required maxlength="255" style="width: 320px;">
        </p>
        <p>
            <label>Content:</label><br>
            <textarea name="content" rows="5" cols="45" required></textarea>
        </p>
        <button type="submit">Publish Post</button>
    </form>

    <hr>
    <h2>News Feed</h2>

    <?php if (empty($posts)): ?>
        <p>No blog posts found.</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div style="border: 1px solid #ccc; padding: 12px; margin-bottom: 20px;">
                <h3>
                    <a href="post.php?id=<?php echo $post['id']; ?>">
                        <?php echo htmlspecialchars($post['title']); ?>
                    </a>
                </h3>
                <p>
                    By: <strong><?php echo htmlspecialchars($post['author_name'] ?? $post['author_email']); ?></strong> | 
                    Posted on: <?php echo $post['created_at']; ?>
                    <?php if (!empty($post['updated_at'])): ?>
                        <em>(edited)</em>
                    <?php endif; ?>
                </p>
                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                <p>
                    <a href="post.php?id=<?php echo $post['id']; ?>">View Comments & Details</a>
                    <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                        | <a href="edit_post.php?id=<?php echo $post['id']; ?>">Edit</a>
                        | <a href="delete_post.php?id=<?php echo $post['id']; ?>" onclick="return confirm('Delete this post?');" style="color: red;">Delete</a>
                    <?php endif; ?>
                </p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>