<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No file specified.";
    header("Location: dashboard.php");
    exit;
}

$file_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
$stmt->execute([$file_id, $user_id]);
$file = $stmt->fetch();

if (!$file) {
    $_SESSION['error'] = "File not found or access denied.";
    header("Location: dashboard.php");
    exit;
}

$filepath = __DIR__ . '/uploads/' . $file['storage_name'];

if (!file_exists($filepath)) {
    $_SESSION['error'] = "The file no longer exists on the server.";
    header("Location: dashboard.php");
    exit;
}

// Helper parameters
$is_image = strpos($file['file_type'], 'image/') === 0;
// Basic check for text/code-like files
$is_text = strpos($file['file_type'], 'text/') === 0 
           || in_array($file['file_type'], ['application/json', 'application/javascript', 'application/xml', 'application/x-httpd-php']);

// Default max preview size for text to prevent crashing browser on huge logs (e.g. 5MB limit)
$max_text_preview_size = 5 * 1024 * 1024; 
$can_preview_text = $is_text && $file['file_size'] <= $max_text_preview_size;

// Helper function to format file sizes
if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2) {
        if ($bytes === null) return '-';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

// For breadcrumbs building
$path_parts = explode('/', $file['original_name']);
$filename = array_pop($path_parts); // Get just the file name
$folder_path = implode('/', $path_parts);

require_once 'includes/header.php';
?>

<div class="row mb-3">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 fs-5 pb-2">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none fw-bold">RepoBox</a></li>
                <?php
                $build_path = '';
                foreach ($path_parts as $index => $part) {
                    $build_path .= ($index === 0 ? '' : '/') . $part;
                    echo '<li class="breadcrumb-item"><a href="dashboard.php?path=' . urlencode($build_path) . '" class="text-decoration-none">' . htmlspecialchars($part) . '</a></li>';
                }
                ?>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($filename); ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center">
            <i class="bi bi-file-earmark-text text-muted me-2 fs-4"></i>
            <strong><?php echo htmlspecialchars($filename); ?></strong>
            <span class="badge bg-secondary ms-3"><?php echo htmlspecialchars(formatBytes($file['file_size'])); ?></span>
        </div>
        <div>
            <a href="raw.php?id=<?php echo $file['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary me-2 shadow-sm">
                <i class="bi bi-box-arrow-up-right me-1"></i> Raw
            </a>
            <a href="download.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-outline-secondary me-2 shadow-sm" title="Download">
                <i class="bi bi-download me-1"></i> Download
            </a>
            <a href="delete.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-outline-danger shadow-sm delete-btn" title="Delete">
                <i class="bi bi-trash me-1"></i> Delete
            </a>
        </div>
    </div>
    
    <div class="card-body p-0" style="min-height: 400px;">
        <?php if ($is_image): ?>
            <!-- IMAGE PREVIEW -->
            <div class="text-center p-4">
                <img src="raw.php?id=<?php echo $file['id']; ?>" class="img-fluid border shadow-sm" style="max-height: 70vh;" alt="<?php echo htmlspecialchars($filename); ?>">
            </div>
            
        <?php elseif ($can_preview_text): ?>
            <!-- TEXT / CODE PREVIEW -->
            <?php $content = file_get_contents($filepath); ?>
            <div style="border-bottom-left-radius: 0.375rem; border-bottom-right-radius: 0.375rem;">
                <pre class="m-0 p-3" style="font-family: ui-monospace, SFMono-Regular, SF Mono, Menlo, Consolas, Liberation Mono, monospace; font-size: 13px; line-height: 1.5; overflow-x: auto;"><code><?php echo htmlspecialchars($content); ?></code></pre>
            </div>
            
        <?php else: ?>
            <!-- NO PREVIEW AVAILABLE -->
            <div class="text-center p-5 mt-5">
                <i class="bi bi-file-earmark-binary display-1 text-muted mb-3 d-block"></i>
                <h4 class="mb-2">View not available</h4>
                <?php if ($is_text): ?>
                    <p class="text-muted">This text file is too large to preview directly in the browser.</p>
                <?php else: ?>
                    <p class="text-muted">This file type (<?php echo htmlspecialchars($file['file_type']); ?>) cannot be previewed.</p>
                <?php endif; ?>
                <a href="download.php?id=<?php echo $file['id']; ?>" class="btn btn-primary mt-3 px-4">
                    <i class="bi bi-download me-2"></i> Download File
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
