<?php
require_once 'src/role-redirect.php';
require_once '../src/auth.php';

// Redirect if already logged in based on role
if ($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
    $redirectUrl = getRedirectUrl($user['role'] ?? 'customer');
    header('Location: ' . $redirectUrl);
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $result = $auth->login($_POST['email'], $_POST['password']);
        if ($result['success']) {
            // Get user role and redirect accordingly
            $user = $auth->getCurrentUser();
            $userRole = $user['role'] ?? 'customer';
            
            // Get redirect URL based on role or custom redirect
            if (isset($_GET['redirect']) && $userRole === 'customer') {
                $redirectUrl = $_GET['redirect'];
            } else {
                $redirectUrl = getRedirectUrl($userRole);
            }
            
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Apple Store</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container container">
            <a href="index.php" class="navbar-brand">
                <i class="fab fa-apple"></i> Apple
            </a>
            <div class="navbar-actions">
                <a href="register.php" class="btn btn-secondary">Create Account</a>
            </div>
        </div>
    </nav>

    <main class="auth-page">
        <div class="auth-wrapper">
            <div class="auth-container">
                <div class="auth-card">
                    <div class="auth-card-body">
                        <div class="text-center mb-8">
                            <h1>Sign In</h1>
                            <p class="text-secondary">Welcome back to Apple Store</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-error mb-4">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="auth-form">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    required 
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                >
                            </div>

                            <div class="form-group">
                                <label for="password" class="form-label">Password</label>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    required 
                                    class="form-control"
                                >
                            </div>

                            <button type="submit" name="login" class="btn btn-primary btn-block">
                                <i class="fas fa-sign-in-alt"></i>
                                Sign In
                            </button>
                        </form>

                        <div class="auth-links text-center mt-6">
                            <p>Don't have an account? <a href="register.php">Create one</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/js/main.js"></script>
</body>
</html>

<style>
.auth-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
    padding: 2rem 0;
}

.auth-wrapper {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.auth-container {
    max-width: 450px;
    width: 100%;
    margin: 0 1rem;
}

.auth-card {
    background: var(--white);
    border-radius: var(--border-radius-xl);
    box-shadow: var(--shadow-xl);
    overflow: hidden;
    padding: 3rem 2.5rem;
}

.auth-card-body {
    width: 100%;
}

.auth-form {
    width: 100%;
}

.form-group {
    margin-bottom: 2rem;
}

.form-label {
    display: block;
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-control {
    width: 100%;
    padding: 1rem 1.25rem;
    border: 2px solid var(--gray-200);
    border-radius: var(--border-radius-lg);
    font-size: 1rem;
    background-color: var(--gray-50);
    transition: all 0.3s ease;
    font-family: var(--font-family-base);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    background-color: var(--white);
    box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
    transform: translateY(-1px);
}

.form-control::placeholder {
    color: var(--gray-400);
    font-style: italic;
}

.btn-block {
    width: 100%;
    padding: 1rem 2rem;
    font-size: 1.1rem;
    font-weight: 600;
    margin-top: 1rem;
}

.auth-links {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--gray-200);
}

.auth-links a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s ease;
}

.auth-links a:hover {
    color: #0056CC;
    text-decoration: underline;
}

@media (max-width: 480px) {
    .auth-card {
        padding: 2rem 1.5rem;
        margin: 1rem;
    }
    
    .auth-container {
        margin: 0 0.5rem;
    }
}
</style>