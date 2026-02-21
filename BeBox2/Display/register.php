<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Page</title>
    <link rel="stylesheet" href="register.css">
</head>
<body>
    <div class="register-container">
        <h2>REGISTER</h2>
        <form action="index.html" method="post"> 
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Choose a username" required minlength="3">
                <small class="validation-message">Username must be filled (min 3 characters)</small>
            </div>
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
                <small class="validation-message">Email must be filled and contain '@'</small>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Create a password" required minlength="6">
                <small class="validation-message">Password must be filled (min 6 characters)</small>
            </div>
            <div class="input-group">
                <label for="confirm-password">Confirm Password</label>
                <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm your password" required minlength="6">
                <small class="validation-message">Confirm password must match the password above</small>
            </div>
            <button type="submit">Create Account</button>
        </form>
        <p class="login-text">Already have an account? <a href="index.php">Sign In</a></p>
    </div>
</body>
</html>