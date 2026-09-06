<?php
session_start();
require_once 'includes/db.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $status      = trim($_POST['status'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $date        = trim($_POST['date'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $user_id     = $_SESSION['user_id'] ?? null;

    // Handle Image Upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $img_name = time() . '_' . basename($_FILES['image']['name']);
        $target_dir = 'uploads/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . $img_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = $target_file;
        }
    }

    if (!empty($title) && !empty($status) && !empty($category) && !empty($location) && !empty($description)) {
        try {
            $sql = "INSERT INTO lost_found_items (user_id, title, status, category, location, description, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $createdAt = !empty($date) ? $date . ' ' . date('H:i:s') : date('Y-m-d H:i:s');
            $stmt->execute([$user_id, $title, $status, $category, $location, $description, $image_path, $createdAt]);

            $success_msg = "Item report submitted successfully!";
        } catch (PDOException $e) {
            try {
                $sql = "INSERT INTO lost_found_items (title, status, category, location, description, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$title, $status, $category, $location, $description]);
                $success_msg = "Item report submitted successfully!";
            } catch (PDOException $ex) {
                $error_msg = "Error submitting report: " . $ex->getMessage();
            }
        }
    } else {
        $error_msg = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Lost or Found Item - FOUNDONE</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=99">
    <style>
        body {
            background-color: #f8f9fa; 
            color: #212529; 
        }
        .card-custom {
            background-color: #ffffff; 
            border: 1px solid #dee2e6;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05) !important;
        }
        .form-control, .form-select {
            background-color: #ffffff !important;
            border: 1px solid #ced4da !important;
            color: #212529 !important;
        }
        .form-control:focus, .form-select:focus {
            background-color: #ffffff !important;
            color: #212529 !important;
            border-color: #0d6efd !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15) !important;
        }
        label, .form-label {
            color: #212529 !important; 
        }
        h2.text-info {
            color: #0b5ed7 !important;
        } 
        .btn-info {
            background-color: #ffc107 !important;
            border-color: #ffc107 !important;
            color: #000000 !important;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-custom p-4 shadow-lg rounded-3">
                    <h2 class="text-center fw-bold text-university mb-2">Report Lost & Found Item</h2>
                    <p class="text-center text-muted mb-4">Fill out the form below to report a lost or found item within the campus community.</p>
                    
                    <?php if (!empty($success_msg)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_msg; ?>
                            <a href="search.php" class="alert-link ms-2">View in Search Page</a>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_msg; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="report.php" enctype="multipart/form-data">
                        
                        <div class="mb-3">
                            <label for="itemName" class="form-label fw-bold">Item Name *</label>
                            <input type="text" name="title" class="form-control" id="itemName" placeholder="e.g., Scientific Calculator, Black Laptop Bag" required>
                        </div>

                        <div class="mb-3">
                            <label for="itemStatus" class="form-label fw-bold">Report Type *</label>
                            <select name="status" class="form-select" id="itemStatus" required>
                                <option value="" selected disabled>Choose... Lost or Found?</option>
                                <option value="Lost">I Lost Something</option>
                                <option value="Found">I Found Something</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="itemCategory" class="form-label fw-bold">Category *</label>
                            <select name="category" class="form-select" id="itemCategory" required>
                                <option value="" selected disabled>Select Category</option>
                                <option value="Electronics">Electronics (Laptop, Phone, Charger)</option>
                                <option value="Documents">Documents (ID Card, Record Book)</option>
                                <option value="Books">Books / Stationery</option>
                                <option value="Clothing">Clothing / Bags</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reportDate" class="form-label fw-bold">Date *</label>
                                <input type="date" name="date" class="form-control" id="reportDate" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="itemLocation" class="form-label fw-bold">Location *</label>
                                <input type="text" name="location" class="form-control" id="itemLocation" placeholder="e.g., Main Library, IT Faculty Room 2" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="itemDescription" class="form-label fw-bold">Description *</label>
                            <textarea name="description" class="form-control" id="itemDescription" rows="4" placeholder="Provide details like color, brand, unique marks..." required></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="itemImage" class="form-label fw-bold">Upload Image (Optional)</label>
                            <input type="file" name="image" class="form-control" id="itemImage" accept="image/*">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning fw-bold text-dark py-2">Submit Report</button>
                        </div>
                    </form>
                </div>
            </div>
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