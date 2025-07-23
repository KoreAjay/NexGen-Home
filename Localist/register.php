<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - NexGen Home</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'IBM Plex Sans', sans-serif;
            background: linear-gradient(135deg, #1A2E44, #293B4D);
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .register-container {
            background-color: #fff;
            color: #2C3E50;
            padding: 40px 30px;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }
        .register-container h2 {
            font-family: 'Playfair Display', serif; /* Or 'IBM Plex Sans' if you prefer */
            color: #1A2E44;
            margin-bottom: 25px;
            font-size: 2.2em;
        }
        .register-container p {
            font-size: 1.05em;
            margin-bottom: 30px;
            color: #555;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #1A2E44;
        }
        .form-group input {
            width: calc(100% - 24px); /* Account for padding */
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            border-color: #007BFF;
            outline: none;
        }
        .btn {
            background-color: #007BFF;
            color: #fff;
            padding: 14px 25px;
            border: none;
            border-radius: 18px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            width: 100%;
            margin-top: 15px;
        }
        .btn:hover {
            background-color: #1A2E44;
            transform: translateY(-3px);
        }
        .login-link {
            margin-top: 25px;
            font-size: 0.95em;
            color: #555;
        }
        .login-link a {
            color: #007BFF;
            text-decoration: none;
            font-weight: 500;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .error {
            color: #e74c3c;
            margin-top: 10px;
            font-size: 0.9em;
        }
        .success {
            color: #28a745;
            margin-top: 10px;
            font-size: 0.9em;
        }
        /* Media Queries for Responsiveness */
        @media (max-width: 600px) {
            .register-container {
                padding: 30px 20px;
            }
            .register-container h2 {
                font-size: 1.8em;
            }
            .form-group input, .btn {
                padding: 10px;
                font-size: 0.95em;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Create Your Account</h2>
        <p>Join NexGen Home to manage your services efficiently.</p>

        <?php
        require_once 'db_connect.php'; // Include your database connection

        $username = $password = $confirm_password = "";
        $username_err = $password_err = $confirm_password_err = "";
        $registration_success = false;

        // Process form submission
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            // Validate username
            if (empty(trim($_POST["username"]))) {
                $username_err = "Please enter a username.";
            } else {
                // Prepare a select statement to check if username already exists
                $sql = "SELECT id FROM users WHERE username = ?";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("s", $param_username);
                    $param_username = trim($_POST["username"]);
                    if ($stmt->execute()) {
                        $stmt->store_result();
                        if ($stmt->num_rows == 1) {
                            $username_err = "This username is already taken.";
                        } else {
                            $username = trim($_POST["username"]);
                        }
                    } else {
                        echo "<p class='error'>Oops! Something went wrong. Please try again later.</p>";
                    }
                    $stmt->close();
                }
            }

            // Validate password
            if (empty(trim($_POST["password"]))) {
                $password_err = "Please enter a password.";
            } elseif (strlen(trim($_POST["password"])) < 6) {
                $password_err = "Password must have at least 6 characters.";
            } else {
                $password = trim($_POST["password"]);
            }

            // Validate confirm password
            if (empty(trim($_POST["confirm_password"]))) {
                $confirm_password_err = "Please confirm password.";
            } else {
                $confirm_password = trim($_POST["confirm_password"]);
                if (empty($password_err) && ($password != $confirm_password)) {
                    $confirm_password_err = "Password did not match.";
                }
            }

            // If there are no errors, insert into database
            if (empty($username_err) && empty($password_err) && empty($confirm_password_err)) {
                $sql = "INSERT INTO users (username, password) VALUES (?, ?)";

                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("ss", $param_username, $param_password);

                    // Hash the password securely
                    $param_password = password_hash($password, PASSWORD_DEFAULT);
                    $param_username = $username;

                    if ($stmt->execute()) {
                        $registration_success = true; // Set flag for success message
                        $username = $password = $confirm_password = ""; // Clear form fields
                    } else {
                        echo "<p class='error'>Error: Could not register user. Please try again.</p>";
                    }
                    $stmt->close();
                }
            }
            $conn->close(); // Close connection after processing
        }
        ?>

        <?php if ($registration_success): ?>
            <p class="success">Account created successfully! You can now <a href="login.php">log in</a>.</p>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" required>
                <?php if (!empty($username_err)): ?><p class="error"><?php echo $username_err; ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
                <?php if (!empty($password_err)): ?><p class="error"><?php echo $password_err; ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
                <?php if (!empty($confirm_password_err)): ?><p class="error"><?php echo $confirm_password_err; ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Register">
            </div>
            <p class="login-link" onclick="window.location.href='login.php'">Already have an account? login here</p>
        </form>
    </div>
</body>
</html> 