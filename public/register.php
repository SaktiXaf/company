<?php
require_once '../src/auth.php';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        $result = $auth->register(
            $_POST['name'],
            $_POST['username'],
            $_POST['email'],
            $_POST['password'],
            $_POST['country'] ?? 'US'
        );
        
        if ($result['success']) {
            $success = 'Account created successfully! You can now sign in.';
        } else {
            $error = $result['message'];
        }
    }
}

$countries = [
    'US' => 'United States',
    'ID' => 'Indonesia',
    'GB' => 'United Kingdom',
    'CA' => 'Canada',
    'AU' => 'Australia',
    'DE' => 'Germany',
    'FR' => 'France',
    'JP' => 'Japan',
    'SG' => 'Singapore',
    'MY' => 'Malaysia'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Apple Store</title>
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
                <a href="login.php" class="btn btn-secondary">Sign In</a>
            </div>
        </div>
    </nav>

    <main class="auth-page">
        <div class="auth-wrapper">
            <div class="auth-container">
                <div class="auth-card">
                    <div class="auth-card-body">
                        <div class="text-center mb-8">
                            <h1>Create Account</h1>
                            <p class="text-secondary">Join the Apple ecosystem</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-error mb-4">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success mb-4">
                                <?php echo htmlspecialchars($success); ?>
                                <p class="mt-2">
                                    <a href="login.php" class="btn btn-primary btn-sm">Sign In Now</a>
                                </p>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="auth-form">
                            <div class="form-group">
                                <label for="name" class="form-label">Full Name</label>
                                <input 
                                    type="text" 
                                    id="name" 
                                    name="name" 
                                    required 
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                >
                            </div>

                            <div class="form-group">
                                <label for="username" class="form-label">Username</label>
                                <input 
                                    type="text" 
                                    id="username" 
                                    name="username" 
                                    required 
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                    minlength="3"
                                >
                            </div>

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
                                    minlength="6"
                                    class="form-control"
                                >
                                <div class="form-help">
                                    Password must be at least 6 characters long
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="country" class="form-label">Country</label>
                                <select id="country" name="country" class="form-control">
                                    <?php foreach ($countries as $code => $name): ?>
                                        <option value="<?php echo $code; ?>" <?php echo ($_POST['country'] ?? 'US') === $code ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" name="register" class="btn btn-primary btn-block">
                                <i class="fas fa-user-plus"></i>
                                Create Account
                            </button>
                        </form>

                        <div class="auth-links text-center mt-6">
                            <p>Already have an account? <a href="login.php">Sign in</a></p>
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

.form-help {
    font-size: 0.875rem;
    color: var(--gray-500);
    margin-top: 0.5rem;
    font-style: italic;
}

.btn-block {
    width: 100%;
    padding: 1rem 2rem;
    font-size: 1.1rem;
    font-weight: 600;
    margin-top: 1rem;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
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

.alert {
    padding: 1rem 1.25rem;
    border-radius: var(--border-radius);
    border: 1px solid transparent;
    margin-bottom: 1rem;
}

.alert-success {
    background-color: rgba(52, 199, 89, 0.1);
    border-color: rgba(52, 199, 89, 0.3);
    color: #155724;
}

.alert-error {
    background-color: rgba(255, 59, 48, 0.1);
    border-color: rgba(255, 59, 48, 0.3);
    color: #721c24;
}
</style>