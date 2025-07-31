    <?php
    // Start the session at the very beginning of the file.
    session_start();

    // Include the database connection file.
    require_once 'db_connect.php';

    // Check if the user is logged in. If not, redirect to the login page.
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        header("location: login.php");
        exit;
    }



    $user_id = $_SESSION["id"];
    $current_username = htmlspecialchars($_SESSION["username"]);

    // Initialize variables for form data and messages for booking form
    $service_id = $booking_date = $booking_time = $notes = "";
    $service_err = $date_err = $time_err = $booking_err = "";
    $booking_success_message = "";

    // Process form submission for new booking
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Check if the form submitted is for booking (distinguish from other potential forms)
        if (isset($_POST['action']) && $_POST['action'] == 'book_service') {
            // Validate service selection
            if (empty(trim($_POST["service_id"]))) {
                $service_err = "Please select a service.";
            } else {
                $service_id = trim($_POST["service_id"]);
                // Validate if service_id exists in the services table
                $check_service_sql = "SELECT id FROM services WHERE id = ?";
                if ($stmt_check = $conn->prepare($check_service_sql)) {
                    $stmt_check->bind_param("i", $service_id);
                    $stmt_check->execute();
                    $stmt_check->store_result();
                    if ($stmt_check->num_rows == 0) {
                        $service_err = "Invalid service selected.";
                    }
                    $stmt_check->close();
                }
            }

            // Validate booking date
            if (empty(trim($_POST["booking_date"]))) {
                $date_err = "Please select a booking date.";
            } else {
                $booking_date = trim($_POST["booking_date"]);
                // Basic date format validation (YYYY-MM-DD)
                if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $booking_date)) {
                    $date_err = "Invalid date format.";
                }
            }

            // Validate booking time
            if (empty(trim($_POST["booking_time"]))) {
                $time_err = "Please select a booking time.";
            } else {
                $booking_time = trim($_POST["booking_time"]);
                // Basic time format validation (HH:MM)
                if (!preg_match("/^\d{2}:\d{2}$/", $booking_time)) {
                    $time_err = "Invalid time format.";
                }
            }

            // Sanitize notes
            $notes = trim($_POST["notes"]);

            // If no errors, insert booking into database
            if (empty($service_err) && empty($date_err) && empty($time_err) && empty($booking_err)) {
                $sql = "INSERT INTO bookings (user_id, service_id, booking_date, booking_time, notes) VALUES (?, ?, ?, ?, ?)";

                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("iiss", $user_id, $service_id, $booking_date, $booking_time, $notes);

                    if ($stmt->execute()) {
                        $booking_success_message = "Your service has been booked successfully! We will contact you shortly to confirm.";
                        // Clear form fields after successful submission
                        $service_id = $booking_date = $booking_time = $notes = "";
                    } else {
                        $booking_err = "Error: Could not process your booking. Please try again.";
                    }
                    $stmt->close();
                } else {
                    $booking_err = "Database error: Could not prepare statement.";
                }
            }
        }
    }

    // Fetch services for the dropdown (used by the booking form)
    $services = [];
    $sql_services = "SELECT id, title FROM services ORDER BY title ASC";
    $result_services = $conn->query($sql_services);
    if ($result_services && $result_services->num_rows > 0) {
        while ($row = $result_services->fetch_assoc()) {
            $services[] = $row;
        }
        // Free result set for services query
        $result_services->free();
    }

    // Fetch user's bookings (for the "My Bookings" section)
    $user_bookings = [];
    $sql_user_bookings = "SELECT b.id, s.title AS service_title, b.booking_date, b.booking_time, b.notes, b.status, b.created_at
                        FROM bookings b
                        JOIN services s ON b.service_id = s.id
                        WHERE b.user_id = ?
                        ORDER BY b.created_at DESC"; // Order by most recent booking first

    if ($stmt_bookings = $conn->prepare($sql_user_bookings)) {
        $stmt_bookings->bind_param("i", $user_id);
        $stmt_bookings->execute();
        $result_bookings = $stmt_bookings->get_result();
        if ($result_bookings->num_rows > 0) {
            while ($row = $result_bookings->fetch_assoc()) {
                $user_bookings[] = $row;
            }
        }
        $stmt_bookings->close();
    } else {
        // Handle error if prepared statement fails
        error_log("Failed to prepare statement for user bookings: " . $conn->error);
    }

    // Close the database connection at the very end of the script
    $conn->close();
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Book a Service - NexGen Home</title>
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
            /* Reusing styles from index.php for consistency */
            :root {
                --color-primary: #1A2E44; /* Deep, sophisticated dark blue-grey */
                --color-secondary: #007BFF; /* A vibrant, but not overly bright blue */
                --color-accent: #FFD700; /* Gold for highlights, a touch of luxury */
                --color-text-dark: #2C3E50;
                --color-text-light: #F0F4F8;
                --color-bg-light: #F8FBFD;
                --color-bg-dark: #293B4D; /* Slightly lighter than primary for contrast */
                --gradient-primary: linear-gradient(135deg, var(--color-primary) 0%, var(--color-bg-dark) 100%);
                --shadow-subtle: 0 4px 15px rgba(0, 0, 0, 0.08);
                --shadow-elevated: 0 10px 30px rgba(0, 0, 0, 0.15);
                --border-radius-lg: 18px;
                --border-radius-sm: 8px;
                --transition-fast: 0.25s ease-out;
                --transition-normal: 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
                --header-height: 80px;
            }

            body {
                font-family: 'IBM Plex Sans', sans-serif;
                background: var(--color-bg-light); /* Consistent with index.php's light background */
                color: var(--color-text-dark);
                display: flex;
                flex-direction: column;
                justify-content: flex-start; /* Align to top */
                align-items: center;
                min-height: 100vh;
                margin: 0;
                padding: 20px;
                box-sizing: border-box;
                overflow-x: hidden;
            }
            .main-content-wrapper {
                width: 100%;
                max-width: 1000px; /* Wider container for tabs */
                margin-top: calc(var(--header-height) + 20px); /* Space for fixed header */
                background-color: #fff;
                padding: 40px 30px;
                border-radius: var(--border-radius-lg);
                box-shadow: var(--shadow-elevated);
                text-align: center;
            }
            .main-content-wrapper h2 {
                font-family: 'Playfair Display', serif;
                color: var(--color-primary);
                margin-bottom: 25px;
                font-size: 2.2em;
            }
            .main-content-wrapper p {
                font-size: 1.05em;
                margin-bottom: 30px;
                color: var(--color-text-dark); /* Consistent text color */
            }
            .form-group {
                margin-bottom: 20px;
                text-align: left;
            }
            .form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 500;
                color: var(--color-primary);
            }
            .form-group input[type="text"],
            .form-group input[type="date"],
            .form-group input[type="time"],
            .form-group select,
            .form-group textarea {
                width: calc(100% - 24px); /* Account for padding */
                padding: 12px;
                border: 1px solid #ccc;
                border-radius: var(--border-radius-sm);
                font-size: 1em;
                transition: border-color 0.3s;
                color: var(--color-text-dark);
                background-color: var(--color-bg-light); /* Consistent light background for inputs */
            }
            .form-group input:focus,
            .form-group select:focus,
            .form-group textarea:focus {
                border-color: var(--color-secondary);
                outline: none;
                box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
            }
            .form-group textarea {
                resize: vertical; /* Allow vertical resizing */
                min-height: 100px;
            }
            .btn {
                background-color: var(--color-secondary);
                color: var(--color-text-light); /* Consistent button text color */
                padding: 14px 25px;
                border: none;
                border-radius: var(--border-radius-lg);
                font-size: 1.1em;
                font-weight: 600;
                cursor: pointer;
                transition: background-color 0.3s ease, transform 0.2s ease;
                width: 100%;
                margin-top: 15px;
            }
            .btn:hover {
                background-color: var(--color-primary);
                transform: translateY(-3px);
            }
            .error {
                color: #e74c3c; /* Red color for errors */
                margin-top: 5px;
                font-size: 0.9em;
                text-align: left;
            }
            .success {
                color: #28a745; /* Green for success */
                margin-top: 10px;
                font-size: 1em;
                font-weight: 500;
            }
            .back-link {
                margin-top: 25px;
                font-size: 0.95em;
                color: var(--color-text-dark); /* Consistent text color */
            }
            .back-link a {
                color: var(--color-secondary);
                text-decoration: none;
                font-weight: 500;
            }
            .back-link a:hover {
                text-decoration: underline;
            }

            /* Header for consistency */
            header {
                background-color: #fff;
                padding: 22px 0;
                box-shadow: var(--shadow-subtle);
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                z-index: 1000;
            }
            .navbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                max-width: 1300px;
                margin: 0 auto;
                padding: 0 35px;
                gap: 20px; /* Added gap for better spacing */
            }
            .logo {
                font-size: 2.5em;
                font-family: 'Playfair Display', serif;
                font-weight: 700;
                color: var(--color-primary);
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .logo i {
                color: var(--color-accent);
                font-size: 0.9em;
            }
            /* New styles for header-right-group */
            .header-right-group {
                display: flex;
                align-items: center;
                gap: 45px; /* Gap between nav links and auth buttons */
                margin-left: auto; /* Push this entire group to the right */
            }
            .nav-links {
                list-style: none;
                margin: 0;
                padding: 0;
                display: flex;
                gap: 45px; /* More gap in nav links */
            }

            .nav-links a {
                color: var(--color-text-dark);
                font-weight: 500;
                position: relative;
                padding: 5px 0;
                transition: color var(--transition-fast);
            }

            .nav-links a::after {
                content: '';
                position: absolute;
                width: 0;
                height: 4px; /* Thicker underline */
                background-color: var(--color-secondary);
                bottom: -10px; /* Lower underline */
                left: 50%;
                transform: translateX(-50%);
                transition: width var(--transition-fast);
            }

            .nav-links a:hover, .nav-links a.active {
                color: var(--color-primary);
            }

            .nav-links a:hover::after,
            .nav-links a.active::after {
                width: 100%;
            }

            .menu-toggle {
                display: none; /* Hidden by default, shown on mobile */
                font-size: 2em;
                color: var(--color-primary);
                cursor: pointer;
            }
            .auth-buttons {
                display: flex;
                align-items: center;
                gap: 20px;
                white-space: nowrap;
            }
            .welcome-message {
                font-size: 1.1em;
                font-weight: 500;
                color: var(--color-primary);
            }
            .btn-primary {
                background-color: var(--color-primary);
                color: var(--color-text-light);
                box-shadow: var(--shadow-subtle);
                padding: 10px 20px;
                border-radius: var(--border-radius-lg);
                font-size: 1em;
                text-transform: uppercase;
                letter-spacing: 0.03em;
                transition: all var(--transition-fast);
            }
            .btn-primary:hover {
                background-color: var(--color-secondary);
                transform: translateY(-2px);
                box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
            }
            .btn-outline {
                background-color: transparent;
                color: var(--color-primary);
                border: 2px solid var(--color-primary);
                padding: 8px 18px;
                border-radius: var(--border-radius-lg);
                font-size: 1em;
                text-transform: uppercase;
                letter-spacing: 0.03em;
                transition: all var(--transition-fast);
            }
            .btn-outline:hover {
                background-color: var(--color-primary);
                color: var(--color-text-light);
                transform: translateY(-2px);
                box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
            }

            /* Tab Navigation */
            .tab-nav {
                display: flex;
                justify-content: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #eee;
            }
            .tab-nav-item {
                padding: 15px 30px;
                cursor: pointer;
                font-size: 1.1em;
                font-weight: 600;
                color: #777;
                border-bottom: 3px solid transparent;
                transition: all 0.3s ease;
            }
            .tab-nav-item:hover {
                color: var(--color-primary);
            }
            .tab-nav-item.active {
                color: var(--color-secondary);
                border-bottom-color: var(--color-secondary);
            }
            .tab-content {
                display: none; /* Hidden by default */
            }
            .tab-content.active {
                display: block; /* Show active tab content */
            }

            /* My Bookings Section Styling */
            .booking-list {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 25px;
                margin-top: 30px;
            }
            .booking-card {
                background-color: var(--color-bg-light);
                border: 1px solid #ddd;
                border-radius: var(--border-radius-sm);
                padding: 20px;
                text-align: left;
                box-shadow: var(--shadow-subtle);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            .booking-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            }
            .booking-card h3 {
                font-family: 'IBM Plex Sans', sans-serif;
                font-size: 1.4em;
                color: var(--color-primary);
                margin-bottom: 10px;
            }
            .booking-card p {
                font-size: 0.95em;
                margin-bottom: 8px;
                color: #666;
                line-height: 1.5;
            }
            .booking-card p strong {
                color: var(--color-text-dark);
            }
            .booking-card .status {
                display: inline-block;
                padding: 5px 10px;
                border-radius: var(--border-radius-sm);
                font-size: 0.85em;
                font-weight: 600;
                margin-top: 10px;
            }
            .status.Pending {
                background-color: #ffeb3b;
                color: #8a6d3b;
            }
            .status.Confirmed {
                background-color: #d4edda;
                color: #155724;
            }
            .status.Completed {
                background-color: #e0e0e0;
                color: #424242;
            }
            .status.Cancelled {
                background-color: #f8d7da;
                color: #721c24;
            }
            .no-bookings-message {
                margin-top: 50px;
                font-size: 1.1em;
                color: #777;
            }

            /* Responsive Design */
            @media (max-width: 992px) {
                .navbar {
                    padding: 0 25px;
                    flex-wrap: nowrap; /* Prevent logo and right group from wrapping */
                    justify-content: space-between; /* Space out logo and right group */
                }
                .logo {
                    font-size: 2em;
                }
                .header-right-group {
                    gap: 20px; /* Smaller gap on mobile */
                    width: auto; /* Allow content to dictate width */
                    margin-left: auto; /* Still push to right */
                }
                .nav-links {
                    display: none; /* Hidden by default */
                    flex-direction: column;
                    width: 100%; /* Take full width when active */
                    background-color: #fff;
                    position: absolute;
                    top: var(--header-height);
                    left: 0;
                    padding: 20px 0;
                    box-shadow: var(--shadow-subtle);
                    border-top: 1px solid rgba(0,0,0,0.1);
                    transition: transform 0.3s ease-out;
                    transform: translateY(-100%);
                    z-index: 999;
                }
                .nav-links.active {
                    display: flex; /* Show when active */
                    transform: translateY(0%);
                }
                .nav-links li {
                    text-align: center;
                    margin: 10px 0;
                }
                .nav-links a::after {
                    left: 50%;
                    transform: translateX(-50%);
                }
                .menu-toggle {
                    display: block; /* Show hamburger icon */
                }
                .auth-buttons {
                    gap: 10px;
                }
                .auth-buttons .welcome-message {
                    font-size: 0.95em;
                }
                .auth-buttons .btn {
                    padding: 8px 15px;
                    font-size: 0.9em;
                }

                .main-content-wrapper {
                    padding: 30px 20px;
                    margin-top: calc(var(--header-height) + 10px);
                }
                .main-content-wrapper h2 {
                    font-size: 1.8em;
                }
                .form-group input, .form-group select, .form-group textarea, .btn {
                    padding: 10px;
                    font-size: 0.95em;
                }
                .tab-nav-item {
                    padding: 12px 20px;
                    font-size: 1em;
                }
                .booking-list {
                    grid-template-columns: 1fr; /* Stack cards on smaller screens */
                }
            }
            @media (max-width: 600px) {
                .navbar {
                    flex-direction: row; /* Keep logo and right group in a row */
                    align-items: center;
                    justify-content: space-between;
                    gap: 10px; /* Smaller gap */
                }
                .logo {
                    font-size: 2em;
                    justify-content: flex-start; /* Align logo to left */
                    width: auto; /* Allow logo to take natural width */
                }
                .header-right-group {
                    width: auto; /* Allow content to dictate width */
                    gap: 10px; /* Smaller gap */
                }
                .auth-buttons {
                    /* Adjust as needed, maybe hide welcome message on smallest screens */
                    gap: 5px;
                }
                .welcome-message {
                    display: none; /* Hide welcome message on very small screens */
                }
                .main-content-wrapper {
                    margin-top: calc(var(--header-height) + 10px); /* Adjust margin for header */
                    padding: 20px;
                }
                .main-content-wrapper h2 {
                    font-size: 1.8em;
                    margin-bottom: 20px;
                }
                .form-group {
                    margin-bottom: 20px;
                }
                .message-success, .message-error {
                    padding: 10px;
                    font-size: 0.9em;
                }
                .tab-nav {
                    flex-wrap: wrap; /* Allow tabs to wrap */
                }
                .tab-nav-item {
                    flex-grow: 1; /* Distribute space evenly */
                    text-align: center;
                    padding: 10px 15px;
                }
            }
        </style>
    </head>
    <body>
        <header>
            <div class="navbar">
                <div class="logo"><a href="index.php" style="color: inherit;"><i class="fas fa-gem"></i>NexGen Home</a></div>
                <div class="header-right-group">
                    <nav id="mainNav">
                        <ul class="nav-links">
                            <li><a href="index.php#about-us">About Us</a></li>
                            <li><a href="index.php#features">Process</a></li>
                            <li><a href="index.php#services">Services</a></li>
                            <li><a href="index.php#why-us">Why Us</a></li>
                            <li><a href="index.php#testimonials">Reviews</a></li>
                            <li><a href="index.php#my-bookings">My Bookings</a></li>
                            <li><a href="index.php#contact">Contact</a></li>
                        </ul>
                    </nav>
                    <div class="auth-buttons">
                        <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                            <span class="welcome-message">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</span>
                            <a href="logout.php" class="btn btn-primary">Logout</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-outline">Log In</a>
                            <a href="register.php" class="btn btn-primary">Sign Up</a>
                        <?php endif; ?>
                    </div>
                    <div class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </div>
                </div>
            </div>
        </header>

        <div class="main-content-wrapper">
            <div class="tab-nav">
                <div class="tab-nav-item active" data-tab="book-service">Book a Service</div>
                <div class="tab-nav-item" data-tab="my-bookings">My Bookings</div>
            </div>

            <div id="book-service" class="tab-content active">
                <h2>Book a Service</h2>
                <p>Fill out the form below to schedule your next home service with NexGen Home.</p>

                <?php
                if (!empty($booking_success_message)) {
                    echo '<p class="success">' . $booking_success_message . '</p>';
                }
                if (!empty($booking_err)) {
                    echo '<p class="error">' . $booking_err . '</p>';
                }
                ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <input type="hidden" name="action" value="book_service">
                    <div class="form-group">
                        <label for="service_id">Select Service</label>
                        <select name="service_id" id="service_id" required>
                            <option value="">-- Choose a Service --</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>" <?php echo ($service_id == $service['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($service['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($service_err)): ?><p class="error"><?php echo $service_err; ?></p><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="booking_date">Preferred Date</label>
                        <input type="date" name="booking_date" id="booking_date" value="<?php echo htmlspecialchars($booking_date); ?>" required min="<?php echo date('Y-m-d'); ?>">
                        <?php if (!empty($date_err)): ?><p class="error"><?php echo $date_err; ?></p><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="booking_time">Preferred Time</label>
                        <input type="time" name="booking_time" id="booking_time" value="<?php echo htmlspecialchars($booking_time); ?>" required>
                        <?php if (!empty($time_err)): ?><p class="error"><?php echo $time_err; ?></p><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="notes">Additional Notes / Specific Requests</label>
                        <textarea name="notes" id="notes" placeholder="e.g., 'Please bring specific tools for smart lock installation', 'Avoid 12-1 PM lunch break'" rows="5"><?php echo htmlspecialchars($notes); ?></textarea>
                    </div>

                    <div class="form-group">
                        <input type="submit" class="btn" value="Book Now">
                    </div>
                    <p class="back-link">
                        <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
                    </p>
                </form>
            </div>

            <div id="my-bookings" class="tab-content">
                <h2>My Bookings</h2>
                <p>Here's a summary of your service requests with NexGen Home.</p>

                <?php if (!empty($user_bookings)): ?>
                    <div class="booking-list">
                        <?php foreach ($user_bookings as $booking): ?>
                            <div class="booking-card">
                                <h3><?php echo htmlspecialchars($booking['service_title']); ?></h3>
                                <p><strong>Date:</strong> <?php echo htmlspecialchars($booking['booking_date']); ?></p>
                                <p><strong>Time:</strong> <?php echo htmlspecialchars($booking['booking_time']); ?></p>
                                <p><strong>Notes:</strong> <?php echo htmlspecialchars($booking['notes'] ?: 'No additional notes.'); ?></p>
                                <p><strong>Booked On:</strong> <?php echo date('M d, Y h:i A', strtotime(htmlspecialchars($booking['created_at']))); ?></p>
                                <span class="status <?php echo htmlspecialchars($booking['status']); ?>">
                                    <?php echo htmlspecialchars($booking['status']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-bookings-message">You haven't made any bookings yet. Go to "Book a Service" to get started!</p>
                <?php endif; ?>
                <p class="back-link" style="margin-top: 30px;">
                    <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
                </p>
            </div>
        </div>

        <script>
            // Function to show custom modal (consistent with index.php)
            function showCustomModal(title, message, redirectUrl = null) {
                const modalOverlay = document.getElementById('customModal');
                const modalTitle = document.getElementById('modalTitle');
                const modalMessage = document.getElementById('modalMessage');
                const modalCloseBtn = modalOverlay.querySelector('.modal-close-btn');

                modalTitle.textContent = title;
                modalMessage.textContent = message;
                modalOverlay.classList.add('active');

                const closeModal = () => {
                    modalOverlay.classList.remove('active');
                    modalCloseBtn.removeEventListener('click', closeModal);
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    }
                };
                modalCloseBtn.addEventListener('click', closeModal);

                modalOverlay.addEventListener('click', (e) => {
                    if (e.target === modalOverlay) {
                        closeModal();
                    }
                });
            }

            // Mobile Menu Toggle Logic (consistent with index.php)
            const menuToggle = document.getElementById('menuToggle');
            const navLinksUL = document.getElementById('mainNav').querySelector('.nav-links');

            if (menuToggle && navLinksUL) {
                menuToggle.addEventListener('click', () => {
                    navLinksUL.classList.toggle('active');
                });
            }

            // Populate service description based on selection (if you re-add description to services table)
            // const serviceSelect = document.getElementById('service_id');
            // const serviceDescriptionTextarea = document.getElementById('service_description');

            // function updateServiceDescription() {
            //     const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
            //     if (selectedOption && selectedOption.dataset.description) {
            //         serviceDescriptionTextarea.value = selectedOption.dataset.description;
            //     } else {
            //         serviceDescriptionTextarea.value = '';
            //     }
            // }

            // serviceSelect.addEventListener('change', updateServiceDescription);

            // Call on page load to set initial description if a service was pre-selected (e.g., after form submission with error)
            // document.addEventListener('DOMContentLoaded', updateServiceDescription);


            // Set min date for booking_date input to today
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
            const dd = String(today.getDate()).padStart(2, '0');
            const minDate = `${yyyy}-${mm}-${dd}`;
            document.getElementById('booking_date').setAttribute('min', minDate);

            // Display success/error messages using the custom modal
            <?php if ($booking_success_message): ?>
                showCustomModal('Booking Confirmed', '<?php echo $booking_success_message; ?>', 'index.php#my-bookings');
            <?php endif; ?>
            <?php if ($booking_err): ?>
                showCustomModal('Booking Failed', '<?php echo $booking_err; ?>');
            <?php endif; ?>

            // Tab navigation logic
            document.addEventListener('DOMContentLoaded', function() {
                const tabNavItems = document.querySelectorAll('.tab-nav-item');
                const tabContents = document.querySelectorAll('.tab-content');

                tabNavItems.forEach(item => {
                    item.addEventListener('click', function() {
                        tabNavItems.forEach(nav => nav.classList.remove('active'));
                        this.classList.add('active');

                        tabContents.forEach(content => content.classList.remove('active'));
                        const targetTabId = this.dataset.tab;
                        document.getElementById(targetTabId).classList.add('active');
                    });
                });

                // Check URL hash for initial tab display
                if (window.location.hash) {
                    const hash = window.location.hash.substring(1); // Remove '#'
                    const targetTab = document.querySelector(`.tab-nav-item[data-tab="${hash}"]`);
                    if (targetTab) {
                        targetTab.click(); // Simulate click to activate tab
                    }
                }
            });
        </script>
    </body>
    </html>
