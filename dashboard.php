<?php
session_start();
require_once 'includes/db.php';

$inquiry_success = false;
$msg = $_GET['msg'] ?? '';
$user_id = $_SESSION['user_id'] ?? null;


if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM lost_found_items WHERE id = ?");
        $stmt->execute([$delete_id]);
        header("Location: dashboard.php?msg=deleted");
        exit();
    } catch (PDOException $e) {
        $msg = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    $item_id     = $_POST['item_id'] ?? null;
    $title       = trim($_POST['title'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status      = trim($_POST['status'] ?? 'Lost');

    if ($item_id && !empty($title)) {
        try {
            $stmt = $pdo->prepare("UPDATE lost_found_items SET title = ?, category = ?, location = ?, description = ?, status = ? WHERE id = ?");
            $stmt->execute([$title, $category, $location, $description, $status, $item_id]);
            header("Location: dashboard.php?msg=updated");
            exit();
        } catch (PDOException $e) {
            $msg = 'error';
        }
    }
}


$editItem = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM lost_found_items WHERE id = ?");
        $stmt->execute([$edit_id]);
        $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $editItem = null;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_inquiry'])) {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($subject) && !empty($message)) {
        try {
            if ($user_id) {
                $stmt = $pdo->prepare("INSERT INTO inquiries (user_id, subject, message, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$user_id, $subject, $message]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO inquiries (subject, message, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$subject, $message]);
            }
            $inquiry_success = true;
        } catch (PDOException $e) {
            try {
                $stmt = $pdo->prepare("INSERT INTO inquiries (subject, message, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$subject, $message]);
                $inquiry_success = true;
            } catch (PDOException $ex) {
                $inquiry_success = false;
            }
        }
    }
}


$lostItems = [];
$foundItems = [];

if ($user_id) {
    try {
        
        $stmtLost = $pdo->prepare("SELECT * FROM lost_found_items WHERE LOWER(status) = 'lost' AND user_id = ? ORDER BY created_at DESC");
        $stmtLost->execute([$user_id]);
        $lostItems = $stmtLost->fetchAll(PDO::FETCH_ASSOC);

       
        $stmtFound = $pdo->prepare("SELECT * FROM lost_found_items WHERE LOWER(status) IN ('found', 'resolved') AND user_id = ? ORDER BY created_at DESC");
        $stmtFound->execute([$user_id]);
        $foundItems = $stmtFound->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $user_id = null;
    }
}

if (!$user_id) {
    try {
       
        $stmtLost = $pdo->query("SELECT * FROM lost_found_items WHERE LOWER(status) = 'lost' ORDER BY created_at DESC");
        $lostItems = $stmtLost ? $stmtLost->fetchAll(PDO::FETCH_ASSOC) : [];

        $stmtFound = $pdo->query("SELECT * FROM lost_found_items WHERE LOWER(status) IN ('found', 'resolved') ORDER BY created_at DESC");
        $foundItems = $stmtFound ? $stmtFound->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (PDOException $e) {
        $lostItems = [];
        $foundItems = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - University Service System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-university shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">FOUNDONE</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto fw-semibold">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="search.php">Lost & Found</a></li>
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="btn btn-danger btn-sm ms-lg-2 px-3 text-white fw-bold" href="auth/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="btn btn-warning btn-sm ms-lg-2 px-3 text-dark fw-bold" href="auth/login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        <div class="text-center mb-4 p-4 bg-light rounded shadow-sm border">
            <h1 class="display-5 fw-bold text-university mb-2">My Report Control Panel</h1>
            <p class="text-muted mb-3">Manage items reported by you and track live institutional service inquiry resolutions.</p>
            <a href="report.php" class="btn btn-warning btn-lg fw-bold text-dark px-4 shadow-sm">+ Report Lost or Found Item</a>
        </div>

        <?php if ($msg === 'deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Item successfully deleted!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif ($msg === 'updated'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Item details successfully updated!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        
        <?php if ($editItem): ?>
            <?php $currStatus = strtolower($editItem['status'] ?? ''); ?>
            <div id="edit-section" class="card shadow border-primary mb-5">
                <div class="card-header bg-primary text-white fw-bold">
                    Edit Reported Item
                </div>
                <div class="card-body">
                    <form action="dashboard.php" method="POST">
                        <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($editItem['id']); ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Item Name / Title</label>
                                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($editItem['title'] ?? $editItem['item_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Lost" <?php echo ($currStatus === 'lost') ? 'selected' : ''; ?>>Lost</option>
                                    <option value="Found" <?php echo ($currStatus === 'found') ? 'selected' : ''; ?>>Found</option>
                                    <option value="Resolved" <?php echo ($currStatus === 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Category</label>
                                <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($editItem['category'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Location</label>
                                <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($editItem['location'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($editItem['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-12 text-end">
                                <a href="dashboard.php" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" name="update_item" class="btn btn-success fw-bold">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        
        <section class="mb-5">
            <h3 class="fw-bold mb-3 text-danger border-bottom pb-2">Lost Property Reports</h3>
            <div class="table-responsive shadow-sm rounded mb-4 bg-white">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Item Name</th>
                            <th>Date Reported</th>
                            <th>Status</th>
                            <th class="text-center">Action Options</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($lostItems) > 0): ?>
                            <?php foreach ($lostItems as $item): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($item['title'] ?? $item['item_name'] ?? 'Item'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($item['created_at'] ?? 'now')); ?></td>
                                    <td><span class="badge bg-danger">Lost</span></td>
                                    <td class="text-center">
                                        <a href="dashboard.php?action=edit&id=<?php echo $item['id']; ?>#edit-section" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                        <a href="dashboard.php?action=delete&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No lost property reports recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h3 class="fw-bold mb-3 text-success border-bottom pb-2">Found Property Reports</h3>
            <div class="table-responsive shadow-sm rounded bg-white">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Item Name</th>
                            <th>Date Reported</th>
                            <th>Status</th>
                            <th class="text-center">Action Options</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($foundItems) > 0): ?>
                            <?php foreach ($foundItems as $item): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($item['title'] ?? $item['item_name'] ?? 'Item'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($item['created_at'] ?? 'now')); ?></td>
                                    <td>
                                        <?php if (strtolower($item['status'] ?? '') === 'resolved'): ?>
                                            <span class="badge bg-success">Resolved</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark">Found</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="dashboard.php?action=edit&id=<?php echo $item['id']; ?>#edit-section" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                        <a href="dashboard.php?action=delete&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No found property reports recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card shadow p-4 max-w-750 mx-auto border-0 bg-white">
            <h3 class="fw-bold mb-4 text-center text-university">SEND INQUIRY</h3>
            <?php if ($inquiry_success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Your inquiry has been logged securely into the university database.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <form method="POST" action="dashboard.php">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="Enter inquiry context heading..." required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold small text-muted">Message</label>
                    <textarea name="message" class="form-control" rows="5" placeholder="Elaborate specific service request message particulars here..." required></textarea>
                </div>
                <div class="text-end">
                    <button type="submit" name="submit_inquiry" class="btn btn-university px-5 fw-bold text-white shadow-sm">SEND INQUIRY</button>
                </div>
            </form>
        </section>
    </main>

    <footer class="bg-dark text-white text-center py-4 border-top border-warning border-3 mt-auto">
        <div class="container">
            <p class="mb-1">&copy; 2026 University Service Management Web Application. All Rights Reserved.</p>
            <p class="small text-muted mb-0">Developed by S.Y.V. PARAMI & W.M.V.P. WEERATHUNGA</p>
        </div>
    </footer>

    <script src="js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>