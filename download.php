<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

if (isset($_GET['id'])) {
    $file_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if ($file_id) {
        $stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
        $stmt->execute([$file_id, $user_id]);
        $file = $stmt->fetch();

        if ($file) {
            $filepath = __DIR__ . '/uploads/' . $file['storage_name'];

            if (file_exists($filepath)) {
                // Set headers for download
                header('Content-Description: File Transfer');
                header('Content-Type: ' . $file['file_type']);
                $safeFilename = rawurlencode(basename($file['original_name']));
                header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . "\"; filename*=UTF-8''" . $safeFilename);
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filepath));
                
                // Clear output buffer
                if (ob_get_level()) {
                    ob_end_clean();
                }
                
                readfile($filepath);
                exit;
            } else {
                $_SESSION['error'] = "The file no longer exists on the server.";
            }
        } else {
            $_SESSION['error'] = "File not found or access denied.";
        }
    } else {
        $_SESSION['error'] = "Invalid file ID.";
    }
} else {
    $_SESSION['error'] = "No file specified for download.";
}

header("Location: dashboard.php");
exit;
