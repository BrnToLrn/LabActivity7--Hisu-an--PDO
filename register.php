<?php
require_once 'db.php';
require_once 'auth_middleware.php';
require_guest();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Email is already registered.";
                $pdo->rollBack();
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
                $stmt->execute([$email, $hashed_password]);

                $pdo->commit();

                header("Location: login.php?registered=1");
                exit;
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
Register

<?php if (count($errors) > 0): ?>
    <?php foreach ($errors as $error): ?>
        Error: <?php echo htmlspecialchars($error); ?>
    <?php endforeach; ?>
<?php endif; ?>

<form method="POST" action="register.php">
    Email: <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
    Password: <input type="password" name="password" minlength="6" required>
    <button type="submit">Register</button>
</form>
<a href="login.php">Login</a>