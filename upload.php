<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files'])) {
    $files = $_FILES['files'];
    $user_id = $_SESSION['user_id'];
    
    // Configuration
    $upload_dir = __DIR__ . '/uploads/';
    $max_size = 10 * 1024 * 1024; // 10MB
    
    // Decode incoming file paths
    $filePaths = [];
    if (!empty($_POST['filePaths'])) {
        $filePaths = json_decode($_POST['filePaths'], true) ?: [];
    }

    $successCount = 0;
    $errorMessages = [];

    // Loop through each uploaded file
    for ($i = 0; $i < count($files['name']); $i++) {
        $error = $files['error'][$i];
        $size = $files['size'][$i];
        $tmp_name = $files['tmp_name'][$i];
        $name = $files['name'][$i];
        $type = $files['type'][$i];

        if ($error !== UPLOAD_ERR_OK) {
            // Ignore empty file selections (e.g. empty directories depending on browser)
            if ($error !== UPLOAD_ERR_NO_FILE) {
                $errorMessages[] = "Failed to upload {$name}: Error code {$error}";
            }
            continue;
        }

        if ($size > $max_size) {
            $errorMessages[] = "File {$name} exceeds limit of 10MB.";
            continue;
        }

        if ($size === 0) {
            $errorMessages[] = "File {$name} is empty.";
            continue;
        }

        // Determine the original path (falling back to simple filename if not available)
        $original_name = isset($filePaths[$i]) && !empty($filePaths[$i]) ? $filePaths[$i] : basename($name);
        // Sanitize path separators just in case
        $original_name = str_replace('\\', '/', $original_name);
        
        $file_type = $type ?: 'application/octet-stream';
        $file_size = $size;
        
        // Generate secure storage name
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $storage_name = uniqid('file_', true) . '_' . bin2hex(random_bytes(8)) . ($ext ? '.' . $ext : '');
        $destination = $upload_dir . $storage_name;

        // Move the file
        if (move_uploaded_file($tmp_name, $destination)) {
            // Insert into DB
            $stmt = $pdo->prepare("INSERT INTO files (user_id, original_name, storage_name, file_size, file_type) VALUES (?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$user_id, $original_name, $storage_name, $file_size, $file_type]);
                $successCount++;
            } catch (PDOException $e) {
                unlink($destination); // rollback
                $errorMessages[] = "Database error for {$name}: " . $e->getMessage();
            }
        } else {
            $errorMessages[] = "Failed to move {$name} to storage.";
        }
    }

    if ($successCount > 0) {
        $_SESSION['success'] = "Successfully uploaded {$successCount} file(s). " . implode(" ", $errorMessages);
    } else if (count($errorMessages) > 0) {
        $_SESSION['error'] = implode("<br>", $errorMessages);
    } else {
        $_SESSION['error'] = "No valid files were uploaded.";
    }
} else {
    $_SESSION['error'] = "Invalid upload request.";
}

header("Location: dashboard.php");
exit;
