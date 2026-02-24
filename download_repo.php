<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$user_id = $_SESSION['user_id'];

// Get all files for this user
$stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ?");
$stmt->execute([$user_id]);
$files = $stmt->fetchAll();

if (count($files) === 0) {
    $_SESSION['error'] = "Your repository is empty.";
    header("Location: dashboard.php");
    exit;
}

// Create a temporary zip file
$zip = new ZipArchive();
$zipFileName = tempnam(sys_get_temp_dir(), 'repobox_') . '.zip';

if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    $_SESSION['error'] = "Failed to create ZIP archive.";
    header("Location: dashboard.php");
    exit;
}

$addedFiles = 0;

foreach ($files as $file) {
    $filepath = __DIR__ . '/uploads/' . $file['storage_name'];
    
    // Only add if the file actually exists on the disk
    if (file_exists($filepath)) {
        // ZipArchive::addFile(string $filepath, string $entryname)
        // $file['original_name'] contains the relative path if uploaded as a folder
        $zip->addFile($filepath, ltrim($file['original_name'], '/\\'));
        $addedFiles++;
    }
}

$zip->close();

if ($addedFiles === 0) {
    @unlink($zipFileName);
    $_SESSION['error'] = "No valid files found to download.";
    header("Location: dashboard.php");
    exit;
}

// Set headers for download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="RepoBox_' . date('Y-m-d_H-i-s') . '.zip"');
header('Content-Length: ' . filesize($zipFileName));
header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Content-Description: File Transfer');

// Clear the output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Output the zip file
readfile($zipFileName);

// Delete the temporary zip file
@unlink($zipFileName);
exit;
