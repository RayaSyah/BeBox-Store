<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="index.php">
</head>
<body>
    <div class="login-container">
        <h2>LOGIN</h2>
        <form action="mainpage.php" method="post"> 
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
                <small class="validation-message">email must be filled in</small>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required minlength="6">
                <small class="validation-message">Password must be filled (minimum 6 characters)</small>
            </div>
            <button type="submit">Sign In</button>
        </form>
        <p class="register-text">Don't have an account? <a href="register.php">Create an account</a></p>
    </div>
</body>
</html>