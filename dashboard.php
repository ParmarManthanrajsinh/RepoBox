<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$current_path = isset($_GET['path']) ? trim($_GET['path'], '/\\') : '';

// Fetch files
$stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? ORDER BY original_name ASC, upload_date DESC");
$stmt->execute([$user_id]);
$all_files = $stmt->fetchAll();

// Message handling
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

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

// Filter files for the current directory level
$display_items = [];
$seen_folders = [];

foreach ($all_files as $file) {
    $full_path = $file['original_name'];
    
    // Check if the file is within the current path
    if ($current_path === '' || strpos($full_path, $current_path . '/') === 0) {
        $relative_to_current = $current_path === '' ? $full_path : substr($full_path, strlen($current_path) + 1);
        
        // Determine if this is a file in the current dir, or a subfolder
        $slash_pos = strpos($relative_to_current, '/');
        
        if ($slash_pos === false) {
            // It's a file in the current directory
            $display_items[] = [
                'type' => 'file',
                'name' => $relative_to_current,
                'data' => $file
            ];
        } else {
            // It's inside a subfolder
            $folder_name = substr($relative_to_current, 0, $slash_pos);
            if (!in_array($folder_name, $seen_folders)) {
                $seen_folders[] = $folder_name;
                $display_items[] = [
                    'type' => 'folder',
                    'name' => $folder_name,
                    'data' => null
                ];
            }
        }
    }
}

// Sort items: folders first, then files alphabetically
usort($display_items, function($a, $b) {
    if ($a['type'] === $b['type']) {
        return strcasecmp($a['name'], $b['name']);
    }
    return $a['type'] === 'folder' ? -1 : 1;
});

require_once 'includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="mb-0">Your Repository</h2>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <a href="download_repo.php" class="btn btn-outline-primary me-2 <?php echo count($all_files) === 0 ? 'disabled' : ''; ?>">
            <i class="bi bi-file-earmark-zip me-1"></i> Download Repo as ZIP
        </a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="bi bi-cloud-arrow-up me-1"></i> Upload
        </button>
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

<div class="row align-items-center mb-3">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 fs-5 pb-2">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none fw-bold">RepoBox</a></li>
                <?php
                if ($current_path !== '') {
                    $path_parts = explode('/', $current_path);
                    $build_path = '';
                    foreach ($path_parts as $index => $part) {
                        $build_path .= ($index === 0 ? '' : '/') . $part;
                        if ($index === count($path_parts) - 1) {
                            echo '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($part) . '</li>';
                        } else {
                            echo '<li class="breadcrumb-item"><a href="dashboard.php?path=' . urlencode($build_path) . '" class="text-decoration-none">' . htmlspecialchars($part) . '</a></li>';
                        }
                    }
                }
                ?>
            </ol>
        </nav>
    </div>
</div>

<div class="card shadow-sm">
    <?php if (count($display_items) > 0 || $current_path !== ''): ?>
    <div class="table-responsive">
        <table class="table table-hover table-borderless mb-0 align-middle">
            <thead class="table-light border-bottom">
                <tr>
                    <th scope="col" class="ps-4 w-50">Name</th>
                    <th scope="col">Size</th>
                    <th scope="col" class="d-none d-md-table-cell">Uploaded</th>
                    <th scope="col" class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($current_path !== ''): 
                    $parent_path = dirname($current_path);
                    if ($parent_path === '.') $parent_path = '';
                ?>
                <tr>
                    <td colspan="4" class="ps-4">
                        <i class="bi bi-folder-fill text-primary me-2 fs-5"></i>
                        <a href="dashboard.php?path=<?php echo urlencode($parent_path); ?>" class="text-decoration-none fw-bold text-dark">..</a>
                    </td>
                </tr>
                <?php endif; ?>

                <?php foreach ($display_items as $item): ?>
                <tr>
                    <td class="ps-4">
                        <?php if ($item['type'] === 'folder'): ?>
                            <i class="bi bi-folder-fill text-primary me-2 fs-5"></i>
                            <a href="dashboard.php?path=<?php echo urlencode(($current_path ? $current_path . '/' : '') . $item['name']); ?>" class="text-decoration-none fw-bold text-dark">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </a>
                        <?php else: ?>
                            <i class="bi bi-file-earmark-text text-muted me-2 fs-5"></i>
                            <a href="view.php?id=<?php echo $item['data']['id']; ?>" class="text-decoration-none text-dark">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted">
                        <?php echo $item['type'] === 'file' ? formatBytes($item['data']['file_size']) : '-'; ?>
                    </td>
                    <td class="text-muted d-none d-md-table-cell">
                        <?php echo $item['type'] === 'file' ? date('M j, Y', strtotime($item['data']['upload_date'])) : '-'; ?>
                    </td>
                    <td class="text-end pe-4">
                        <?php if ($item['type'] === 'file'): ?>
                            <a href="download.php?id=<?php echo $item['data']['id']; ?>" class="btn btn-sm btn-outline-secondary me-1" title="Download">
                                <i class="bi bi-download"></i>
                            </a>
                            <a href="delete.php?id=<?php echo $item['data']['id']; ?>" class="btn btn-sm btn-outline-danger delete-btn" title="Delete">
                                <i class="bi bi-trash"></i>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="text-center p-5">
        <i class="bi bi-folder2-open display-4 text-muted mb-3 d-block"></i>
        <h4>Your repository is empty</h4>
        <p class="text-muted">Upload your first file to get started.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="upload.php" method="POST" enctype="multipart/form-data" id="uploadForm">
          <div class="modal-header">
            <h5 class="modal-title" id="uploadModalLabel">Upload Files or Folder</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
              <div class="mb-3">
                  <div class="form-check form-switch mb-2">
                      <input class="form-check-input" type="checkbox" id="folderUploadSwitch">
                      <label class="form-check-label" for="folderUploadSwitch">Upload an entire folder</label>
                  </div>
                  <label for="fileInput" class="form-label">Select files</label>
                  <input class="form-control" type="file" id="fileInput" name="files[]" multiple required>
                  <input type="hidden" id="filePaths" name="filePaths">
                  <div class="form-text">Max file size: 10MB per file.</div>
              </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary" style="background-color: #2da44e;" id="uploadBtn">Upload</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const folderSwitch = document.getElementById('folderUploadSwitch');
    const fileInput = document.getElementById('fileInput');
    const uploadForm = document.getElementById('uploadForm');
    const filePathsInput = document.getElementById('filePaths');
    const uploadBtn = document.getElementById('uploadBtn');

    folderSwitch.addEventListener('change', function() {
        if (this.checked) {
            fileInput.setAttribute('webkitdirectory', '');
            fileInput.setAttribute('directory', '');
            fileInput.labels[0].textContent = 'Select folder';
        } else {
            fileInput.removeAttribute('webkitdirectory');
            fileInput.removeAttribute('directory');
            fileInput.labels[0].textContent = 'Select files';
        }
        fileInput.value = ''; // Reset selection when toggling
    });

    uploadForm.addEventListener('submit', function(e) {
        // Collect all file paths before submitting to keep directory structure intact
        const paths = [];
        for (let i = 0; i < fileInput.files.length; i++) {
            // Use webkitRelativePath if available and folder upload is checked, otherwise just filename
            const path = folderSwitch.checked && fileInput.files[i].webkitRelativePath 
                            ? fileInput.files[i].webkitRelativePath 
                            : fileInput.files[i].name;
            paths.push(path);
        }
        filePathsInput.value = JSON.stringify(paths);
        
        // Show loading state
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading...';
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
