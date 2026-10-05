<?php
require_once 'db.php';
require_once 'auth_middleware.php';
require_guest();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    if (empty($password)) {
        $errors[] = "Please enter your password.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Invalid email or password.";
        }
    }
}
?>
Login

<?php if (isset($_GET['registered'])): ?>
    Registration successful! Please log in.
<?php endif; ?>

<?php if (count($errors) > 0): ?>
    <?php foreach ($errors as $error): ?>
        Error: <?php echo htmlspecialchars($error); ?>
    <?php endforeach; ?>
<?php endif; ?>

<form method="POST" action="login.php">
    Email: <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
    Password: <input type="password" name="password" required>
    <button type="submit">Login</button>
</form>
<a href="register.php">Register</a>