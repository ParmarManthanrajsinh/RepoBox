<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current user info
$stmt = $pdo->prepare("SELECT id, username, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Count user's files
$stmt = $pdo->prepare("SELECT COUNT(*) FROM files WHERE user_id = ?");
$stmt->execute([$user_id]);
$file_count = $stmt->fetchColumn();

// Total storage used
$stmt = $pdo->prepare("SELECT COALESCE(SUM(file_size), 0) FROM files WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_size = $stmt->fetchColumn();

// Helper function to format file sizes
if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2) {
        if ($bytes === null || $bytes == 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    $action = $_POST['action'] ?? '';

    if ($action === 'change_username') {
        $new_username = trim($_POST['new_username'] ?? '');

        if (empty($new_username)) {
            $error = "Username cannot be empty.";
        } elseif (strlen($new_username) < 3 || strlen($new_username) > 50) {
            $error = "Username must be between 3 and 50 characters.";
        } elseif ($new_username === $user['username']) {
            $error = "That's already your current username.";
        } else {
            // Check if taken
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$new_username, $user_id]);
            if ($stmt->fetchColumn() > 0) {
                $error = "That username is already taken.";
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
                    $stmt->execute([$new_username, $user_id]);
                    $_SESSION['username'] = $new_username;
                    $user['username'] = $new_username;
                    $success = "Username updated successfully!";
                } catch (PDOException $e) {
                    error_log("RepoBox username change error: " . $e->getMessage());
                    $error = "Failed to update username. Please try again.";
                }
            }
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_new_password'] ?? '';

        // Fetch the current hash
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_hash = $stmt->fetchColumn();

        if (empty($current_password) || empty($new_password)) {
            $error = "All password fields are required.";
        } elseif (!password_verify($current_password, $current_hash)) {
            $error = "Current password is incorrect.";
        } elseif (strlen($new_password) < 6) {
            $error = "New password must be at least 6 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } elseif ($current_password === $new_password) {
            $error = "New password must be different from the current one.";
        } else {
            try {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $user_id]);
                $success = "Password updated successfully!";
            } catch (PDOException $e) {
                error_log("RepoBox password change error: " . $e->getMessage());
                $error = "Failed to update password. Please try again.";
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0"><i class="bi bi-person-gear me-2"></i>Profile Settings</h2>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-3 g-md-4">
    <!-- Account Overview Card -->
    <div class="col-12 col-md-4">
        <div class="card profile-overview">
            <div class="card-body text-center py-3 py-md-4">
                <div class="d-none d-md-block mb-3">
                    <i class="bi bi-person-circle display-1 text-muted"></i>
                </div>
                <!-- Mobile: compact horizontal layout -->
                <div class="d-flex d-md-none align-items-center mb-2">
                    <i class="bi bi-person-circle fs-1 text-muted me-3"></i>
                    <div class="text-start">
                        <h5 class="mb-0"><?php echo htmlspecialchars($user['username']); ?></h5>
                        <small class="text-muted">Member since <?php echo date('M j, Y', strtotime($user['created_at'])); ?></small>
                    </div>
                </div>
                <!-- Desktop: centered layout -->
                <h4 class="mb-1 d-none d-md-block"><?php echo htmlspecialchars($user['username']); ?></h4>
                <p class="text-muted small mb-3 d-none d-md-block">Member since <?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
                <hr class="my-2 my-md-3" style="border-color: var(--github-border);">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="fw-bold fs-6 fs-md-5"><?php echo $file_count; ?></div>
                        <div class="text-muted small">Files</div>
                    </div>
                    <div class="col-6">
                        <div class="fw-bold fs-6 fs-md-5"><?php echo formatBytes($total_size); ?></div>
                        <div class="text-muted small">Storage</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Cards -->
    <div class="col-12 col-md-8">
        <!-- Change Username -->
        <div class="card mb-3 mb-md-4">
            <div class="card-header py-2 py-md-3">
                <h5 class="mb-0 fs-6 fs-md-5"><i class="bi bi-pencil-square me-2"></i>Change Username</h5>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="profile.php">
                    <?php echo csrfInputField(); ?>
                    <input type="hidden" name="action" value="change_username">
                    <div class="mb-3">
                        <label for="current_username" class="form-label text-muted small">Current Username</label>
                        <input type="text" class="form-control" id="current_username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="new_username" class="form-label">New Username</label>
                        <input type="text" class="form-control" id="new_username" name="new_username" required minlength="3" maxlength="50" autocomplete="username">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 w-md-auto">Update Username</button>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card">
            <div class="card-header py-2 py-md-3">
                <h5 class="mb-0 fs-6 fs-md-5"><i class="bi bi-shield-lock me-2"></i>Change Password</h5>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="profile.php">
                    <?php echo csrfInputField(); ?>
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6" autocomplete="new-password">
                        <div class="form-text text-muted">Minimum 6 characters.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 w-md-auto">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
