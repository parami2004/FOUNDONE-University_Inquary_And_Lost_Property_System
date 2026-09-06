<?php
session_start();
require_once 'includes/db.php';

$errors = [];
$success = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $errors[] = "Please fill in all required fields.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "This email is already registered.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $hashedPassword])) {
                $success = "Registration successful! You can now log in.";
            } else {
                $errors[] = "An error occurred during registration. Please try again.";
            }
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "Please enter both Email and Password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['user_email'] = $user['email'];
            
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Invalid Email or Password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication - FOUNDONE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=99">
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
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#contact">Contact</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="btn btn-danger btn-sm ms-lg-2 px-3 text-white fw-bold" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="btn btn-warning btn-sm ms-lg-2 px-3 text-dark fw-bold active" href="auth.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <div><?php echo htmlspecialchars($success); ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-5 align-items-stretch">
            
            
            <div class="col-md-5">
                <div class="card shadow h-100 p-4 border-top border-4 border-university bg-white">
                    <h2 class="fw-bold mb-4 text-center text-university">Login</h2>
                    <form action="auth.php" method="POST">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email address</label>
                            <input type="email" name="email" class="form-control" required placeholder="Enter university email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control" required placeholder="Enter password">
                        </div>
                        <button type="submit" class="btn btn-dark w-100 fw-bold py-2 mt-3">LOGIN</button>
                    </form>
                </div>
            </div>

            
            <div class="col-md-2 d-none d-md-flex align-items-center justify-content-center position-relative">
                <div class="vr h-75 bg-secondary opacity-25"></div>
            </div>

            
            <div class="col-md-5">
                <div class="card shadow h-100 p-4 border-top border-4 border-warning bg-white">
                    <h2 class="fw-bold mb-4 text-center text-dark">Register</h2>
                    <form action="auth.php" method="POST">
                        <input type="hidden" name="action" value="register">
                        
                        <div class="mb-3">
                            <label for="regUsername" class="form-label fw-semibold">Username</label>
                            <input type="text" name="username" class="form-control" id="regUsername" required placeholder="Choose username">
                        </div>
                        <div class="mb-3">
                            <label for="regEmail" class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" id="regEmail" required placeholder="name@university.lk">
                        </div>
                        <div class="mb-3">
                            <label for="regPassword" class="form-label fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control" id="regPassword" required placeholder="Create secure password">
                        </div>
                        <button type="submit" class="btn btn-warning w-100 fw-bold shadow-sm py-2 text-dark">REGISTER</button>
                    </form>
                </div>
            </div>

        </div>
    </main>

    
    <footer class="bg-dark text-white text-center py-4 border-top border-warning border-3 mt-auto">
        <div class="container">
            <p class="mb-1">&copy; 2026 University Service Management Web Application. All Rights Reserved.</p>
            <p class="small text-muted mb-0">Developed by S.Y.V. PARAMI & W.M.V.P. WEERATHUNGA</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>