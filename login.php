<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Fetch user from the database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Check if the user exists, the password is correct, and the status is 'active'
    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'active') {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            header("Location: " . ($user['role'] === 'admin' ? 'admin_dashboard.php' : ($user['role'] === 'supplier' ? 'suppliers_dashboard.php' : 'index.php?page=home')));
            exit();
        } else {
            $error = "Your account is not active. Please contact support.";
        }
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <div class="container-fluid p-0">
        <div class="row g-0 min-vh-100">
            <!-- Left Side: Image with Gradient Overlay -->
            <div class="col-md-6 d-flex align-items-center justify-content-center p-5" style="background: linear-gradient(135deg, #6a11cb, #2575fc);">
                <div class="text-white text-center">
                    <h1 class="display-4 fw-bold">Welcome Back!</h1>
                    <p class="lead">Login to access your account and Get your Carte already !!</p>
                </div>
            </div>

            <!-- Right Side: Login Form -->
            <div class="col-md-6 d-flex align-items-center justify-content-center p-5 bg-light">
                <div class="w-100" style="max-width: 400px;">
                    <!-- Logo -->
                    <div class="text-center mb-4">
                        <img src="images/MagaZini-logo.png" alt="MagaZini Logo" class="img-fluid" style="max-height: 80px;">
                    </div>

                    <!-- Login Card -->
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <h3 class="card-title text-center mb-4">Login</h3>
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>
                            <form action="login.php" method="POST">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">👁</button>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Login</button>
                            </form>
                            <div class="mt-3 text-center">
                                <p>Don't have an account? <a href="register.php" class="text-decoration-none">Register here</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            let passField = document.getElementById('password');
            let toggleBtn = event.currentTarget;
            
            if (passField.type === 'password') {
                passField.type = 'text';
                toggleBtn.innerText = "🙈"; // Change icon
            } else {
                passField.type = 'password';
                toggleBtn.innerText = "👁"; // Change icon
            }
        }
    </script>
</body>
</html>