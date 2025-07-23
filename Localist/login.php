<?php
// Start the session at the very beginning of the file.
// This is crucial for managing user sessions (e.g., checking if a user is logged in).
session_start();

// Check if the user is already logged in. If they are, redirect them to the dashboard.
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit; // Always exit after a header redirect to prevent further script execution.
}

// Include the database connection file.
// Ensure 'db_connect.php' is in the same directory as this file.
require_once 'db_connect.php';

// Initialize variables for username, password, and error messages.
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Process form data when the form is submitted using POST method.
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Validate username input.
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }

    // Validate password input.
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }

    // If there are no input errors, proceed with authentication.
    if(empty($username_err) && empty($password_err)){
        // Prepare a SQL statement to select user data based on the provided username.
        $sql = "SELECT id, username, password FROM users WHERE username = ?";

        // Use a prepared statement to prevent SQL injection.
        if($stmt = $conn->prepare($sql)){
            // Bind parameters to the prepared statement.
            $stmt->bind_param("s", $param_username);
            $param_username = $username; // Set the parameter.

            // Attempt to execute the prepared statement.
            if($stmt->execute()){
                // Store the result to check for existence of the username.
                $stmt->store_result();

                // Check if a username exists with the provided credentials.
                if($stmt->num_rows == 1){
                    // Bind result variables to the retrieved data.
                    $stmt->bind_result($id, $username, $hashed_password);
                    if($stmt->fetch()){
                        // Verify the provided password against the hashed password stored in the database.
                        // password_verify() is the secure way to check hashed passwords.
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, so start a new session (if not already started).
                            // This session was already started at the top of the file.

                            // Store user data in session variables.
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;

                            // Redirect user to the dashboard page upon successful login.
                            header("location: index.php");
                            exit; // Exit to ensure the redirect happens immediately.
                        } else{
                            // Password is not valid.
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else{
                    // Username doesn't exist.
                    $login_err = "Invalid username or password.";
                }
            } else{
                // Error during SQL execution.
                echo "<p class='error'>Oops! Something went wrong. Please try again later.</p>";
            }
            // Close the statement.
            $stmt->close();
        }
    }
    // Close the database connection after processing the form.
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NexGen Home</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* General body styling for the login page */
        body {
            font-family: 'IBM Plex Sans', sans-serif;
            background: linear-gradient(135deg, #1A2E44, #293B4D); /* Dark blue gradient background */
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* Full viewport height */
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        /* Container for the login form */
        .login-container {
            background-color: #fff; /* White background for the form */
            color: #2C3E50; /* Dark text color */
            padding: 40px 30px;
            border-radius: 18px; /* Rounded corners */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); /* Soft shadow for depth */
            width: 100%;
            max-width: 450px; /* Max width for responsiveness */
            text-align: center;
        }
        /* Styling for the main heading */
        .login-container h2 {
            font-family: 'Playfair Display', serif; /* Elegant serif font for headings */
            color: #1A2E44; /* Deep dark blue color */
            margin-bottom: 25px;
            font-size: 2.2em;
        }
        /* Styling for introductory paragraph */
        .login-container p {
            font-size: 1.05em;
            margin-bottom: 30px;
            color: #555; /* Muted text color */
        }
        /* Styling for form groups (label + input) */
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        /* Styling for labels */
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #1A2E44;
        }
        /* Styling for input fields */
        .form-group input {
            width: calc(100% - 24px); /* Full width minus padding */
            padding: 12px;
            border: 1px solid #ccc; /* Light grey border */
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s; /* Smooth transition for focus effect */
        }
        /* Styling for input fields on focus */
        .form-group input:focus {
            border-color: #007BFF; /* Vibrant blue border on focus */
            outline: none; /* Remove default outline */
        }
        /* Styling for the submit button */
        .btn {
            background-color: #007BFF; /* Vibrant blue background */
            color: #fff; /* White text */
            padding: 14px 25px;
            border: none;
            border-radius: 18px; /* Rounded button */
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease; /* Smooth hover effects */
            width: 100%;
            margin-top: 15px;
        }
        /* Styling for the submit button on hover */
        .btn:hover {
            background-color: #1A2E44; /* Darker blue on hover */
            transform: translateY(-3px); /* Slight lift effect */
        }
        /* Styling for the registration link */
        .register-link {
            margin-top: 25px;
            font-size: 0.95em;
            color: #555;
        }
        /* Styling for the link within the registration text */
        .register-link a {
            color: #007BFF; /* Vibrant blue link color */
            text-decoration: none;
            font-weight: 500;
        }
        /* Styling for the link on hover */
        .register-link a:hover {
            text-decoration: underline;
        }
        /* Styling for error messages */
        .error {
            color: #e74c3c; /* Red color for errors */
            margin-top: 10px;
            font-size: 0.9em;
        }
        /* Media Queries for Responsiveness */
        @media (max-width: 600px) {
            .login-container {
                padding: 30px 20px;
            }
            .login-container h2 {
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
    <div class="login-container">
        <h2>Login to Your Account</h2>
        <p>Please fill in your credentials to log in.</p>

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
            <p class="register-link">Don't have an account? <a href="register.php">Sign up now</a>.</p>
        </form>
    </div>
</body>
</html>
