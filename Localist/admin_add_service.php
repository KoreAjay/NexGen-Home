<?php
// Start the session at the very beginning of the file.
session_start();


// Include the database connection file.
require_once 'db_connect.php';


// Check if the user is logged in as an admin.
// If not, redirect to the admin login page.
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: admin_login.php");
    exit;
}

$admin_username = htmlspecialchars($_SESSION["admin_username"]);
$message = ""; // For success/error messages

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $price = trim($_POST["price"]);
    $duration_minutes = trim($_POST["duration_minutes"]);

    // Validate inputs
    if (empty($title)) {
        $message = "Error: Service title cannot be empty.";
    } elseif (!is_numeric($price) || $price < 0) {
        $message = "Error: Price must be a non-negative number.";
    } elseif (!filter_var($duration_minutes, FILTER_VALIDATE_INT) || $duration_minutes <= 0) {
        $message = "Error: Duration must be a positive whole number of minutes.";
    } else {
        // Prepare an insert statement
        $sql = "INSERT INTO services (title, description, price, duration_minutes) VALUES (?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssdi", $title, $description, $price, $duration_minutes); // s=string, d=double, i=integer

            if ($stmt->execute()) {
                $message = "Service '{$title}' added successfully!";
                // Clear form fields after successful submission
                $title = $description = $price = $duration_minutes = "";
            } else {
                $message = "Error adding service: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "Database error preparing statement: " . $conn->error;
        }
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Service - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Re-use CSS from admin_panel.php for consistency */
        :root {
            --color-primary: #1A2E44;
            --color-secondary: #007BFF;
            --color-accent: #FFD700;
            --color-text-dark: #2C3E50;
            --color-text-light: #F0F4F8;
            --color-bg-light: #F8FBFD;
            --color-bg-dark: #293B4D;
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
            background: linear-gradient(135deg, #E7EEF5, #F8FBFD);
            color: var(--color-text-dark);
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        header {
            background-color: var(--color-primary);
            color: var(--color-text-light);
            padding: 25px 40px;
            width: 100%;
            box-shadow: var(--shadow-elevated);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        header .logo-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1500px;
            padding: 0 30px;
            box-sizing: border-box;
        }
        header .logo {
            font-size: 2.5em;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.03em;
        }
        header .logo i {
            color: var(--color-accent);
            font-size: 0.9em;
        }
        header .admin-info {
            display: flex;
            align-items: center;
            gap: 25px;
            flex-wrap: wrap;
            justify-content: center;
        }
        header .welcome-message {
            font-size: 1.15em;
            font-weight: 500;
            color: var(--color-text-light);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 25px;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            font-size: 0.98em;
            cursor: pointer;
            transition: all var(--transition-fast);
            border: none;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            white-space: nowrap;
        }
        .btn-primary {
            background-color: var(--color-secondary);
            color: #fff;
            box-shadow: var(--shadow-subtle);
        }
        .btn-primary:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .btn-outline {
            background-color: transparent;
            color: var(--color-text-light);
            border: 2px solid var(--color-text-light);
        }
        .btn-outline:hover {
            background-color: var(--color-text-light);
            color: var(--color-primary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .btn-danger {
            background-color: #dc3545;
            color: #fff;
        }
        .btn-danger:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* Navbar Styling */
        .admin-navbar {
            background-color: var(--color-bg-dark);
            width: 100%;
            padding: 15px 0;
            box-shadow: var(--shadow-subtle);
            margin-top: 15px;
        }
        .admin-navbar ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 25px;
        }
        .admin-navbar ul li a {
            color: var(--color-text-light);
            text-decoration: none;
            font-weight: 500;
            font-size: 1.05em;
            padding: 8px 15px;
            border-radius: var(--border-radius-sm);
            transition: background-color 0.3s ease, transform 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .admin-navbar ul li a:hover {
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }
        .admin-navbar ul li a.active {
            background-color: var(--color-secondary);
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
        }

        /* Content Specific Styles */
        .container {
            width: 100%;
            max-width: 800px; /* Adjusted max-width for forms */
            margin: 40px auto;
            padding: 0 30px;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            color: var(--color-primary);
            text-align: center;
            margin-bottom: 20px;
            font-size: 2.8em;
            letter-spacing: -0.03em;
        }
        p.subtitle {
            font-size: 1.1em;
            color: #666;
            text-align: center;
            margin-bottom: 40px;
        }

        .message {
            padding: 18px;
            margin-bottom: 30px;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            text-align: center;
            font-size: 1.05em;
            animation: slideInFromTop 0.5s ease-out forwards;
        }
        @keyframes slideInFromTop {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-card {
            background-color: #fff;
            padding: 40px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-elevated);
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--color-primary);
            font-size: 1.05em;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea {
            width: calc(100% - 24px); /* Account for padding */
            padding: 14px;
            border: 1px solid #dcdcdc;
            border-radius: var(--border-radius-sm);
            font-size: 1.1em;
            transition: border-color 0.3s, box-shadow 0.3s;
            color: var(--color-text-dark);
            background-color: #fcfdff;
            box-sizing: border-box; /* Include padding in width */
        }
        .form-group textarea {
            resize: vertical; /* Allow vertical resizing */
            min-height: 100px;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--color-secondary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.2);
        }

        .form-card .btn {
            width: 100%;
            margin-top: 15px;
            padding: 16px 30px;
            font-size: 1.15em;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            header .logo-container {
                flex-direction: column;
                gap: 15px;
            }
            header .admin-info {
                width: 100%;
                justify-content: center;
            }
            .admin-navbar ul {
                flex-direction: column;
                gap: 10px;
            }
            .admin-navbar ul li a {
                width: calc(100% - 30px);
                justify-content: center;
            }
            .container {
                margin-top: 30px;
                padding: 0 15px;
            }
            h1 {
                font-size: 2.5em;
            }
            p.subtitle {
                font-size: 1.05em;
            }
            .form-card {
                padding: 30px;
            }
        }
        @media (max-width: 768px) {
            h1 {
                font-size: 2.2em;
            }
            .form-card {
                padding: 25px;
            }
            .form-group input,
            .form-group textarea {
                padding: 12px;
                font-size: 1em;
            }
            .form-card .btn {
                padding: 14px 25px;
                font-size: 1.05em;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <div class="logo"><i class="fas fa-tools"></i> Admin Panel</div>
            <div class="admin-info">
                <span class="welcome-message">Welcome, <?php echo $admin_username; ?>!</span>
                <a href="admin_logout.php" class="btn btn-outline">Logout</a>
            </div>
        </div>
        <nav class="admin-navbar">
            <ul>
                <li><a href="admin_panel.php"><i class="fas fa-calendar-check"></i> Manage Bookings</a></li>
                <li><a href="admin_add_service.php" class="active"><i class="fas fa-plus-circle"></i> Add New Service</a></li>
                <li><a href="admin_add_technician.php"><i class="fas fa-user-plus"></i> Add Technician</a></li>
                <li><a href="admin_feedback.php"><i class="fas fa-comments"></i> View Feedback</a></li>
                </ul>
        </nav>
    </header>

    <div class="container">
        <h1>Add New Service</h1>
        <p class="subtitle">Quickly add new service offerings to your system.</p>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="title">Service Title</label>
                    <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" rows="5"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Price (e.g., 75.00)</label>
                    <input type="number" name="price" id="price" step="0.01" min="0" value="<?php echo htmlspecialchars($price ?? '0.00'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="duration_minutes">Approx. Duration (minutes)</label>
                    <input type="number" name="duration_minutes" id="duration_minutes" min="1" value="<?php echo htmlspecialchars($duration_minutes ?? '60'); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Add Service</button>
            </form>
        </div>
    </div>
</body>
</html>
