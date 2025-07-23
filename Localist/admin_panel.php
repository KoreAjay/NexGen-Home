<?php
// Temporarily enable error reporting for debugging. REMOVE THESE LINES IN PRODUCTION!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session at the very beginning of the file.
session_start();

// Include the database connection file.
require_once 'db_connect.php';

// Check if the database connection was successful
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Check if the user is logged in as an admin.
// If not, redirect to the admin login page.
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: admin_login.php"); // Redirect to the dedicated admin login
    exit;
}

$admin_username = htmlspecialchars($_SESSION["admin_username"]); // Fetch from admin session
$message = ""; // For success/error messages

// Retrieve and clear any session messages
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the message after displaying it
}

// --- Handle Form Submissions ---

// 1. Handle Assign Technician / Update Status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $booking_id = filter_var($_POST['booking_id'], FILTER_SANITIZE_NUMBER_INT);

    if ($_POST['action'] == 'assign_technician' && isset($_POST['technician_id'])) {
        $technician_id = filter_var($_POST['technician_id'], FILTER_SANITIZE_NUMBER_INT);
        $status = "Confirmed"; // Automatically confirm when assigning a technician

        $sql = "UPDATE bookings SET technician_id = ?, status = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("isi", $technician_id, $status, $booking_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Booking #{$booking_id} confirmed and technician assigned successfully!";
            } else {
                $_SESSION['message'] = "Error assigning technician: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = "Database error preparing assign technician statement: " . $conn->error;
        }
    } elseif ($_POST['action'] == 'update_status' && isset($_POST['status'])) {
        $status = htmlspecialchars($_POST['status']);

        $sql = "UPDATE bookings SET status = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("si", $status, $booking_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Booking #{$booking_id} status updated to '{$status}' successfully!";
            } else {
                $_SESSION['message'] = "Error updating status: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = "Database error preparing update status statement: " . $conn->error;
        }
    }
    // Redirect to prevent form re-submission and display updated data
    header("Location: admin_panel.php");
    exit;
}

// --- Fetch Data for Display ---

// Fetch all technicians for dropdowns
$technicians = [];
$sql_technicians = "SELECT id, name, specialty FROM technicians ORDER BY name ASC";
$result_technicians = $conn->query($sql_technicians);
if ($result_technicians && $result_technicians->num_rows > 0) {
    while ($row = $result_technicians->fetch_assoc()) {
        $technicians[] = $row;
    }
    $result_technicians->free();
}

// Fetch all bookings with user and service details
$all_bookings = [];
$sql_all_bookings = "SELECT
                        b.id AS booking_id,
                        u.username,
                        s.title AS service_title,
                        b.booking_date,
                        b.booking_time,
                        b.notes,
                        b.status,
                        t.name AS technician_name,
                        t.specialty AS technician_specialty,
                        t.contact_phone AS technician_phone,
                        b.created_at
                     FROM bookings b
                     JOIN users u ON b.user_id = u.id
                     JOIN services s ON b.service_id = s.id
                     LEFT JOIN technicians t ON b.technician_id = t.id
                     ORDER BY b.created_at DESC"; // Most recent bookings first

$result_all_bookings = $conn->query($sql_all_bookings);
if ($result_all_bookings) { // Check if query itself was successful
    if ($result_all_bookings->num_rows > 0) {
        while ($row = $result_all_bookings->fetch_assoc()) {
            $all_bookings[] = $row;
        }
        $result_all_bookings->free();
    }
} else {
    // This message will be displayed if the SQL query itself fails (e.g., table not found, syntax error)
    $message = "Error fetching bookings from database: " . $conn->error . ". Please check your database tables (bookings, users, services, technicians) and their relationships.";
}


// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - NexGen Home</title>
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
            padding: 25px 40px; /* More padding */
            width: 100%;
            box-shadow: var(--shadow-elevated);
            display: flex;
            flex-direction: column; /* Stack logo, info, and nav vertically */
            align-items: center;
            gap: 20px; /* Space between elements */
        }
        header .logo-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1500px; /* Match container width */
            padding: 0 30px; /* Match container padding */
            box-sizing: border-box;
        }
        header .logo {
            font-size: 2.5em; /* Larger logo */
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
            gap: 25px; /* More space */
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
            padding: 12px 25px; /* Slightly larger buttons */
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
            background-color: var(--color-bg-dark); /* Darker background for navbar */
            width: 100%;
            padding: 15px 0;
            box-shadow: var(--shadow-subtle);
            margin-top: 15px; /* Space below header info */
        }
        .admin-navbar ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 25px; /* Space between nav items */
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
            background-color: rgba(255, 255, 255, 0.15); /* Light hover effect */
            transform: translateY(-2px);
        }
        .admin-navbar ul li a.active {
            background-color: var(--color-secondary);
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
        }


        .container {
            width: 100%;
            max-width: 1500px; /* Wider container for more data */
            margin: 40px auto; /* More margin */
            padding: 0 30px; /* More padding */
        }

        h1 {
            font-family: 'Playfair Display', serif;
            color: var(--color-primary);
            text-align: center;
            margin-bottom: 30px; /* Adjusted margin */
            font-size: 3em; /* Larger heading */
            letter-spacing: -0.03em;
        }
        p.subtitle {
            font-size: 1.15em;
            color: #666;
            text-align: center;
            margin-bottom: 40px;
        }

        .message {
            padding: 18px; /* More padding */
            margin-bottom: 30px; /* More margin */
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

        .booking-table-container {
            background-color: #fff;
            padding: 35px; /* More padding */
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-elevated);
            overflow-x: auto; /* Enable horizontal scrolling for table on small screens */
        }

        .booking-table {
            width: 100%;
            border-collapse: separate; /* Use separate for rounded corners on cells */
            border-spacing: 0;
            margin-top: 25px;
            overflow: hidden; /* Ensures border-radius applies to table */
        }
        .booking-table th, .booking-table td {
            padding: 18px; /* More padding */
            border-bottom: 1px solid #eee;
            text-align: left;
            vertical-align: middle;
        }
        .booking-table th {
            background-color: var(--color-primary);
            color: var(--color-text-light);
            font-weight: 700; /* Bolder headers */
            font-size: 1em;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            position: sticky; /* Make headers sticky for horizontal scroll */
            top: 0;
            z-index: 1;
        }
        .booking-table th:first-child { border-top-left-radius: var(--border-radius-sm); }
        .booking-table th:last-child { border-top-right-radius: var(--border-radius-sm); }

        .booking-table td {
            background-color: #fff;
            font-size: 0.95em;
            color: var(--color-text-dark);
        }
        .booking-table tr:nth-child(even) td {
            background-color: var(--color-bg-light);
        }
        .booking-table tr:hover td {
            background-color: #e9f5ff;
        }
        .booking-table tr:last-child td {
            border-bottom: none; /* No border on last row */
        }

        .status-badge {
            display: inline-block;
            padding: 8px 15px; /* Larger badge */
            border-radius: var(--border-radius-sm);
            font-size: 0.85em;
            font-weight: 700;
            text-transform: uppercase;
            min-width: 80px; /* Ensure consistent width */
            text-align: center;
        }
        .status-badge.Pending {
            background-color: #fff3cd; /* Lighter yellow */
            color: #856404;
        }
        .status-badge.Confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-badge.Completed {
            background-color: #e2e6ea; /* Lighter grey */
            color: #383d41;
        }
        .status-badge.Cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .action-form {
            display: flex;
            flex-direction: column; /* Stack elements vertically */
            gap: 8px; /* Reduced gap for compactness */
            align-items: flex-start; /* Align items to the start */
        }
        .action-form select, .action-form .btn {
            padding: 10px 18px; /* Larger form elements */
            font-size: 0.9em;
            border-radius: var(--border-radius-sm);
            border: 1px solid #ccc;
            background-color: #fff;
            width: 100%; /* Full width within cell */
            box-sizing: border-box; /* Include padding/border in width */
        }
        .action-form select:focus {
            border-color: var(--color-secondary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
        }
        .action-form .btn {
            margin-top: 5px; /* Small margin above button */
        }

        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .container {
                padding: 0 20px;
            }
            .booking-table th, .booking-table td {
                padding: 15px;
            }
        }

        @media (max-width: 992px) {
            header {
                flex-direction: column;
                padding: 20px;
                text-align: center;
            }
            header .logo-container {
                flex-direction: column;
                gap: 15px;
            }
            header .admin-info {
                width: 100%;
                justify-content: center;
            }
            .admin-navbar ul {
                flex-direction: column; /* Stack nav items vertically */
                gap: 10px; /* Smaller gap */
            }
            .admin-navbar ul li a {
                width: calc(100% - 30px); /* Full width minus padding */
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
            .booking-table-container {
                padding: 25px;
            }
            .booking-table th, .booking-table td {
                padding: 12px;
            }
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 2em;
            }
            .booking-table thead {
                display: none; /* Hide table headers on small screens */
            }
            .booking-table, .booking-table tbody, .booking-table tr, .booking-table td {
                display: block;
                width: 100%;
            }
            .booking-table tr {
                margin-bottom: 20px;
                border: 1px solid #ddd;
                border-radius: var(--border-radius-lg); /* Larger radius for cards */
                box-shadow: var(--shadow-subtle);
                padding: 15px; /* Padding for the card itself */
            }
            .booking-table td {
                text-align: right;
                padding-left: 50%;
                position: relative;
                border: none;
                border-bottom: 1px solid #eee;
                padding-top: 10px;
                padding-bottom: 10px;
            }
            .booking-table td:last-child {
                border-bottom: none;
            }
            .booking-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                width: calc(50% - 30px);
                padding-right: 10px;
                white-space: nowrap;
                font-weight: 700; /* Bolder label */
                color: var(--color-primary);
                font-size: 0.9em;
                text-align: left;
            }
            .action-form {
                margin-top: 15px;
                border-top: 1px solid #eee;
                padding-top: 15px;
                align-items: center; /* Center forms in card */
            }
            .action-form select, .action-form .btn {
                width: calc(100% - 20px); /* Adjust width for card padding */
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
                <li><a href="admin_panel.php" class="active"><i class="fas fa-calendar-check"></i> Manage Bookings</a></li>
                <li><a href="admin_add_service.php"><i class="fas fa-plus-circle"></i> Add New Service</a></li>
                <li><a href="admin_add_technician.php"><i class="fas fa-user-plus"></i> Add Technician</a></li>
                <li><a href="admin_feedback.php"><i class="fas fa-comments"></i> View Feedback</a></li>
                <!-- Add more admin links here as needed -->
            </ul>
        </nav>
    </header>

    <div class="container">
        <h1>Manage Customer Bookings</h1>
        <p class="subtitle">Review and manage all service requests from your customers.</p>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="booking-table-container">
            <?php if (!empty($all_bookings)): ?>
                <table class="booking-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Date & Time</th>
                            <th>Notes</th>
                            <th>Status</th>
                            <th>Assigned Tech</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_bookings as $booking): ?>
                            <tr>
                                <td data-label="Booking ID">#<?php echo htmlspecialchars($booking['booking_id']); ?></td>
                                <td data-label="Customer"><?php echo htmlspecialchars($booking['username']); ?></td>
                                <td data-label="Service"><?php echo htmlspecialchars($booking['service_title']); ?></td>
                                <td data-label="Date & Time">
                                    <?php echo htmlspecialchars($booking['booking_date']); ?> at <?php echo htmlspecialchars($booking['booking_time']); ?>
                                </td>
                                <td data-label="Notes"><?php echo htmlspecialchars($booking['notes'] ?: 'N/A'); ?></td>
                                <td data-label="Status">
                                    <span class="status-badge <?php echo htmlspecialchars($booking['status']); ?>">
                                        <?php echo htmlspecialchars($booking['status']); ?>
                                    </span>
                                </td>
                                <td data-label="Assigned Tech">
                                    <?php
                                    // Display technician name and specialty if assigned, otherwise "Not Assigned"
                                    if ($booking['technician_name']) {
                                        echo htmlspecialchars($booking['technician_name']) . " (" . htmlspecialchars($booking['technician_specialty']) . ")";
                                        echo "<br><small>Ph: " . htmlspecialchars($booking['technician_phone']) . "</small>";
                                    } else {
                                        echo "Not Assigned";
                                    }
                                    ?>
                                </td>
                                <td data-label="Actions">
                                    <div class="action-form">
                                        <?php if ($booking['status'] == 'Pending'): ?>
                                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                                <input type="hidden" name="action" value="assign_technician">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <select name="technician_id" required>
                                                    <option value="">Assign Technician</option>
                                                    <?php foreach ($technicians as $tech): ?>
                                                        <option value="<?php echo $tech['id']; ?>">
                                                            <?php echo htmlspecialchars($tech['name']); ?> (<?php echo htmlspecialchars($tech['specialty']); ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn btn-primary">Assign & Confirm</button>
                                            </form>
                                        <?php endif; ?>

                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                            <select name="status" onchange="this.form.submit()">
                                                <option value="">Update Status</option>
                                                <option value="Pending" <?php echo ($booking['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Confirmed" <?php echo ($booking['status'] == 'Confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="Completed" <?php echo ($booking['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                                <option value="Cancelled" <?php echo ($booking['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <!-- The submit button is removed as the status update is now triggered by onchange -->
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; font-size: 1.2em; color: #777; margin-top: 50px;">
                    No bookings to manage at the moment.
                    <?php if (empty($all_bookings) && !empty($message) && strpos($message, 'Error fetching bookings') !== false): ?>
                        <br><strong>Potential Issue:</strong> Please check the error message above for database connection or query issues. Also, ensure that the `user_id` and `service_id` in your `bookings` table have corresponding valid entries in the `users` and `services` tables respectively, as the display relies on these `JOIN` operations.
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
