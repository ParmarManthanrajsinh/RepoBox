<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Redirect if already logged in
redirectIfLoggedIn();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Incorrect username or password.";
        }
    }
}
?>
<?php require_once 'includes/header.php'; ?>

<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="text-center mb-4">
            <i class="bi bi-box-seam display-1 text-primary"></i>
            <h2 class="h3 mt-3 font-weight-normal">Sign in to RepoBox</h2>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card p-4">
            <form method="POST" action="index.php">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus autocomplete="username">
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-2">Sign in</button>
            </form>
        </div>
        
        <div class="text-center mt-4">
            <p>New to RepoBox? <a href="register.php" class="text-decoration-none">Create an account</a>.</p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
