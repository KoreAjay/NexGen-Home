<?php
// Start the session at the very beginning of the file
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
?>
    
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Your Dashboard - NexGen Home</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'IBM Plex Sans', sans-serif;
            background: linear-gradient(135deg, #007BFF, #00A9F2);
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
            text-align: center;
        }
        .dashboard-container {
            background-color: #fff;
            color: #1A2E44;
            padding: 50px 30px;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 600px;
        }
        .dashboard-container h2 {
            font-family: 'Playfair Display', serif;
            color: #1A2E44;
            margin-bottom: 20px;
            font-size: 2.5em;
        }
        .dashboard-container p {
            font-size: 1.2em;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn {
            background-color: #1A2E44;
            color: #fff;
            padding: 14px 30px;
            border: none;
            border-radius: 18px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background-color: #007BFF;
            transform: translateY(-3px);
        }
        .welcome-message {
            color: #007BFF;
            font-weight: 600;
            margin-top: 10px;
            font-size: 1.3em;
        }
        /* Media Queries for Responsiveness */
        @media (max-width: 600px) {
            .dashboard-container {
                padding: 30px 20px;
            }
            .dashboard-container h2 {
                font-size: 2em;
            }
            .dashboard-container p, .welcome-message {
                font-size: 1em;
            }
            .btn {
                padding: 12px 25px;
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h2>Welcome to Your Dashboard, <span class="welcome-message"><?php echo htmlspecialchars($_SESSION["username"]); ?></span>!</h2>
        <p>This is a protected page accessible only to logged-in users. Here you can find personalized content, service history, and more.</p>
        <p>You have successfully logged into the NexGen Home service portal.</p>
        <a href="logout.php" class="btn">Logout</a>
    </div>
</body>
</html>
