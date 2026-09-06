<?php
session_start();
require_once 'includes/db.php';

$inquiry_success = false;
$user_id = $_SESSION['user_id'] ?? null;

// Handle Inquiry Form Submission
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

// Fetch Items Safely (Supports DB with or without user_id column)
$lostItems = [];
$foundItems = [];

if ($user_id) {
    try {
        $stmtLost = $pdo->prepare("SELECT * FROM lost_found_items WHERE status = 'Lost' AND user_id = ? ORDER BY created_at DESC");
        $stmtLost->execute([$user_id]);
        $lostItems = $stmtLost->fetchAll(PDO::FETCH_ASSOC);

        $stmtFound = $pdo->prepare("SELECT * FROM lost_found_items WHERE status = 'Found' AND user_id = ? ORDER BY created_at DESC");
        $stmtFound->execute([$user_id]);
        $foundItems = $stmtFound->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $user_id = null; // Fallback to all items if user_id column is missing
    }
}

if (!$user_id) {
    try {
        $stmtLost = $pdo->query("SELECT * FROM lost_found_items WHERE status = 'Lost' ORDER BY created_at DESC");
        $lostItems = $stmtLost ? $stmtLost->fetchAll(PDO::FETCH_ASSOC) : [];

        $stmtFound = $pdo->query("SELECT * FROM lost_found_items WHERE status = 'Found' ORDER BY created_at DESC");
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
    <link rel="stylesheet" href="style.css?v=99">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <!-- Navigation Bar -->
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
                    <li class="nav-item"><a class="nav-link" href="index.php#contact">Contact</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="btn btn-danger btn-sm ms-lg-2 px-3 text-white fw-bold" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="btn btn-warning btn-sm ms-lg-2 px-3 text-dark fw-bold" href="auth.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        <div class="text-center mb-5 p-4 bg-light rounded shadow-sm border">
            <h1 class="display-5 fw-bold text-university mb-1">My Report Control Panel</h1>
            <p class="text-muted mb-0">Manage items reported by you and track live institutional service inquiry resolutions.</p>
        </div>

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
                                        <a href="edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                        <a href="delete.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td class="fw-semibold">HP Pen Drive 64GB</td>
                                <td>24/07/2026</td>
                                <td><span class="badge bg-warning text-dark">Processing</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1">Edit</button>
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </td>
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
                                    <td><span class="badge bg-success">Found</span></td>
                                    <td class="text-center">
                                        <a href="edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                        <a href="delete.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td class="fw-semibold">Black Spectacles Case</td>
                                <td>26/07/2026</td>
                                <td><span class="badge bg-success">Resolved</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1">Edit</button>
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </td>
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

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4 border-top border-warning border-3 mt-auto">
        <div class="container">
            <p class="mb-1">&copy; 2026 University Service Management Web Application. All Rights Reserved.</p>
            <p class="small text-muted mb-0">Developed by S.Y.V. PARAMI & W.M.V.P. WEERATHUNGA</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>