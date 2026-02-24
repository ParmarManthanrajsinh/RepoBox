<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

if (isset($_GET['id'])) {
    $file_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if ($file_id) {
        // First retrieve to get storage_name, ensuring it belongs to the user
        $stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
        $stmt->execute([$file_id, $user_id]);
        $file = $stmt->fetch();

        if ($file) {
            $filepath = __DIR__ . '/uploads/' . $file['storage_name'];
            
            // Delete from database
            $delStmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
            if ($delStmt->execute([$file_id])) {
                // Delete from filesystem
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                $_SESSION['success'] = "File deleted successfully.";
            } else {
                $_SESSION['error'] = "Failed to delete file record from database.";
            }
        } else {
            $_SESSION['error'] = "File not found or access denied.";
        }
    } else {
        $_SESSION['error'] = "Invalid file ID.";
    }
} else {
    $_SESSION['error'] = "No file specified for deletion.";
}

header("Location: dashboard.php");
exit;
