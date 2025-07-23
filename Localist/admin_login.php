<?php
// Start the session at the very beginning of the file.
session_start();

// Include the database connection file.
require_once 'db_connect.php';

// Initialize variables
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Check if the user is already logged in as an admin
if (isset($_SESSION["admin_loggedin"]) && $_SESSION["admin_loggedin"] === true) {
    header("location: admin_panel.php");
    exit;
}

// Process form submission when the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check if username is empty
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate credentials
    if (empty($username_err) && empty($password_err)) {
        // Prepare a select statement from the admin_users table
        // This query now fetches the plain text password from the database
        $sql = "SELECT id, admin_username, admin_password FROM admin_users WHERE admin_username = ?";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_username);

            // Set parameters
            $param_username = $username;

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Store result
                $stmt->store_result();

                // Check if username exists, if yes then verify password
                if ($stmt->num_rows == 1) {
                    // Bind result variables
                    $stmt->bind_result($id, $admin_username, $stored_password); // Renamed for clarity
                    if ($stmt->fetch()) {
                        // WARNING: Comparing plain text passwords directly. This is INSECURE.
                        // This is done ONLY because you explicitly requested to work with plain text passwords.
                        // For proper security, use password_verify($password, $hashed_password) and store hashed passwords.
                        if ($password === $stored_password) {
                            // Password is correct, start a new session for admin
                            session_regenerate_id(true); // Regenerate session ID for security

                            $_SESSION["admin_loggedin"] = true; // Specific flag for admin login
                            $_SESSION["admin_id"] = $id;
                            $_SESSION["admin_username"] = $admin_username;

                            // Redirect administrator to admin panel page
                            header("location: admin_panel.php");
                            exit; // Ensure no further code is executed
                        } else {
                            // Password is not valid
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else {
                    // Username doesn't exist
                    $login_err = "Invalid username or password.";
                }
            } else {
                // This error means the SQL query itself failed (e.g., table not found)
                $login_err = "Oops! Something went wrong with the database. Please try again later. (Error: " . $conn->error . ")";
            }

            // Close statement
            $stmt->close();
        } else {
            // This error means the prepare statement failed (e.g., SQL syntax error)
            $login_err = "Database error: Could not prepare statement. (Error: " . $conn->error . ")";
        }
    }

    // Close connection (only if it was opened successfully)
    if (isset($conn) && $conn->ping()) { // Check if connection is still open before closing
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - NexGen Home</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Base Variables & Utilities */
        :root {
            --color-primary: #1A2E44; /* Deep, sophisticated dark blue-grey */
            --color-secondary: #007BFF; /* A vibrant, but not overly bright blue */
            --color-accent: #FFD700; /* Gold for highlights, a touch of luxury */
            --color-text-dark: #2C3E50;
            --color-text-light: #F0F4F8;
            --color-bg-light: #F8FBFD;
            --color-bg-dark: #293B4D; /* Slightly lighter than primary for contrast */
            --gradient-primary: linear-gradient(135deg, #1A2E44 0%, #293B4D 100%);
            --shadow-subtle: 0 4px 15px rgba(0, 0, 0, 0.08);
            --shadow-elevated: 0 10px 30px rgba(0, 0, 0, 0.15);
            --border-radius-lg: 18px;
            --border-radius-sm: 8px;
            --transition-fast: 0.25s ease-out;
            --transition-normal: 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        body {
            font-family: 'IBM Plex Sans', sans-serif;
            background: linear-gradient(135deg, #E7EEF5, #F8FBFD); /* Soft gradient background */
            color: var(--color-text-dark);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
            overflow: hidden; /* Prevent scrollbars from gradient */
        }

        .login-container {
            background-color: #ffffff; /* Pure white background for the card */
            padding: 50px 40px; /* Increased padding for more spacious feel */
            border-radius: var(--border-radius-lg); /* Large rounded corners */
            box-shadow: var(--shadow-elevated); /* Prominent shadow */
            width: 100%;
            max-width: 480px; /* Slightly wider for better form layout */
            text-align: center;
            position: relative;
            overflow: hidden;
            animation: fadeInScale 0.8s ease-out forwards; /* Animation on load */
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .login-container h2 {
            font-family: 'Playfair Display', serif;
            color: var(--color-primary);
            margin-bottom: 10px; /* Reduced margin, as subtitle follows */
            font-size: 2.8em; /* Larger, more impactful heading */
            letter-spacing: -0.02em;
        }
        .login-container p.subtitle {
            font-size: 1.1em;
            color: #666;
            margin-bottom: 40px; /* More space before form */
            font-weight: 300;
        }

        .form-group {
            margin-bottom: 28px; /* More space between fields */
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 12px; /* More space for label */
            font-weight: 600; /* Bolder labels */
            color: var(--color-primary);
            font-size: 1.1em;
        }
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: calc(100% - 28px); /* Account for padding */
            padding: 16px; /* Even larger input fields */
            border: 1px solid #dcdcdc; /* Lighter border */
            border-radius: var(--border-radius-sm);
            font-size: 1.15em;
            transition: border-color 0.3s, box-shadow 0.3s;
            color: var(--color-text-dark);
            background-color: #fcfdff; /* Slightly off-white input background */
        }
        .form-group input:focus {
            border-color: var(--color-secondary);
            outline: none;
            box-shadow: 0 0 0 5px rgba(0, 123, 255, 0.2); /* Softer, wider focus glow */
        }

        .btn {
            background: var(--gradient-primary); /* Use gradient for button */
            color: var(--color-text-light);
            padding: 18px 35px; /* Larger, more prominent button */
            border: none;
            border-radius: var(--border-radius-lg);
            font-size: 1.2em;
            font-weight: 700; /* Bolder text */
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 30px; /* More space above button */
            text-transform: uppercase;
            letter-spacing: 0.08em; /* More pronounced letter spacing */
            box-shadow: var(--shadow-subtle);
        }
        .btn:hover {
            transform: translateY(-5px); /* More pronounced lift effect */
            box-shadow: var(--shadow-elevated); /* Stronger shadow on hover */
            background: linear-gradient(135deg, #007BFF 0%, #1A2E44 100%); /* Invert gradient or change color */
        }
        .btn:active {
            transform: translateY(-2px); /* Slight press effect */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .error {
            color: #e74c3c;
            margin-top: 15px; /* More space for error message */
            font-size: 1em;
            text-align: center; /* Center error message */
            padding: 10px;
            background-color: #fef2f2; /* Light red background for error */
            border-radius: var(--border-radius-sm);
            border: 1px solid #e74c3c;
            font-weight: 500;
        }

        .customer-login-link {
            margin-top: 35px; /* More space below button */
            font-size: 1.05em;
            color: #777;
        }
        .customer-login-link a {
            color: var(--color-secondary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .customer-login-link a:hover {
            color: var(--color-primary);
            text-decoration: underline;
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            .login-container {
                padding: 30px 25px;
                max-width: 95%; /* Adjust max-width for very small screens */
            }
            .login-container h2 {
                font-size: 2.2em;
            }
            .login-container p.subtitle {
                font-size: 1em;
                margin-bottom: 30px;
            }
            .form-group input {
                padding: 14px;
                font-size: 1.05em;
            }
            .btn {
                padding: 16px 25px;
                font-size: 1.1em;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <p class="subtitle">Secure access for administrators.</p>

        <?php
        // Display login error message if any
        if(!empty($login_err)){
            echo '<p class="error">' . $login_err . '</p>';
        }
        ?>

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
                <input type="submit" class="btn" value="Login">
            </div>
            <p class="customer-login-link">
                <a href="login.php">Go to Customer Login</a>
            </p>
        </form>
    </div>
</body>
</html>
