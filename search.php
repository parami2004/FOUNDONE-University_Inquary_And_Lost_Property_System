<?php
session_start();
require_once 'includes/db.php';

$keyword = trim($_GET['keyword'] ?? '');
$status  = trim($_GET['status'] ?? '');

$sql = "SELECT * FROM lost_found_items WHERE 1=1";
$params = [];

if (!empty($keyword)) {
    $sql .= " AND (title LIKE ? OR description LIKE ? OR location LIKE ? OR category LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

if (!empty($status)) {
    $sql .= " AND status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Items - FOUNDONE</title>
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
                    <li class="nav-item"><a class="nav-link active" href="search.php">Lost & Found</a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
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
        <h2 class="fw-bold mb-4 text-dark">Search Lost & Found Items</h2>

        <!-- Search Form -->
        <form method="GET" action="search.php" class="row g-3 mb-4 bg-white p-4 rounded shadow-sm">
            <div class="col-md-6">
                <input type="text" name="keyword" class="form-control" placeholder="Search by title, description, category, or location..." value="<?php echo htmlspecialchars($keyword); ?>">
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Statuses (Lost & Found)</option>
                    <option value="Lost" <?php echo $status === 'Lost' ? 'selected' : ''; ?>>Lost</option>
                    <option value="Found" <?php echo $status === 'Found' ? 'selected' : ''; ?>>Found</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-warning w-100 fw-bold">Search</button>
            </div>
        </form>

        <!-- Results Table -->
        <div class="table-responsive bg-white shadow-sm rounded p-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Status</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Description</th>
                        <th>Date Reported</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($items) > 0): ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-<?php echo $item['status'] === 'Lost' ? 'danger' : 'success'; ?>">
                                        <?php echo strtoupper($item['status']); ?>
                                    </span>
                                </td>
                                <td class="fw-bold"><?php echo htmlspecialchars($item['title']); ?></td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td><?php echo htmlspecialchars($item['location']); ?></td>
                                <td><?php echo htmlspecialchars($item['description']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($item['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No records found matching your search.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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