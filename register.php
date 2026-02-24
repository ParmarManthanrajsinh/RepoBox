<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Redirect if already logged in
redirectIfLoggedIn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = "Username must be between 3 and 50 characters.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if username is already taken
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $error = "That username is already taken. Please choose another.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $stmt->execute([$username, $hashed_password]);
                $success = "Registration successful! You can now <a href='index.php'>login</a>.";
            } catch (PDOException $e) {
                error_log("RepoBox registration error: " . $e->getMessage());
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<?php require_once 'includes/header.php'; ?>

<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="text-center mb-4">
            <i class="bi bi-box-seam display-1 text-success"></i>
            <h2 class="h3 mt-3 font-weight-normal">Create your RepoBox account</h2>
            <p class="text-muted">Sign up for your personal cloud storage</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
        <?php else: ?>
            <div class="card p-4">
                <form method="POST" action="register.php">
                    <?php echo csrfInputField(); ?>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required autofocus autocomplete="username" minlength="3" maxlength="50">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password" minlength="6">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Create Account</button>
                </form>
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <p>Already have an account? <a href="index.php" class="text-decoration-none">Sign in here</a>.</p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
