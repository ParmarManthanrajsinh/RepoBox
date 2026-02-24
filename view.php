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

// Map file extensions to Highlight.js language identifiers
$ext_to_lang = [
    'php' => 'php', 'js' => 'javascript', 'ts' => 'typescript', 'jsx' => 'javascript',
    'tsx' => 'typescript', 'py' => 'python', 'rb' => 'ruby', 'java' => 'java',
    'c' => 'c', 'cpp' => 'cpp', 'cc' => 'cpp', 'h' => 'c', 'hpp' => 'cpp',
    'cs' => 'csharp', 'go' => 'go', 'rs' => 'rust', 'swift' => 'swift',
    'kt' => 'kotlin', 'scala' => 'scala', 'r' => 'r', 'lua' => 'lua',
    'pl' => 'perl', 'sh' => 'bash', 'bash' => 'bash', 'zsh' => 'bash',
    'bat' => 'dos', 'ps1' => 'powershell', 'psm1' => 'powershell',
    'html' => 'html', 'htm' => 'html', 'css' => 'css', 'scss' => 'scss',
    'sass' => 'scss', 'less' => 'less', 'xml' => 'xml', 'svg' => 'xml',
    'json' => 'json', 'yaml' => 'yaml', 'yml' => 'yaml', 'toml' => 'ini',
    'ini' => 'ini', 'cfg' => 'ini', 'conf' => 'ini',
    'md' => 'markdown', 'markdown' => 'markdown',
    'sql' => 'sql', 'graphql' => 'graphql', 'gql' => 'graphql',
    'dockerfile' => 'dockerfile', 'makefile' => 'makefile',
    'cmake' => 'cmake', 'gradle' => 'gradle',
    'tf' => 'hcl', 'hcl' => 'hcl',
    'dart' => 'dart', 'zig' => 'zig', 'nim' => 'nim',
    'ex' => 'elixir', 'exs' => 'elixir', 'erl' => 'erlang',
    'hs' => 'haskell', 'ml' => 'ocaml', 'fs' => 'fsharp',
    'clj' => 'clojure', 'lisp' => 'lisp', 'el' => 'lisp',
    'vue' => 'html', 'svelte' => 'html',
    'txt' => 'plaintext', 'log' => 'plaintext', 'env' => 'bash',
    'gitignore' => 'bash', 'htaccess' => 'apache',
];

// Get file extension
$file_ext = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));
// Also check filename itself for extensionless files like Dockerfile, Makefile
$basename_lower = strtolower(pathinfo($file['original_name'], PATHINFO_FILENAME));

$highlight_lang = $ext_to_lang[$file_ext] ?? $ext_to_lang[$basename_lower] ?? null;

// Broader text/code detection - if we have a highlight lang OR mime starts with text/
$is_text = strpos($file['file_type'], 'text/') === 0 
           || $highlight_lang !== null
           || in_array($file['file_type'], ['application/json', 'application/javascript', 'application/xml', 'application/x-httpd-php']);

// Default max preview size for text to prevent crashing browser on huge logs (e.g. 5MB limit)
$max_text_preview_size = 5 * 1024 * 1024; 
$can_preview_text = $is_text && $file['file_size'] <= $max_text_preview_size;

// If no specific lang detected, default to plaintext
if ($highlight_lang === null && $can_preview_text) {
    $highlight_lang = 'plaintext';
}

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
            <form action="delete.php" method="POST" class="d-inline delete-form">
                <?php echo csrfInputField(); ?>
                <input type="hidden" name="id" value="<?php echo $file['id']; ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger shadow-sm delete-btn" title="Delete">
                    <i class="bi bi-trash me-1"></i> Delete
                </button>
            </form>
        </div>
    </div>
    
    <div class="card-body p-0" style="min-height: 400px;">
        <?php if ($is_image): ?>
            <!-- IMAGE PREVIEW -->
            <div class="text-center p-4">
                <img src="raw.php?id=<?php echo $file['id']; ?>" class="img-fluid border shadow-sm" style="max-height: 70vh;" alt="<?php echo htmlspecialchars($filename); ?>">
            </div>
            
        <?php elseif ($can_preview_text): ?>
            <!-- TEXT / CODE PREVIEW WITH SYNTAX HIGHLIGHTING -->
            <?php $content = file_get_contents($filepath); ?>
            <?php $lines = substr_count($content, "\n") + 1; ?>
            <div class="code-preview-wrapper" style="border-bottom-left-radius: 0.375rem; border-bottom-right-radius: 0.375rem; overflow-x: auto;">
                <div class="d-flex">
                    <div class="line-numbers text-end pe-3 ps-3 py-3 user-select-none" style="min-width: 50px; background-color: #0d1117; border-right: 1px solid #21262d;">
                        <?php for ($i = 1; $i <= $lines; $i++): ?>
                            <div style="font-family: ui-monospace, SFMono-Regular, SF Mono, Menlo, Consolas, monospace; font-size: 12px; line-height: 20px; color: #484f58;"><?php echo $i; ?></div>
                        <?php endfor; ?>
                    </div>
                    <div class="flex-grow-1" style="overflow-x: auto;">
                        <pre class="m-0 p-3" style="background: #0d1117; border: none; border-radius: 0;"><code class="language-<?php echo htmlspecialchars($highlight_lang); ?>" style="font-size: 12px; line-height: 20px;"><?php echo htmlspecialchars($content); ?></code></pre>
                    </div>
                </div>
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
