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

// --- Handle Form Submissions for Feedback Actions ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $feedback_id = filter_var($_POST['feedback_id'], FILTER_SANITIZE_NUMBER_INT);

    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'delete_feedback') {
            $sql = "DELETE FROM testimonials WHERE id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("i", $feedback_id);
                if ($stmt->execute()) {
                    $message = "Feedback #{$feedback_id} deleted successfully!";
                } else {
                    $message = "Error deleting feedback: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = "Database error preparing delete statement: " . $conn->error;
            }
        } elseif ($_POST['action'] == 'toggle_block_feedback') {
            $current_status = htmlspecialchars($_POST['current_status']);
            $new_status = ($current_status == 'active') ? 'blocked' : 'active';

            $sql = "UPDATE testimonials SET display_status = ? WHERE id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("si", $new_status, $feedback_id);
                if ($stmt->execute()) {
                    $message = "Feedback #{$feedback_id} status changed to '{$new_status}' successfully!";
                } else {
                    $message = "Error updating feedback status: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = "Database error preparing block/unblock statement: " . $conn->error;
            }
        }
    }
}

// --- Fetch all feedback/testimonials ---
$all_feedback = [];
// Now selecting the new 'display_status' column
$sql_feedback = "SELECT id, quote, author_name, author_location, stars, avatar_url, display_status FROM testimonials ORDER BY id DESC";

$result_feedback = $conn->query($sql_feedback);
if ($result_feedback && $result_feedback->num_rows > 0) {
    while ($row = $result_feedback->fetch_assoc()) {
        $all_feedback[] = $row;
    }
    $result_feedback->free();
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Feedback - Admin Panel</title>
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
            max-width: 1200px; /* Wider container for feedback cards */
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

        /* Feedback Grid/Cards */
        .feedback-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px; /* Space between cards */
            margin-top: 20px;
        }

        .feedback-card {
            background-color: #fff;
            padding: 30px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-subtle);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative; /* For status badge */
        }
        .feedback-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-elevated);
        }

        .feedback-card .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 10px;
            border-radius: var(--border-radius-sm);
            font-size: 0.75em;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-badge.active {
            background-color: #d4edda; /* Greenish */
            color: #155724;
        }
        .status-badge.blocked {
            background-color: #f8d7da; /* Reddish */
            color: #721c24;
        }

        .feedback-card .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid var(--color-secondary);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .feedback-card .stars {
            color: var(--color-accent); /* Gold color for stars */
            margin-bottom: 15px;
            font-size: 1.2em;
        }
        .feedback-card .stars i {
            margin: 0 2px;
        }

        .feedback-card .quote {
            font-style: italic;
            color: var(--color-text-dark);
            margin-bottom: 20px;
            line-height: 1.6;
            font-size: 1.05em;
        }

        .feedback-card .author-info {
            font-weight: 600;
            color: var(--color-primary);
            font-size: 1.1em;
        }
        .feedback-card .author-info span {
            display: block;
            font-weight: 400;
            font-size: 0.9em;
            color: #777;
            margin-top: 5px;
        }

        .feedback-actions {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            width: 100%;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .feedback-actions .btn {
            padding: 8px 15px;
            font-size: 0.85em;
            border-radius: var(--border-radius-sm);
            width: auto; /* Allow buttons to size content */
            margin: 0; /* Override default button margin */
        }

        .no-feedback-message {
            text-align: center;
            font-size: 1.2em;
            color: #777;
            margin-top: 50px;
            padding: 20px;
            background-color: #fff;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-subtle);
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
            .feedback-grid {
                grid-template-columns: 1fr; /* Stack cards on smaller screens */
            }
            .feedback-card {
                padding: 25px;
            }
        }
        @media (max-width: 768px) {
            h1 {
                font-size: 2.2em;
            }
            .feedback-actions {
                flex-direction: column;
                gap: 8px;
            }
            .feedback-actions .btn {
                width: 100%;
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
                <li><a href="admin_add_service.php"><i class="fas fa-plus-circle"></i> Add New Service</a></li>
                <li><a href="admin_add_technician.php"><i class="fas fa-user-plus"></i> Add Technician</a></li>
                <li><a href="admin_feedback.php" class="active"><i class="fas fa-comments"></i> View Feedback</a></li>
                <!-- Add more admin links here as needed -->
            </ul>
        </nav>
    </header>

    <div class="container">
        <h1>Customer Feedback</h1>
        <p class="subtitle">Review testimonials and feedback submitted by your customers.</p>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($all_feedback)): ?>
            <div class="feedback-grid">
                <?php foreach ($all_feedback as $feedback): ?>
                    <div class="feedback-card">
                        <span class="status-badge <?php echo htmlspecialchars($feedback['display_status']); ?>">
                            <?php echo htmlspecialchars($feedback['display_status']); ?>
                        </span>
                        <img src="<?php echo htmlspecialchars($feedback['avatar_url'] ?: 'https://placehold.co/150x150/cccccc/ffffff?text=User'); ?>" alt="Author Avatar" class="avatar" onerror="this.onerror=null;this.src='https://placehold.co/150x150/cccccc/ffffff?text=User';">
                        <div class="stars">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <?php if ($i < $feedback['stars']): ?>
                                    <i class="fas fa-star"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <p class="quote">"<?php echo htmlspecialchars($feedback['quote']); ?>"</p>
                        <div class="author-info">
                            <?php echo htmlspecialchars($feedback['author_name']); ?>
                            <?php if (!empty($feedback['author_location'])): ?>
                                <span><?php echo htmlspecialchars($feedback['author_location']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="feedback-actions">
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" onsubmit="return confirm('Are you sure you want to delete this feedback?');">
                                <input type="hidden" name="action" value="delete_feedback">
                                <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                                <button type="submit" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Delete</button>
                            </form>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <input type="hidden" name="action" value="toggle_block_feedback">
                                <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                                <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($feedback['display_status']); ?>">
                                <button type="submit" class="btn <?php echo ($feedback['display_status'] == 'active') ? 'btn-outline' : 'btn-primary'; ?>">
                                    <?php if ($feedback['display_status'] == 'active'): ?>
                                        <i class="fas fa-ban"></i> Block
                                    <?php else: ?>
                                        <i class="fas fa-check-circle"></i> Unblock
                                    <?php endif; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-feedback-message">No customer feedback available yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>
