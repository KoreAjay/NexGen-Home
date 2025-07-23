<?php
// Start the session at the very beginning of the file.
// This is crucial for managing user sessions across pages.
session_start();

// Include the database connection file.
// Ensure 'db_connect.php' is in the same directory as this file.
require_once 'db_connect.php';

// Check if the user is logged in
$is_logged_in = (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true);
$current_username = $is_logged_in ? htmlspecialchars($_SESSION["username"]) : '';
$current_user_id = $is_logged_in ? $_SESSION["id"] : null; // Get user ID if logged in

// Variable to store testimonials
$active_testimonials = [];

// Fetch testimonials with 'active' status
$sql_testimonials = "SELECT quote, author_name, author_location, stars, avatar_url FROM testimonials WHERE display_status = 'active' ORDER BY id DESC"; // Order by most recent active feedback first
$result_testimonials = $conn->query($sql_testimonials);

if ($result_testimonials && $result_testimonials->num_rows > 0) {
    while ($row = $result_testimonials->fetch_assoc()) {
        $active_testimonials[] = $row;
    }
    $result_testimonials->free();
}

// --- Fetch User Bookings (New Section) ---
$user_bookings = [];
if ($is_logged_in && $current_user_id) {
    $sql_user_bookings = "SELECT
                            b.id AS booking_id,
                            s.title AS service_title,
                            b.booking_date,
                            b.booking_time,
                            b.notes,
                            b.status,
                            t.name AS technician_name,
                            t.contact_phone AS technician_phone,
                            t.specialty AS technician_specialty
                         FROM bookings b
                         JOIN users u ON b.user_id = u.id
                         JOIN services s ON b.service_id = s.id
                         LEFT JOIN technicians t ON b.technician_id = t.id
                         WHERE b.user_id = ?
                         ORDER BY b.booking_date ASC, b.booking_time ASC";

    if ($stmt_user_bookings = $conn->prepare($sql_user_bookings)) {
        $stmt_user_bookings->bind_param("i", $current_user_id);
        if ($stmt_user_bookings->execute()) {
            $result_user_bookings = $stmt_user_bookings->get_result();
            if ($result_user_bookings->num_rows > 0) {
                while ($row = $result_user_bookings->fetch_assoc()) {
                    $user_bookings[] = $row;
                }
            }
            $result_user_bookings->free();
        } else {
            // Log this error, but don't die, as it's not critical for the whole page
            error_log("Error fetching user bookings: " . $stmt_user_bookings->error);
        }
        $stmt_user_bookings->close();
    } else {
        error_log("Database error preparing user bookings statement: " . $conn->error);
    }
}
// --- End Fetch User Bookings ---

// Close the database connection here if it was opened for the login process
// If you have a persistent connection, this might need adjustment.
// For simplicity, assuming $conn is closed at the very end of the script.
// $conn->close(); // This was moved to the very end of the HTML for consistency.

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexGen Home: Future of Home Services</title>
    <!-- Google Fonts for a distinct modern look -->
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Base Variables & Utilities - Updated for a premium feel */
        :root {
            --color-primary: #212121; /* Deep Charcoal */
            --color-secondary: #C59D5F; /* Muted Gold/Bronze */
            --color-accent: #00796B; /* Dark Cyan/Teal */
            --color-text-dark: #333333; /* Soft Black */
            --color-text-light: #F5F5F5; /* Off-white */
            --color-bg-light: #F8F8F8; /* Very light grey */
            --color-bg-dark: #363636; /* Slightly lighter dark grey */
            --gradient-primary: linear-gradient(135deg, var(--color-primary) 0%, var(--color-bg-dark) 100%);
            --shadow-subtle: 0 4px 15px rgba(0, 0, 0, 0.08);
            --shadow-elevated: 0 10px 30px rgba(0, 0, 0, 0.15);
            --border-radius-lg: 18px;
            --border-radius-sm: 8px;
            --transition-fast: 0.25s ease-out;
            --transition-normal: 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            --header-height: 80px;
        }

        /* General & Typography */
        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'IBM Plex Sans', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            color: var(--color-text-dark);
            line-height: 1.7;
            background-color: var(--color-bg-light);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        a {
            text-decoration: none;
            color: var(--color-secondary);
            transition: color var(--transition-fast);
        }
        a:hover {
            color: var(--color-accent);
        }

        h1, h2, h3, h4 {
            font-family: 'Playfair Display', serif;
            color: var(--color-text-dark);
            line-height: 1.25;
            margin-bottom: 0.6em;
        }

        h1 { font-size: 4.2em; font-weight: 700; }
        h2 { font-size: 3.2em; font-weight: 700; }
        h3 { font-size: 2.3em; font-weight: 700; }
        p { font-size: 1.15em; margin-bottom: 1em; } /* Slightly larger base font */

        .container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 35px;
        }

        section {
            padding: 120px 0; /* More vertical gap */
            position: relative;
            z-index: 1; /* Ensure content is above any background effects */
        }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }

        /* Buttons */
        .btn {
            display: inline-flex; /* Use flex for icon alignment */
            align-items: center;
            justify-content: center;
            padding: 16px 40px; /* Larger buttons */
            border-radius: var(--border-radius-lg);
            font-weight: 600;
            font-size: 1.15em;
            cursor: pointer;
            transition: all var(--transition-normal);
            border: none;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap; /* Prevent button text from wrapping prematurely */
        }

        .btn i {
            margin-right: 12px;
            font-size: 1.2em;
        }

        .btn-primary {
            background-color: var(--color-primary);
            color: var(--color-text-light);
            box-shadow: var(--shadow-elevated);
        }
        .btn-primary:hover {
            background-color: var(--color-secondary);
            transform: translateY(-5px); /* More pronounced lift */
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.3);
        }

        .btn-secondary {
            background-color: var(--color-secondary);
            color: #fff;
        }
        .btn-secondary:hover {
            background-color: var(--color-primary);
            transform: translateY(-5px);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.3);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--color-primary);
            border: 2px solid var(--color-primary);
            padding: 14px 38px; /* Adjust for border */
        }
        .btn-outline:hover {
            background-color: var(--color-primary);
            color: var(--color-text-light);
            transform: translateY(-5px);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.3);
        }

        /* Header */
        header {
            background-color: #fff;
            padding: 22px 0; /* Slightly larger padding */
            box-shadow: var(--shadow-subtle);
            position: fixed; /* Fixed header */
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; /* Allows items to wrap on smaller screens */
            gap: 20px; /* Gap between logo and right group */
        }

        .logo {
            font-size: 2.5em; /* Larger logo */
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

        .header-right-group { /* New wrapper for navigation, auth buttons, and toggle */
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

        /* Mobile Menu Toggle */
        .menu-toggle {
            display: none; /* Hidden by default, shown on mobile */
            font-size: 2em;
            color: var(--color-primary);
            cursor: pointer;
        }

        /* Auth buttons and welcome message styling */
        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 20px;
            white-space: nowrap; /* Prevent wrapping */
        }

        .auth-buttons .welcome-message {
            font-weight: 600;
            color: var(--color-primary);
            font-size: 1.1em;
        }

        /* Hero Section - Image Slideshow */
        .hero-section {
            background: var(--gradient-primary);
            color: var(--color-text-light);
            padding: calc(var(--header-height) + 100px) 0 100px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            min-height: 85vh;
            text-align: center;
        }

        .hero-image-slideshow {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            overflow: hidden;
            z-index: 0;
        }

        .hero-image-slideshow img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0;
            transition: opacity 1.5s ease-in-out; /* Slower transition for images */
        }

        .hero-image-slideshow img.active {
            opacity: 1;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(33, 33, 33, 0.7); /* Dark overlay for text readability, matches new primary color */
            z-index: 1;
        }

        .hero-slideshow-controls {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            transform: translateY(-50%);
            padding: 0 20px;
            box-sizing: border-box;
            z-index: 15; /* Above overlay and images */
        }

        .hero-slideshow-arrow {
            background-color: rgba(255, 255, 255, 0.2);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            font-size: 1.2em;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: background-color 0.3s ease;
        }

        .hero-slideshow-arrow:hover {
            background-color: rgba(255, 255, 255, 0.4);
        }

        .hero-slideshow-pagination {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 15;
        }

        .hero-pagination-dot {
            width: 10px;
            height: 10px;
            background-color: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .hero-pagination-dot.active {
            background-color: var(--color-accent);
            transform: scale(1.2);
        }


        .hero-content {
            position: relative;
            z-index: 10;
            max-width: 950px; /* Wider content area */
            margin: 0 auto;
            opacity: 0;
            animation: fadeInScale 1.5s forwards var(--transition-normal); /* Slower, more graceful entrance */
            animation-delay: 0.5s;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9) translateY(30px); } /* More pronounced starting state */
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .hero-content h1 {
            color: #fff;
            margin-bottom: 30px; /* More gap */
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.4); /* Stronger shadow for depth */
        }

        .hero-content p {
            font-size: 1.4em; /* Larger hero text */
            margin-bottom: 50px; /* More gap */
            color: var(--color-text-light);
            max-width: 850px;
            margin-left: auto;
            margin-right: auto;
        }

        .search-bar {
            background-color: #fff;
            padding: 18px; /* Larger padding */
            border-radius: var(--border-radius-lg);
            display: flex;
            align-items: center;
            width: 100%;
            max-width: 750px; /* Wider search bar */
            box-shadow: var(--shadow-elevated);
            margin-top: 50px; /* More gap */
            overflow: hidden;
            position: relative;
            flex-wrap: wrap; /* Allow elements inside to wrap */
        }

        .search-bar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px; /* Thicker focus line */
            background: linear-gradient(90deg, var(--color-secondary), var(--color-accent));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform var(--transition-normal);
        }
        .search-bar:focus-within::before {
            transform: scaleX(1);
        }

        .search-bar input {
            border: none;
            padding: 14px 25px; /* Larger input padding */
            font-size: 1.2em;
            flex-grow: 1;
            outline: none;
            color: var(--color-text-dark);
            border-radius: var(--border-radius-sm);
            transition: width var(--transition-normal);
            min-width: 150px; /* Ensure input doesn't shrink too much */
        }
        /* Style for disabled search input */
        .search-bar input:disabled {
            background-color: #f0f0f0;
            cursor: not-allowed;
            color: #888;
        }

        .search-bar button {
            background: var(--color-secondary);
            color: #fff;
            border: none;
            padding: 14px 30px; /* Larger button padding */
            border-radius: var(--border-radius-sm);
            font-size: 1.15em;
            font-weight: 600;
            display: flex;
            align-items: center;
            transition: all var(--transition-normal);
            flex-shrink: 0;
        }
        .search-bar button:hover {
            background-color: var(--color-primary);
            transform: scale(1.03); /* More pronounced scale */
        }
        /* Style for disabled search button */
        .search-bar button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            opacity: 0.7;
        }

        /* Feature Section (Example of unique layout) */
        .feature-section {
            background-color: #fff;
            padding-bottom: 80px; /* More vertical gap */
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); /* Slightly wider minmax */
            gap: 80px 50px; /* More gap between features */
            align-items: start;
        }

        .feature-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 35px; /* Larger padding */
            border-radius: var(--border-radius-lg);
            position: relative;
            background: var(--color-bg-light);
            box-shadow: var(--shadow-subtle);
            transition: transform var(--transition-fast), box-shadow var(--transition-fast);
        }
        .feature-item:hover {
            transform: translateY(-10px); /* More pronounced lift */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .feature-item .icon-box {
            background: var(--color-secondary);
            color: #fff;
            width: 100px; /* Larger icon box */
            height: 100px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 3.5em; /* Larger icon */
            margin-top: -70px; /* More overlap */
            margin-bottom: 30px; /* More gap */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25); /* Stronger shadow */
            border: 6px solid #fff; /* Thicker white border */
            position: relative;
            z-index: 2;
            transition: background-color var(--transition-fast);
        }
        .feature-item:hover .icon-box {
            background-color: var(--color-primary);
        }

        .feature-item h3 {
            margin-bottom: 18px; /* More gap */
            font-size: 2em;
            color: var(--color-primary);
        }
        .feature-item p {
            color: var(--color-text-dark);
            font-size: 1.05em;
        }

        /* About Us Section - New Content Section */
        .about-us-section {
            background-color: #fff;
        }

        .about-us-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px; /* More gap */
            align-items: center;
        }

        .about-us-content h2 {
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 15px;
        }
        .about-us-content h2::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100px;
            height: 5px;
            background-color: var(--color-secondary);
            border-radius: 3px;
        }

        .about-us-content p {
            margin-bottom: 25px; /* More gap */
            font-size: 1.1em;
            line-height: 1.8;
            color: var(--color-text-dark);
        }

        .about-us-content ul {
            list-style: none;
            padding: 0;
            margin-top: 30px;
        }

        .about-us-content ul li {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            font-size: 1.05em;
            color: var(--color-text-dark);
        }

        .about-us-content ul li i {
            color: var(--color-accent);
            margin-right: 12px;
            font-size: 1.2em;
            flex-shrink: 0;
            margin-top: 4px;
        }

        .about-us-image-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px; /* Gap between images */
        }
        .about-us-image-grid img {
            width: 100%;
            height: 250px; /* Fixed height for consistency */
            object-fit: cover;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-subtle);
            transition: transform var(--transition-fast), box-shadow var(--transition-fast);
        }
        .about-us-image-grid img:hover {
            transform: scale(1.03);
            box-shadow: var(--shadow-elevated);
        }
        .about-us-image-grid img:nth-child(even) {
            margin-top: 40px; /* Asymmetrical stacking */
        }

        /* Services Section - Dynamic, slightly asymmetrical */
        .services-section {
            background-color: var(--color-bg-dark);
            color: var(--color-text-light);
            position: relative;
            overflow: hidden;
            padding-bottom: 0; /* Custom separator */
        }

        .services-section h2, .services-section p {
            color: #fff;
        }

        .service-cards-wrapper {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 40px; /* More gap */
            margin-top: 70px; /* More gap */
        }

        .service-card {
            background-color: var(--color-primary);
            color: var(--color-text-light);
            padding: 40px; /* More padding */
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-elevated);
            transition: transform var(--transition-normal), box-shadow var(--transition-normal);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at top left, rgba(255, 255, 255, 0.1), transparent 70%); /* Stronger radial gradient */
            border-radius: var(--border-radius-lg);
            transition: opacity var(--transition-fast);
            opacity: 0;
        }
        .service-card:hover::before {
            opacity: 1;
        }

        .service-card:hover {
            transform: translateY(-15px) scale(1.03); /* More pronounced lift and scale */
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4); /* Stronger shadow */
        }

        .service-card .icon {
            font-size: 4em; /* Larger icons */
            color: var(--color-accent);
            margin-bottom: 20px; /* More gap */
            transition: transform var(--transition-fast);
        }
        .service-card:hover .icon {
            transform: rotate(7deg) scale(1.1); /* More pronounced rotation */
        }

        .service-card h3 {
            font-size: 2em;
            color: #fff;
            margin-bottom: 15px; /* More gap */
        }

        .service-card p {
            font-size: 1.05em;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.8;
        }

        /* Custom Wave Divider */
        .wave-divider {
            width: 100%;
            height: 120px; /* Adjust height for desired wave intensity */
            background-color: var(--color-bg-light); /* Next section's background */
            position: relative;
            margin-top: -1px; /* Overlap slightly to hide lines */
            z-index: 5;
        }

        .wave-divider::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--color-bg-dark); /* Previous section's background */
            clip-path: ellipse(100% 50% at 50% 100%); /* Bottom half ellipse for wave */
        }
        .wave-divider.top::before { /* For a top wave */
            background: var(--color-bg-light);
            clip-path: ellipse(100% 50% at 50% 0%); /* Top half ellipse for wave */
        }


        /* Why Choose Us Section - Grid with image */
        .why-choose-us-section {
            background-color: var(--color-bg-light);
            padding-top: 80px; /* Compensate for wave */
        }

        .why-choose-us-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr; /* Slightly wider image column */
            gap: 70px; /* More gap */
            align-items: center;
        }

        .why-choose-us-content h2 {
            margin-bottom: 35px; /* More gap */
            color: var(--color-primary);
            position: relative;
        }
        .why-choose-us-content h2::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -15px; /* Lower underline */
            width: 100px; /* Wider underline */
            height: 6px; /* Thicker underline */
            background-color: var(--color-secondary);
            border-radius: 3px;
        }

        .why-choose-us-content p {
            margin-bottom: 30px;
            font-size: 1.15em;
        }

        .why-choose-us-list {
            list-style: none;
            padding: 0;
            margin-top: 50px; /* More gap */
        }

        .why-choose-us-list li {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px; /* More gap */
        }

        .why-choose-us-list li i {
            font-size: 2em; /* Larger icons */
            color: var(--color-accent);
            margin-right: 20px; /* More gap */
            flex-shrink: 0;
            margin-top: 5px;
        }

        .why-choose-us-list li div h4 {
            font-size: 1.6em;
            margin-bottom: 8px; /* More gap */
            color: var(--color-text-dark);
        }

        .why-choose-us-list li div p {
            font-size: 1.05em;
            color: var(--color-text-dark);
            line-height: 1.7;
        }

        .why-choose-us-image {
            position: relative;
            padding: 30px; /* More padding */
            border-radius: var(--border-radius-lg);
            background-color: var(--color-primary);
            box-shadow: var(--shadow-elevated);
        }

        .why-choose-us-image img {
            max-width: 100%;
            height: auto;
            display: block;
            border-radius: var(--border-radius-lg);
            transform: translate(30px, 30px); /* More pronounced overlap */
            transition: transform var(--transition-normal);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3); /* Stronger shadow */
        }
        .why-choose-us-image:hover img {
            transform: translate(15px, 15px); /* Less overlap on hover */
        }

        /* Testimonials Section */
        .testimonials-section {
            background: linear-gradient(135deg, var(--color-bg-light) 0%, #E7EEF5 100%); /* Soft gradient */
            padding-bottom: 140px; /* More space for quote bubbles */
            position: relative;
            overflow: hidden;
        }

        .testimonials-section::before, .testimonials-section::after {
            content: '';
            position: absolute;
            background-color: rgba(0, 121, 107, 0.07); /* Matches new accent color */
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            filter: blur(60px); /* More blur */
        }
        .testimonials-section::before {
            width: 180px; height: 180px; /* Larger bubbles */
            top: 25%; left: -70px;
            animation: floatBubble 12s infinite ease-in-out; /* Slower animation */
        }
        .testimonials-section::after {
            width: 230px; height: 230px;
            bottom: 15%; right: -100px;
            animation: floatBubble 14s infinite ease-in-out reverse;
        }

        @keyframes floatBubble {
            0% { transform: translateY(0); opacity: 0.8; }
            50% { transform: translateY(-25px); opacity: 1; } /* More pronounced movement and opacity */
            100% { transform: translateY(0); opacity: 0.8; }
        }

        /* Testimonial Carousel Container */
        .testimonial-carousel-container {
            position: relative;
            width: 100%;
            max-width: 1200px; /* Adjust as needed */
            margin: 0 auto;
            padding: 0 50px; /* Padding for arrows */
            box-sizing: border-box;
        }

        .testimonial-slider {
            display: flex;
            overflow-x: scroll; /* Enable horizontal scrolling */
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 40px;
            margin-top: 80px;
            gap: 40px;
            justify-content: flex-start; /* Align items to start for snap effect */
            scroll-behavior: smooth; /* Smooth scrolling for navigation */
            scrollbar-width: none; /* Hide scrollbar for Firefox */
            -ms-overflow-style: none;  /* Hide scrollbar for IE and Edge */
        }
        .testimonial-slider::-webkit-scrollbar {
            display: none; /* Hide scrollbar for Chrome, Safari, and Opera */
        }


        .testimonial-card {
            flex: 0 0 420px; /* Fixed width for cards */
            scroll-snap-align: start; /* Snap to the start of each card */
            background-color: #fff;
            padding: 40px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-elevated);
            text-align: left;
            position: relative;
            border-top: 8px solid var(--color-accent);
            transition: transform var(--transition-normal);
            z-index: 1;
            flex-shrink: 0; /* Prevent cards from shrinking */
        }

        .testimonial-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.25);
        }

        .testimonial-card .quote-icon {
            position: absolute;
            top: 30px;
            right: 30px;
            font-size: 3.5em;
            color: rgba(0, 0, 0, 0.1);
            z-index: 0;
        }

        .testimonial-card .quote-text { /* New class for the quote content */
            font-style: italic;
            color: var(--color-text-dark);
            margin: 0 0 25px 0;
            font-size: 1.25em;
            position: relative;
            z-index: 1;
        }

        .testimonial-card .author-info {
            display: flex;
            align-items: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid rgba(0, 0, 0, 0.12);
        }

        .testimonial-card .author-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background-color: var(--color-bg-dark); /* Fallback background */
            margin-right: 18px;
            overflow: hidden;
            flex-shrink: 0;
            border: 3px solid var(--color-secondary);
        }
        .testimonial-card .author-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block; /* Ensure image behaves as block */
        }

        .testimonial-card .author-details strong {
            display: block;
            font-weight: 600;
            color: var(--color-primary);
            font-size: 1.15em;
        }

        .testimonial-card .author-details span {
            display: block;
            font-size: 0.95em;
            color: var(--color-text-dark);
            margin-top: 4px;
        }

        .testimonial-card .stars {
            color: var(--color-accent);
            font-size: 1.2em;
            margin-bottom: 12px;
            text-align: right;
        }

        /* Carousel Controls */
        .testimonial-carousel-controls {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            transform: translateY(-50%);
            padding: 0 20px; /* Adjust padding to align with container */
            box-sizing: border-box;
            pointer-events: none; /* Allow clicks to pass through to content by default */
        }

        .carousel-arrow {
            background-color: var(--color-primary);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 1.5em;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: var(--shadow-elevated);
            transition: background-color 0.3s ease, transform 0.2s ease;
            pointer-events: auto; /* Enable clicks on arrows */
            z-index: 10; /* Ensure arrows are above cards */
        }

        .carousel-arrow:hover {
            background-color: var(--color-secondary);
            transform: scale(1.1);
        }

        .carousel-arrow:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            opacity: 0.7;
            transform: none;
            box-shadow: none;
        }

        .carousel-pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 10px;
        }

        .pagination-dot {
            width: 12px;
            height: 12px;
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 50%;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .pagination-dot.active {
            background-color: var(--color-secondary);
            transform: scale(1.2);
        }


        /* Call to Action Section */
        .cta-section {
            background: linear-gradient(45deg, var(--color-secondary), var(--color-accent)); /* Vibrant blue gradient, using new accent */
            color: #fff;
            text-align: center;
            padding: 100px 0; /* More padding */
        }

        .cta-section h2 {
            color: #fff;
            margin-bottom: 25px; /* More gap */
        }

        .cta-section p {
            font-size: 1.4em; /* Larger text */
            margin-bottom: 50px; /* More gap */
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
            color: rgba(255, 255, 255, 0.95);
        }

        .cta-section .btn {
            background-color: var(--color-text-light); /* Off-white for contrast */
            color: var(--color-primary);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3); /* Stronger shadow */
            font-weight: 700;
        }
        .cta-section .btn:hover {
            background-color: #ffffff; /* Pure white on hover */
            transform: translateY(-6px); /* More pronounced lift */
            box-shadow: 0 18px 35px rgba(0, 0, 0, 0.4);
        }

        /* Footer */
        footer {
            background-color: var(--color-primary);
            color: var(--color-text-light);
            padding: 80px 0 40px; /* More padding */
            font-size: 1em; /* Slightly larger base font */
            position: relative;
            z-index: 10;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1.2fr;
            gap: 50px; /* More gap */
        }

        .footer-col h4 {
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 1.6em; /* Larger headings */
            color: #fff;
            margin-bottom: 30px; /* More gap */
            position: relative;
            padding-bottom: 12px; /* More padding */
        }
        .footer-col h4::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 70px; /* Wider underline */
            height: 5px; /* Thicker underline */
            background-color: var(--color-secondary);
            border-radius: 2px;
        }


        .footer-col ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-col ul li {
            margin-bottom: 15px; /* More gap */
        }

        .footer-col ul li a, .footer-col ul li span {
            color: var(--color-text-light);
            font-size: 0.95em;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .footer-col ul li i {
            color: var(--color-accent);
            font-size: 1.2em;
        }
        .footer-col ul li a:hover {
            color: var(--color-accent);
            text-decoration: underline;
        }

        .footer-col p {
            color: var(--color-text-light);
            font-size: 0.95em;
            line-height: 1.9; /* More line height */
        }

        .social-icons {
            margin-top: 30px; /* More gap */
            display: flex;
            gap: 25px;
            justify-content: flex-start; /* Default align left */
        }

        .social-icons a {
            color: #fff;
            font-size: 1.8em; /* Larger icons */
            transition: color var(--transition-fast), transform var(--transition-fast);
        }

        .social-icons a:hover {
            color: var(--color-accent);
            transform: scale(1.15); /* More pronounced scale */
        }

        .footer-bottom {
            text-align: center;
            margin-top: 60px; /* More gap */
            padding-top: 30px; /* More padding */
            border-top: 1px solid rgba(255, 255, 255, 0.15); /* Stronger border */
            font-size: 0.9em;
            color: rgba(255, 255, 255, 0.8);
        }
        .footer-bottom i {
            color: #e74c3c;
            margin: 0 6px;
        }

        /* Custom Modal / Message Box Styling */
        .modal-overlay {
            display: none; /* Hidden by default */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6); /* Dark semi-transparent background */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000; /* Above everything else */
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
            pointer-events: none; /* Allows clicks to pass through when hidden */
        }
        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto; /* Enables clicks when active */
        }
        .modal-content {
            background-color: #fff;
            color: var(--color-text-dark);
            padding: 30px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-elevated);
            max-width: 400px;
            width: 90%;
            text-align: center;
            transform: translateY(-50px); /* Start slightly above */
            opacity: 0;
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
        }
        .modal-overlay.active .modal-content {
            transform: translateY(0);
            opacity: 1;
        }
        .modal-content h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8em;
            color: var(--color-primary);
            margin-bottom: 15px;
        }
        .modal-content p {
            font-size: 1.1em;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        .modal-content .modal-close-btn {
            background-color: var(--color-secondary);
            color: #fff;
            padding: 10px 25px;
            border: none;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .modal-content .modal-close-btn:hover {
            background-color: var(--color-primary);
        }

        /* Back to Top Button */
        #backToTopBtn {
            display: none; /* Hidden by default */
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 99;
            font-size: 24px;
            border: none;
            outline: none;
            background-color: var(--color-secondary);
            color: white;
            cursor: pointer;
            padding: 15px 20px;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s, transform 0.3s, opacity 0.3s;
            opacity: 0.8;
        }
        #backToTopBtn:hover {
            background-color: var(--color-primary);
            transform: translateY(-5px);
            opacity: 1;
        }


        /* Responsive Design - Expert Level Breakpoints & Adjustments */
        @media (max-width: 1200px) {
            .container {
                padding: 0 25px;
            }
            h1 { font-size: 3.6em; }
            h2 { font-size: 2.8em; }
            h3 { font-size: 2em; }
            .footer-grid {
                grid-template-columns: 1.5fr 1fr 1fr;
            }
            .testimonial-carousel-container {
                padding: 0 20px; /* Less padding for arrows on smaller screens */
            }
        }

        @media (max-width: 992px) {
            h1 { font-size: 3em; }
            h2 { font-size: 2.4em; }
            h3 { font-size: 1.8em; }
            p { font-size: 1.05em; }

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

            .navbar {
                flex-wrap: nowrap; /* Prevent logo and toggle from wrapping on the same line */
                justify-content: space-between; /* Space out logo and right group */
            }
            .menu-toggle {
                display: block; /* Show hamburger icon */
            }
            /* Auth buttons on mobile */
            .auth-buttons {
                gap: 10px; /* Smaller gap */
                /* Keep them inline with menu toggle */
            }
            .auth-buttons .welcome-message {
                font-size: 0.95em;
            }
            .auth-buttons .btn {
                padding: 8px 15px;
                font-size: 0.9em;
            }


            .hero-section {
                padding: calc(var(--header-height) + 80px) 0 80px;
                min-height: unset; /* Allow content to dictate height */
            }
            .hero-content p {
                font-size: 1.2em;
            }

            .search-bar {
                flex-direction: column;
                padding: 15px;
            }
            .search-bar input {
                margin-bottom: 15px;
                width: calc(100% - 30px); /* Adjust for padding within input */
                font-size: 1.1em;
                padding: 10px 15px; /* Adjusted padding */
            }
            .search-bar button {
                width: 100%;
                justify-content: center;
                padding: 12px 20px;
            }

            section {
                padding: 100px 0; /* Adjust section padding */
            }

            .feature-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 90px 30px;
            }
            .feature-item .icon-box {
                margin-top: -80px; /* Adjust overlap */
            }

            .about-us-grid {
                grid-template-columns: 1fr;
                gap: 50px;
                text-align: center; /* Center content for single column */
            }
            .about-us-content h2::after {
                left: 50%;
                transform: translateX(-50%);
            }
            .about-us-image-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            .about-us-image-grid img {
                height: 300px;
            }
            .about-us-image-grid img:nth-child(even) {
                margin-top: 0; /* Remove asymmetrical stacking on mobile */
            }
            .about-us-content ul {
                text-align: left; /* Keep list left aligned */
                max-width: 500px; /* Constrain width for better readability */
                margin-left: auto;
                margin-right: auto;
            }
            .about-us-content .btn {
                margin-top: 20px; /* Add margin to button */
            }


            .service-cards-wrapper {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            }

            .wave-divider {
                height: 90px;
            }

            .why-choose-us-grid {
                grid-template-columns: 1fr; /* Stack columns */
                gap: 60px;
            }
            .why-choose-us-content {
                order: 2; /* Put content below image on mobile */
                text-align: center;
            }
            .why-choose-us-content h2::after {
                left: 50%;
                transform: translateX(-50%);
            }
            .why-choose-us-list li {
                justify-content: center;
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .why-choose-us-list li i {
                margin-bottom: 10px;
                margin-right: 0;
            }
            .why-choose-us-image {
                order: 1; /* Put image above content on mobile */
                padding: 20px;
            }
            .why-choose-us-image img {
                transform: translate(0, 0); /* Remove overlap effect on mobile */
            }
            .why-choose-us-image:hover img {
                transform: translate(0, 0);
            }

            .testimonial-slider {
                justify-content: flex-start;
                padding-left: 25px;
                padding-right: 25px;
                box-sizing: border-box;
            }
            .testimonial-card {
                flex: 0 0 calc(100% - 50px);
            }

            .footer-grid {
                grid-template-columns: 1fr; /* Stack footer columns */
                text-align: center;
            }
            .footer-col h4::after {
                left: 50%;
                transform: translateX(-50%);
            }
            .footer-col ul {
                text-align: center;
            }
            .footer-col ul li {
                justify-content: center;
            }
            .social-icons {
                justify-content: center;
            }
        }

        @media (max-width: 600px) {
            h1 { font-size: 2.8em; }
            h2 { font-size: 2.2em; }
            h3 { font-size: 1.6em; }

            .btn {
                padding: 10px 20px;
                font-size: 1em;
            }
            .btn i { margin-right: 8px; font-size: 1em; }

            .hero-section {
                padding: calc(var(--header-height) + 60px) 0 60px;
            }
            .hero-content p {
                font-size: 1.1em;
            }
            .search-bar input, .search-bar button {
                padding: 10px 15px;
                font-size: 1em;
            }
            .search-bar button {
                padding: 12px 20px;
                font-size: 1.05em;
            }

            section { padding: 80px 0; }

            .feature-item .icon-box {
                width: 90px;
                height: 90px;
                font-size: 3em;
                margin-top: -70px;
            }
            .about-us-image-grid img {
                height: 200px;
            }
            .service-card {
                padding: 30px;
            }
            .service-card .icon {
                font-size: 3.5em;
            }
            .wave-divider {
                height: 70px;
            }
            .testimonial-card {
                padding: 30px;
                flex: 0 0 calc(100% - 30px);
            }
            .testimonial-card .author-avatar {
                width: 50px;
                height: 50px;
            }
            .footer-grid {
                gap: 20px;
            }
             .logo {
                font-size: 2em; /* Smaller logo on very small screens */
            }
            .testimonial-carousel-controls {
                padding: 0 10px; /* Even less padding for arrows on smallest screens */
            }
        }
        @media (max-width: 480px) {
            h1 { font-size: 2.4em; }
            h2 { font-size: 2em; }
            h3 { font-size: 1.5em; }
            p { font-size: 1em; }

            .hero-section {
                padding: calc(var(--header-height) + 40px) 0 40px;
            }
            .hero-content p {
                font-size: 1em;
            }

            .btn {
                padding: 8px 15px;
                font-size: 0.9em;
            }
            .search-bar input {
                padding: 8px 12px;
                font-size: 0.95em;
            }
            .search-bar button {
                padding: 10px 15px;
                font-size: 0.95em;
            }

            section {
                padding: 60px 0; /* Reduce padding on smallest screens */
            }

            .feature-grid, .service-cards-wrapper {
                grid-template-columns: 1fr; /* Single column for features/services on smallest screens */
                gap: 60px; /* Adjust gap for single column */
            }
            .feature-item .icon-box {
                margin-top: -60px; /* Adjust overlap for smaller icons/boxes */
                width: 80px;
                height: 80px;
                font-size: 2.8em;
            }
            .feature-item {
                padding: 25px; /* Smaller padding */
            }
            .testimonial-card {
                flex: 0 0 calc(100% - 20px); /* Fill most of the screen, small margin */
                padding: 25px;
            }
            .testimonial-slider {
                padding-left: 10px;
                padding-right: 10px;
            }
            .about-us-image-grid img {
                height: 180px;
            }
        }

        /* New styles for My Bookings section */
        .my-bookings-section {
            background-color: #f8fbfd;
            padding: 80px 0;
            text-align: center;
        }

        .my-bookings-section h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.8em;
            color: var(--color-primary);
            margin-bottom: 20px;
            letter-spacing: -0.02em;
        }

        .my-bookings-section .booking-cards-wrapper {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .my-bookings-section .booking-card {
            background-color: #fff;
            padding: 30px;
            border-radius: var(--border-radius-lg);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1); /* Softer shadow */
            text-align: left;
            border-left: 8px solid; /* Dynamic border color */
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .my-bookings-section .booking-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 50%);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 0;
        }

        .my-bookings-section .booking-card:hover::before {
            opacity: 1;
        }

        .my-bookings-section .booking-card:hover {
            transform: translateY(-8px); /* More pronounced lift */
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2); /* Stronger shadow on hover */
        }

        /* Status-specific border colors */
        .my-bookings-section .booking-card.status-Pending { border-left-color: var(--color-secondary); } /* Gold */
        .my-bookings-section .booking-card.status-Confirmed { border-left-color: var(--color-accent); } /* Teal */
        .my-bookings-section .booking-card.status-Completed { border-left-color: #28a745; } /* Green */
        .my-bookings-section .booking-card.status-Cancelled { border-left-color: #dc3545; } /* Red */

        .my-bookings-section .booking-card h3 {
            font-size: 1.8em; /* Slightly larger title */
            color: var(--color-primary);
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }

        .my-bookings-section .booking-card p {
            font-size: 1.05em;
            color: #444; /* Darker text for readability */
            margin-bottom: 6px;
            position: relative;
            z-index: 1;
        }

        .my-bookings-section .booking-card .status-badge {
            display: inline-block;
            padding: 8px 15px; /* Larger padding */
            border-radius: var(--border-radius-sm);
            font-size: 0.85em; /* Slightly larger font */
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); /* Subtle shadow for badge */
            position: relative;
            z-index: 1;
        }
        .my-bookings-section .booking-card .status-badge.Pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .my-bookings-section .booking-card .status-badge.Confirmed {
            background-color: #cce5ff;
            color: #004085;
        }
        .my-bookings-section .booking-card .status-badge.Completed {
            background-color: #d4edda;
            color: #155724;
        }
        .my-bookings-section .booking-card .status-badge.Cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .my-bookings-section .booking-card .technician-details {
            margin-top: 20px; /* More space */
            padding-top: 20px;
            border-top: 1px solid #f0f0f0; /* Softer border */
            font-size: 0.98em;
            color: var(--color-text-dark);
            position: relative;
            z-index: 1;
        }
        .my-bookings-section .booking-card .technician-details strong {
            color: var(--color-primary);
        }
        .my-bookings-section .no-bookings-message {
            font-size: 1.2em; /* Slightly larger message */
            color: #777;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <header>
        <div class="container navbar">
            <div class="logo"><i class="fas fa-gem"></i>NexGen Home</div>
            <div class="header-right-group"> <!-- New wrapper for navigation, auth buttons, and toggle -->
                <nav id="mainNav">
                    <ul class="nav-links">
                        <li><a href="#about-us">About Us</a></li>
                        <li><a href="#features">Process</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#why-us">Why Us</a></li>
                        <li><a href="#testimonials">Reviews</a></li>
                        <?php if ($is_logged_in): ?>
                            <li><a href="#my-bookings">My Bookings</a></li>
                        <?php endif; ?>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </nav>
                <div class="auth-buttons">
                    <?php if ($is_logged_in): ?>
                        <span class="welcome-message">Welcome, <?php echo $current_username; ?>!</span>
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

    <section class="hero-section">
        <div class="hero-image-slideshow">
            <img src="https://placehold.co/1920x1080/212121/F5F5F5?text=Home+Renovation" alt="Home Renovation" data-src="https://placehold.co/1920x1080/212121/F5F5F5?text=Home+Renovation">
            <img src="https://placehold.co/1920x1080/212121/F5F5F5?text=Plumbing+Service" alt="Plumbing Service" class="active" data-src="https://placehold.co/1920x1080/212121/F5F5F5?text=Plumbing+Service">
            <img src="https://placehold.co/1920x1080/363636/F5F5F5?text=Home+Cleaning" alt="Home Cleaning" data-src="https://placehold.co/1920x1080/363636/F5F5F5?text=Home+Cleaning">
            <img src="https://placehold.co/1920x1080/C59D5F/212121?text=Professional+Painting" alt="Professional Painting" data-src="https://placehold.co/1920x1080/C59D5F/212121?text=Professional+Painting">
            <img src="https://placehold.co/1920x1080/00796B/F5F5F5?text=Smart+Home+Setup" alt="Smart Home Setup" data-src="https://placehold.co/1920x1080/00796B/F5F5F5?text=Smart+Home+Setup">
        </div>
        <div class="hero-overlay"></div>
        <div class="container hero-content">
            <h1>Revolutionizing Home Services for the Modern Era.</h1>
            <p>Experience unparalleled convenience, expert craftsmanship, and transparent pricing. Your home, elevated, in Pune and beyond.</p>
            <div class="search-bar">
                <input type="text" id="serviceSearchInput" placeholder="What service do you need? (e.g., 'Smart Home Setup', 'Deep Clean')" <?php echo $is_logged_in ? '' : 'disabled'; ?>>
                <button id="searchButton" <?php echo $is_logged_in ? '' : 'disabled'; ?>><i class="fas fa-search"></i> Search Now</button>
            </div>
        </div>
        <div class="hero-slideshow-controls">
            <button class="hero-slideshow-arrow prev-slide"><i class="fas fa-chevron-left"></i></button>
            <button class="hero-slideshow-arrow next-slide"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="hero-slideshow-pagination">
            <!-- Dots will be dynamically generated by JavaScript -->
        </div>
    </section>

    <?php if ($is_logged_in && !empty($user_bookings)): ?>
    <section id="my-bookings" class="my-bookings-section">
        <div class="container text-center">
            <h2>My Upcoming Bookings</h2>
            <p class="subtitle">Here are your scheduled services and their current status.</p>
            <div class="booking-cards-wrapper">
                <?php foreach ($user_bookings as $booking): ?>
                    <div class="booking-card status-<?php echo htmlspecialchars($booking['status']); ?>">
                        <h3><?php echo htmlspecialchars($booking['service_title']); ?></h3>
                        <p><strong>Booking ID:</strong> #<?php echo htmlspecialchars($booking['booking_id']); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($booking['booking_date']); ?></p>
                        <p><strong>Time:</strong> <?php echo htmlspecialchars($booking['booking_time']); ?></p>
                        <p><strong>Notes:</strong> <?php echo htmlspecialchars($booking['notes'] ?: 'N/A'); ?></p>
                        <span class="status-badge <?php echo htmlspecialchars($booking['status']); ?>">
                            <?php echo htmlspecialchars($booking['status']); ?>
                        </span>
                        <?php if ($booking['technician_name']): ?>
                            <div class="technician-details">
                                <p><strong>Assigned Technician:</strong> <?php echo htmlspecialchars($booking['technician_name']); ?> (<?php echo htmlspecialchars($booking['technician_specialty']); ?>)</p>
                                <p><strong>Contact:</strong> <?php echo htmlspecialchars($booking['technician_phone']); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="technician-details">
                                <p><strong>Assigned Technician:</strong> Not yet assigned</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php elseif ($is_logged_in && empty($user_bookings)): ?>
    <section id="my-bookings" class="my-bookings-section">
        <div class="container text-center">
            <h2>My Upcoming Bookings</h2>
            <p class="no-bookings-message">You currently have no upcoming bookings. Why not <a href="#services">explore our services</a>?</p>
        </div>
    </section>
    <?php endif; ?>

    <section id="about-us" class="about-us-section">
        <div class="container about-us-grid">
            <div class="about-us-content text-left">
                <h2>Our Story: Building the Future of Home Care</h2>
                <p>NexGen Home was founded on the principle that modern homes deserve modern solutions. Frustrated by inconsistent quality, opaque pricing, and a lack of specialized expertise in traditional home services, we set out to create a platform that connects homeowners with thoroughly vetted, highly skilled professionals across a spectrum of advanced and essential services.</p>
                <p>We believe in **transparency, reliability, and continuous innovation**. Our mission is to simplify home maintenance and upgrades, giving you peace of mind and more time to enjoy your living space.</p>
                <ul>
                    <li><i class="fas fa-check-circle"></i> Pioneers in smart home integration and sustainable solutions.</li>
                    <li><i class="fas fa-check-circle"></i> A network of over 500+ certified and background-verified experts.</li>
                    <li><i class="fas fa-check-circle"></i> Dedicated to customer satisfaction with a 24/7 support system.</li>
                    <li><i class="fas fa-check-circle"></i> Committed to eco-friendly practices in all our services.</li>
                </ul>
                <button class="btn btn-outline" id="learnMoreButton">Learn More About Us</button>
            </div>
            <div class="about-us-image-grid">
                <img src="https://placehold.co/400x250/E7EEF5/212121?text=Team+Working" alt="NexGen Home Team Working">
                <img src="https://placehold.co/400x250/E7EEF5/212121?text=Modern+Office" alt="Modern Collaboration Space">
                <img src="https://placehold.co/400x250/E7EEF5/212121?text=Sustainable+Home" alt="Eco-friendly Home">
                <img src="https://placehold.co/400x250/E7EEF5/212121?text=Happy+Customer" alt="Happy Customer">
            </div>  
        </div>
    </section>

    <section id="features" class="feature-section">
        <div class="container text-center">
            <h2 class="text-center">Our Seamless Process: From Click to Completion</h2>
            <p class="text-center" style="max-width: 900px; margin: 25px auto 100px;">Experience the simplicity of modern home service booking. We've streamlined every step to ensure efficiency and your absolute convenience.</p>
            <div class="feature-grid">
                <div class="feature-item">
                    <div class="icon-box"><i class="fas fa-desktop"></i></div>
                    <h3>Step 1: Discover & Select</h3>
                    <p>Browse our intuitive platform to find the exact service you need. Detailed descriptions and transparent pricing make selection effortless.</p>
                </div>
                <div class="feature-item">
                    <div class="icon-box"><i class="fas fa-calendar-check"></i></div>
                    <h3>Step 2: Schedule & Confirm</h3>
                    <p>Choose a time slot that fits your busy schedule. Our real-time booking system provides instant confirmation and reminders.</p>
                </div>
                <div class="feature-item">
                    <div class="icon-box"><i class="fas fa-user-shield"></i></div>
                    <h3>Step 3: Expert Arrives</h3>
                    <p>A certified, background-verified professional will arrive punctually, equipped with the right tools and expertise for the job.</p>
                </div>
                <div class="feature-item">
                    <div class="icon-box"><i class="fas fa-clipboard-check"></i></div>
                    <h3>Step 4: Quality Assurance</h3>
                    <p>We ensure every task is completed to the highest standards. Your satisfaction is our benchmark for success.</p>
                </div>
                <div class="feature-item">
                    <div class="icon-box"><i class="fas fa-credit-card"></i></div>
                    <h3>Step 5: Seamless Payment</h3>
                    <p>Enjoy secure and flexible payment options, with clear invoices delivered right to your inbox after service completion.</p>
                </div>
                <div class="feature-item">
                    <div class="icon-box"><i class="fas fa-headset"></i></div>
                    <h3>Step 6: Post-Service Support</h3>
                    <p>Our dedicated support team is always available to assist with any follow-up questions or concerns.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="services" class="services-section">
        <div class="container text-center">
            <h2>Our Signature Services: Crafted for Modern Homes</h2>
            <p style="max-width: 1000px; margin: 30px auto;">At NexGen Home, we offer a comprehensive portfolio of services designed to enhance the comfort, efficiency, and value of your home. From cutting-edge tech installations to meticulous maintenance, our experts deliver excellence.</p>
            <div class="service-cards-wrapper">
                <?php
                // Fetch services from the database
                $sql = "SELECT icon_class, title, description FROM services ORDER BY sort_order ASC, id ASC";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    // Output data of each row
                    while($row = $result->fetch_assoc()) {
                        echo '<div class="service-card">';
                        echo '    <div class="icon"><i class="' . htmlspecialchars($row["icon_class"]) . '"></i></div>';
                        echo '    <h3>' . htmlspecialchars($row["title"]) . '</h3>';
                        echo '    <p>' . htmlspecialchars($row["description"]) . '</p>';
                        echo '</div>';
                    }
                } else {
                    echo "<p>No services found.</p>";
                }
                ?>
            </div>
        </div>
    </section>
    <div class="wave-divider"></div>


    <section id="why-us" class="why-choose-us-section">
        <div class="container why-choose-us-grid">
            <div class="why-choose-us-content text-left">
                <h2>Why NexGen Home Is Your Ultimate Choice for Modern Living</h2>
                <p>Choosing a home service provider should be about more than just getting the job done. It's about trust, innovation, and a commitment to excellence that stands the test of time. NexGen Home embodies these values, providing a superior experience from start to finish.</p>
                <p>We invest in continuous training for our professionals, ensuring they are always at the forefront of the latest home technologies and service methodologies. Our quality control processes are rigorous, and our customer support is unmatched, making us the preferred partner for discerning homeowners in Pune.</p>
                <ul class="why-choose-us-list">
                    <li>
                        <i class="fas fa-user-graduate"></i>
                        <div>
                            <h4>Unrivaled Expertise & Training</h4>
                            <p>Our technicians aren't just skilled; they are continuously trained in the latest smart home and efficiency technologies.</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-hand-holding-usd"></i>
                        <div>
                            <h4>Value-Driven & Transparent Pricing</h4>
                            <p>We provide clear, detailed quotes with no hidden fees, ensuring you always know what to expect and receive exceptional value.</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-lightbulb"></i>
                        <div>
                            <h4>Innovative & Future-Ready Solutions</h4>
                            <p>We focus on implementing solutions that not only solve your current needs but also future-proof your home for evolving technologies.</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-headset"></i>
                        <div>
                            <h4>Exceptional Post-Service Support</h4>
                            <p>Our commitment extends beyond service completion with dedicated support to address any queries or ensure lasting satisfaction.</p>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="why-choose-us-image">
                <img src="https://placehold.co/600x400/212121/F8F8F8?text=Modern+Smart+Home" alt="Modern Smart Home Interior">
            </div>
        </div>
    </section>

    <section id="testimonials" class="testimonials-section text-center">
        <div class="container">
            <h2>Voices of Elevated Living: What Our Clients Say</h2>
            <p style="max-width: 900px; margin: 30px auto;">Our greatest reward is the satisfaction of our clients. Hear directly from homeowners in Pune who have experienced the NexGen Home difference in quality, reliability, and innovation.</p>
            <div class="testimonial-carousel-container">
                <div class="testimonial-slider">
                    <?php if (!empty($active_testimonials)): ?>
                        <?php foreach ($active_testimonials as $testimonial): ?>
                            <div class="testimonial-card">
                                <div class="quote-icon"><i class="fas fa-quote-right"></i></div>
                                <div class="stars">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <?php if ($i < $testimonial['stars']): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <!-- Removed blockquote tag as per user request -->
                                <p class="quote-text">"<?php echo htmlspecialchars($testimonial['quote']); ?>"</p>
                                <div class="author-info">
                                    <div class="author-avatar"><img src="<?php echo htmlspecialchars($testimonial['avatar_url'] ?: 'https://placehold.co/70x70/363636/F5F5F5?text=AV'); ?>" alt="Avatar" onerror="this.src='https://placehold.co/70x70/363636/F5F5F5?text=AV';"></div>
                                    <div class="author-details">
                                        <strong><?php echo htmlspecialchars($testimonial['author_name']); ?></strong>
                                        <span><?php echo htmlspecialchars($testimonial['author_location']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-testimonials-message">No active testimonials to display yet.</p>
                    <?php endif; ?>
                </div>
                <div class="testimonial-carousel-controls">
                    <button class="carousel-arrow prev-arrow"><i class="fas fa-chevron-left"></i></button>
                    <button class="carousel-arrow next-arrow"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div class="carousel-pagination">
                    <!-- Dots will be dynamically generated by JavaScript -->
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container">
            <h2>Ready to Experience the Future of Home Services in Pune?</h2>
            <p>Embrace effortless home management with NexGen Home. Book your first service today and elevate your living experience to the next level.</p>
            <button class="btn btn-cta" id="bookServiceButton">
                <i class="fas fa-arrow-right"></i> Book Your Service Now
            </button>
        </div>
    </section>

    <footer id="contact">
        <div class="container footer-grid">
            <div class="footer-col">
                <h4>About NexGen Home</h4>
                <p>Pioneering the next generation of home services in Pune, we combine cutting-edge technology with unparalleled expertise to deliver exceptional home care solutions. Our commitment to innovation and customer satisfaction sets us apart.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="#about-us"><i class="fas fa-angle-right"></i> About Us</a></li>
                    <li><a href="#features"><i class="fas fa-angle-right"></i> Our Process</a></li>
                    <li><a href="#services"><i class="fas fa-angle-right"></i> Our Services</a></li>
                    <li><a href="#why-us"><i class="fas fa-angle-right"></i> Why Choose Us</a></li>
                    <li><a href="#testimonials"><i class="fas fa-angle-right"></i> Client Reviews</a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> Careers</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Support</h4>
                <ul>
                    <li><a href="#"><i class="fas fa-angle-right"></i> Help Center</a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> FAQs</a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> Privacy Policy</a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> Terms & Conditions</a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> Service Warranty</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Get in Touch</h4>
                <ul>
                    <li><span><i class="fas fa-map-marker-alt"></i> NexGen Hub, 7th Floor, Elite Towers, SB Road, Pune - 411016, Maharashtra, India</span></li>
                    <li><span><i class="fas fa-phone"></i> +91 99887-NEXTGN (639846)</span></li>
                    <li><span><i class="fas fa-envelope"></i> info@nexgenhome.com</span></li>
                    <li><span><i class="fas fa-clock"></i> Mon-Sat: 9:00 AM - 8:00 PM</span></li>
                    <li><span><i class="fas fa-clock"></i> Sunday: 10:00 AM - 5:00 PM (Emergency Services Only)</span></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date("Y"); ?> NexGen Home. All rights reserved. | Built with <i class="fas fa-heart"></i> by Expert Developers in Pune.</p>
        </div>
    </footer>

    <!-- Custom Modal / Message Box HTML -->
    <div id="customModal" class="modal-overlay">
        <div class="modal-content">
            <h3 id="modalTitle"></h3>
            <p id="modalMessage"></p>
            <button class="modal-close-btn">OK</button>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button onclick="topFunction()" id="backToTopBtn" title="Go to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <?php $conn->close(); // Close the database connection at the end of the page ?>

    <script>
        // PHP variable to JavaScript for login status
        const isLoggedIn = <?php echo json_encode($is_logged_in); ?>;

        // Function to show custom modal
        function showCustomModal(title, message, redirectUrl = null) {
            const modalOverlay = document.getElementById('customModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalMessage = document.getElementById('modalMessage');
            const modalCloseBtn = modalOverlay.querySelector('.modal-close-btn');

            modalTitle.textContent = title;
            modalMessage.textContent = message;
            modalOverlay.classList.add('active');

            // Handle closing the modal
            const closeModal = () => {
                modalOverlay.classList.remove('active');
                modalCloseBtn.removeEventListener('click', closeModal); // Clean up listener
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                }
            };
            modalCloseBtn.addEventListener('click', closeModal);

            // Close modal if clicked outside (optional)
            modalOverlay.addEventListener('click', (e) => {
                if (e.target === modalOverlay) {
                    closeModal();
                }
            });
        }


        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();

                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                const headerOffset = document.querySelector('header').offsetHeight; // Get dynamic header height

                // Close mobile menu if open
                const navLinks = document.getElementById('mainNav').querySelector('.nav-links');
                if (navLinks.classList.contains('active')) {
                    navLinks.classList.remove('active');
                }

                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - headerOffset - 20, // Offset for fixed header + extra padding
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Highlight active nav link on scroll
        const sections = document.querySelectorAll('section');
        const navLinks = document.querySelectorAll('.nav-links a');
        const header = document.querySelector('header');

        window.addEventListener('scroll', () => {
            let current = '';
            const scrollY = window.pageYOffset;
            const headerHeight = header.offsetHeight;

            sections.forEach(section => {
                const sectionTop = section.offsetTop - headerHeight - 30; // More aggressive offset
                const sectionHeight = section.clientHeight;

                if (scrollY >= sectionTop && scrollY < sectionTop + sectionHeight) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href').includes(current)) {
                    link.classList.add('active');
                }
            });

            // Show/Hide Back to Top button
            if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                document.getElementById("backToTopBtn").style.display = "block";
            } else {
                document.getElementById("backToTopBtn").style.display = "none";
            }
        });

        // Dynamic Text for Hero Section - More engaging variations
        const heroHeading = document.querySelector('.hero-section h1');
        const heroParagraph = document.querySelector('.hero-section p');
        const messages = [
            { heading: "Revolutionizing Home Services for the Modern Era.", paragraph: "Experience unparalleled convenience, expert craftsmanship, and transparent pricing. Your home, elevated, in Pune and beyond." },
            { heading: "Smart Living Starts Here. Expert Setup & Support.", paragraph: "From intelligent automation to energy efficiency, we're building the future of home comfort in Pune." },
            { heading: "Unmatched Expertise. Flawless Execution. Total Peace of Mind.", paragraph: "Our verified professionals deliver precision and reliability for every home service, every time, across Pune." }
        ];

        let currentMessageIndex = 0;
        function updateHeroContent() {
            const currentContent = messages[currentMessageIndex];
            heroHeading.style.opacity = 0;
            heroParagraph.style.opacity = 0;

            setTimeout(() => {
                heroHeading.innerText = currentContent.heading;
                heroParagraph.innerText = currentContent.paragraph;
                heroHeading.style.opacity = 1;
                heroParagraph.style.opacity = 1;
                currentMessageIndex = (currentMessageIndex + 1) % messages.length;
            }, 600); // Fade duration
        }

        setInterval(updateHeroContent, 9000); // Change every 9 seconds
        updateHeroContent(); // Initial call

        // Search bar interaction - Restricted if not logged in
        document.getElementById('searchButton').addEventListener('click', function(event) {
            if (!isLoggedIn) {
                event.preventDefault(); // Prevent default button action
                showCustomModal('Access Denied', 'Please log in to use the search functionality.', 'login.php');
            } else {
                const searchTerm = document.getElementById('serviceSearchInput').value;
                if (searchTerm.trim()) {
                    showCustomModal('Search Initiated', `Initiating advanced search for: "${searchTerm.trim()}".\n\n(This is a demo. A real expert system would process your request for real-time results in Pune.)`);
                } else {
                    showCustomModal('Input Required', 'Please enter a service keyword to find your next solution!');
                }
            }
        });

        // "Learn More About Us" button interaction - Restricted if not logged in
        document.getElementById('learnMoreButton').addEventListener('click', function(event) {
            if (!isLoggedIn) {
                event.preventDefault();
                showCustomModal('Access Denied', 'Please log in or sign up to learn more about us and our services.', 'login.php');
            } else {
                showCustomModal('Information', 'Learning more about our values... (Demo)');
            }
        });

        // "Book Your Service Now" button interaction - Redirects to booking.php if logged in
        document.getElementById('bookServiceButton').addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default button action

            if (!isLoggedIn) {
                showCustomModal('Access Denied', 'Please log in to book a service.', 'login.php');
            } else {
                // If logged in, redirect directly to the booking page
                window.location.href = 'booking.php';
            }
        });


        // Simple scroll animation for elements on appearance (can be expanded with Intersection Observer API)
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1 // When 10% of the element is visible
        };

        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = `fadeInUp 0.8s ${entry.target.dataset.delay || '0s'} forwards`;
                    entry.target.style.opacity = 1; // Ensure opacity is set
                    entry.target.style.transform = 'translateY(0)'; // Ensure transform is reset
                    observer.unobserve(entry.target); // Stop observing once animated
                }
            });
        }, observerOptions);

        document.querySelectorAll('.feature-item, .service-card, .why-choose-us-content, .why-choose-us-image, .testimonial-card, .about-us-content, .about-us-image-grid img, .booking-card').forEach(el => { // Added .booking-card
            el.style.opacity = 0; // Hide initially
            el.style.transform = 'translateY(20px)'; // Start slightly lower
            observer.observe(el);
        });

        // Mobile Menu Toggle Logic
        const menuToggle = document.getElementById('menuToggle');
        const navLinksUL = document.getElementById('mainNav').querySelector('.nav-links');

        menuToggle.addEventListener('click', () => {
            navLinksUL.classList.toggle('active');
        });

        // Back to top button function
        function topFunction() {
            document.body.scrollTop = 0; // For Safari
            document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
        }

        // Testimonial Carousel Logic
        const testimonialSlider = document.querySelector('.testimonial-slider');
        const prevArrow = document.querySelector('.prev-arrow');
        const nextArrow = document.querySelector('.next-arrow');
        const paginationDotsContainer = document.querySelector('.carousel-pagination');
        const testimonialCards = document.querySelectorAll('.testimonial-card');

        if (testimonialCards.length > 0) {
            // Generate pagination dots
            testimonialCards.forEach((_, index) => {
                const dot = document.createElement('div');
                dot.classList.add('pagination-dot');
                dot.dataset.index = index;
                paginationDotsContainer.appendChild(dot);

                dot.addEventListener('click', () => {
                    const cardWidth = testimonialCards[0].offsetWidth + 40; // Card width + gap
                    testimonialSlider.scrollLeft = index * cardWidth;
                });
            });

            const paginationDots = document.querySelectorAll('.pagination-dot');

            // Function to update active dot and arrow states
            function updateCarouselState() {
                const scrollLeft = testimonialSlider.scrollLeft;
                const cardWidth = testimonialCards[0].offsetWidth + 40; // Card width + gap

                // Calculate current active index
                const currentIndex = Math.round(scrollLeft / cardWidth);

                // Update active dot
                paginationDots.forEach((dot, index) => {
                    if (index === currentIndex) {
                        dot.classList.add('active');
                    } else {
                        dot.classList.remove('active');
                    }
                });

                // Update arrow states
                prevArrow.disabled = currentIndex === 0;
                nextArrow.disabled = currentIndex === testimonialCards.length - 1;
            }

            // Event listeners for arrows
            prevArrow.addEventListener('click', () => {
                const cardWidth = testimonialCards[0].offsetWidth + 40; // Card width + gap
                testimonialSlider.scrollLeft -= cardWidth;
            });

            nextArrow.addEventListener('click', () => {
                const cardWidth = testimonialCards[0].offsetWidth + 40; // Card width + gap
                testimonialSlider.scrollLeft += cardWidth;
            });

            // Update state on scroll (for user-initiated scrolls)
            testimonialSlider.addEventListener('scroll', () => {
                // Debounce scroll event for performance
                clearTimeout(testimonialSlider.scrollTimeout);
                testimonialSlider.scrollTimeout = setTimeout(updateCarouselState, 100);
            });

            // Initial state update
            updateCarouselState();
        } else {
            // If no testimonials, hide controls and pagination
            if (prevArrow) prevArrow.style.display = 'none';
            if (nextArrow) nextArrow.style.display = 'none';
            if (paginationDotsContainer) paginationDotsContainer.style.display = 'none';
        }

        // Hero Image Slideshow Logic
        const heroSlideshow = document.querySelector('.hero-image-slideshow');
        const heroImages = heroSlideshow ? heroSlideshow.querySelectorAll('img') : [];
        const heroPrevArrow = document.querySelector('.hero-slideshow-arrow.prev-slide');
        const heroNextArrow = document.querySelector('.hero-slideshow-arrow.next-slide');
        const heroPaginationContainer = document.querySelector('.hero-slideshow-pagination');
        let currentHeroImageIndex = 0;
        let heroSlideshowInterval;

        function showHeroSlide(index) {
            if (heroImages.length === 0) return;

            // Ensure index loops correctly
            if (index >= heroImages.length) {
                currentHeroImageIndex = 0;
            } else if (index < 0) {
                currentHeroImageIndex = heroImages.length - 1;
            } else {
                currentHeroImageIndex = index;
            }

            // Deactivate all images and dots
            heroImages.forEach(img => img.classList.remove('active'));
            document.querySelectorAll('.hero-pagination-dot').forEach(dot => dot.classList.remove('active'));

            // Activate current image and dot
            heroImages[currentHeroImageIndex].classList.add('active');
            if (heroPaginationContainer.children[currentHeroImageIndex]) {
                heroPaginationContainer.children[currentHeroImageIndex].classList.add('active');
            }
        }

        function nextHeroSlide() {
            showHeroSlide(currentHeroImageIndex + 1);
        }

        function prevHeroSlide() {
            showHeroSlide(currentHeroImageIndex - 1);
        }

        function startHeroSlideshow() {
            if (heroImages.length > 1) {
                heroSlideshowInterval = setInterval(nextHeroSlide, 7000); // Change image every 7 seconds
            }
        }

        function stopHeroSlideshow() {
            clearInterval(heroSlideshowInterval);
        }

        // Initialize hero slideshow
        if (heroImages.length > 0) {
            // Generate pagination dots for hero slideshow
            heroImages.forEach((_, index) => {
                const dot = document.createElement('div');
                dot.classList.add('hero-pagination-dot');
                dot.dataset.index = index;
                heroPaginationContainer.appendChild(dot);

                dot.addEventListener('click', () => {
                    stopHeroSlideshow(); // Stop auto-play on manual navigation
                    showHeroSlide(index);
                    startHeroSlideshow(); // Restart auto-play
                });
            });

            showHeroSlide(currentHeroImageIndex); // Show initial slide
            startHeroSlideshow(); // Start auto-play

            // Add event listeners for hero slideshow arrows
            if (heroPrevArrow) {
                heroPrevArrow.addEventListener('click', () => {
                    stopHeroSlideshow();
                    prevHeroSlide();
                    startHeroSlideshow();
                });
            }
            if (heroNextArrow) {
                heroNextArrow.addEventListener('click', () => {
                    stopHeroSlideshow();
                    nextHeroSlide();
                    startHeroSlideshow();
                });
            }
        }

    </script>

</body>
</html>
