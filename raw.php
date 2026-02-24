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
                // Set headers for raw inline display
                header('Content-Type: ' . $file['file_type']);
                header('Content-Length: ' . filesize($filepath));
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                
                // Clear output buffer
                if (ob_get_level()) {
                    ob_end_clean();
                }
                
                readfile($filepath);
                exit;
            }
        }
    }
}
// If anything fails, return 404
http_response_code(404);
exit;
