<?php
session_start();
include 'db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user's bookings
$stmt = $conn->prepare("
    SELECT b.*, h.name as hostel_name, h.location as hostel_location, r.room_type 
    FROM bookings b 
    JOIN hostels h ON b.hostel_id = h.id 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.user_id = ? 
    ORDER BY b.booking_date DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process booking cancellation
if(isset($_POST['cancel_booking']) && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];
    
    // Check if booking belongs to user
    $stmt = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ?");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($booking) {
        // Update booking status
        $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE id = ?");
        $stmt->execute([$booking_id]);
        
        // Redirect to refresh page
        header("Location: dashboard.php?cancelled=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Hilton Hostel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        header {
            background-color: #003580;
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }
        
        .logo span {
            color: #f5c710;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 20px;
        }
        
        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        nav ul li a:hover {
            color: #f5c710;
        }
        
        .user-menu {
            position: relative;
        }
        
        .user-menu-btn {
            display: flex;
            align-items: center;
            background: none;
            border: none;
            color: white;
            font-weight: 600;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .user-menu-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .user-menu-btn span {
            margin-right: 8px;
        }
        
        .user-menu-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 200px;
            z-index: 100;
            display: none;
        }
        
        .user-menu-dropdown.active {
            display: block;
        }
        
        .user-menu-dropdown ul {
            list-style: none;
            padding: 10px 0;
        }
        
        .user-menu-dropdown ul li {
            margin: 0;
        }
        
        .user-menu-dropdown ul li a {
            display: block;
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .user-menu-dropdown ul li a:hover {
            background-color: #f8f9fa;
            color: #003580;
        }
        
        .dashboard {
            flex: 1;
            padding: 40px 0;
        }
        
        .dashboard-header {
            margin-bottom: 30px;
        }
        
        .dashboard-header h1 {
            font-size: 32px;
            color: #003580;
            margin-bottom: 10px;
        }
        
        .dashboard-header p {
            color: #666;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 3fr;
            gap: 30px;
        }
        
        .dashboard-sidebar {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        .user-profile {
            text-align: center;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #003580;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 600;
            margin: 0 auto 15px;
        }
        
        .user-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .user-email {
            color: #666;
            font-size: 14px;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 10px;
        }
        
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            border-radius: 4px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu li a:hover {
            background-color: #f8f9fa;
            color: #003580;
        }
        
        .sidebar-menu li a.active {
            background-color: #003580;
            color: white;
        }
        
        .sidebar-menu li a span {
            margin-left: 10px;
        }
        
        .dashboard-content {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .content-header h2 {
            font-size: 24px;
            color: #003580;
        }
        
        .search-box {
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 8px 15px;
            width: 300px;
        }
        
        .search-box input {
            border: none;
            background: none;
            outline: none;
            width: 100%;
            padding: 5px;
            font-size: 14px;
        }
        
        .booking-tabs {
            display: flex;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        
        .booking-tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .booking-tab.active {
            border-bottom-color: #003580;
            color: #003580;
        }
        
        .booking-list {
            display: grid;
            gap: 20px;
        }
        
        .booking-card {
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .booking-header {
            background-color: #f8f9fa;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
        }
        
        .booking-id {
            font-weight: 600;
            color: #003580;
        }
        
        .booking-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .booking-body {
            padding: 20px;
            display: grid;
            grid-template-columns: 3fr 1fr;
            gap: 20px;
        }
        
        .booking-details h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #003580;
        }
        
        .booking-details p {
            margin-bottom: 5px;
            color: #666;
        }
        
        .booking-details p strong {
            color: #333;
        }
        
        .booking-actions {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 10px;
        }
        
        .booking-actions button, .booking-actions a {
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .view-btn {
            background-color: #003580;
            color: white;
            border: none;
            text-decoration: none;
        }
        
        .view-btn:hover {
            background-color: #002a66;
        }
        
        .cancel-btn {
            background-color: white;
            color: #dc3545;
            border: 1px solid #dc3545;
        }
        
        .cancel-btn:hover {
            background-color: #dc3545;
            color: white;
        }
        
        .no-bookings {
            text-align: center;
            padding: 40px 0;
            color: #666;
        }
        
        .no-bookings h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #003580;
        }
        
        .no-bookings p {
            margin-bottom: 20px;
        }
        
        .no-bookings a {
            display: inline-block;
            background-color: #003580;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .no-bookings a:hover {
            background-color: #002a66;
        }
        
        .notification {
            background-color: #d4edda;
            color: #155724;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        footer {
            background-color: #002a66;
            color: white;
            padding: 20px 0;
            margin-top: auto;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-links {
            display: flex;
            gap: 20px;
        }
        
        .footer-links a {
            color: #ddd;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: #f5c710;
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                text-align: center;
            }
            
            nav ul {
                margin: 15px 0;
                justify-content: center;
            }
            
            .user-menu {
                margin-top: 15px;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .booking-body {
                grid-template-columns: 1fr;
            }
            
            .content-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .search-box {
                width: 100%;
            }
            
            .footer-content {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">Hilton <span>Hostel</span></a>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="search.php">Hostels</a></li>
                    <li><a href="#">About</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
            </nav>
            <div class="user-menu">
                <button class="user-menu-btn" id="userMenuBtn">
                    <span><?php echo $user['username']; ?></span> ▼
                </button>
                <div class="user-menu-dropdown" id="userMenuDropdown">
                    <ul>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="#">Profile Settings</a></li>
                        <li><a href="#">Notifications</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <section class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1>My Dashboard</h1>
                <p>Welcome back, <?php echo $user['full_name']; ?>!</p>
            </div>
            
            <?php if(isset($_GET['cancelled'])): ?>
                <div class="notification">Your booking has been successfully cancelled.</div>
            <?php endif; ?>
            
            <div class="dashboard-grid">
                <div class="dashboard-sidebar">
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </div>
                        <h3 class="user-name"><?php echo $user['full_name']; ?></h3>
                        <p class="user-email"><?php echo $user['email']; ?></p>
                    </div>
                    
                    <ul class="sidebar-menu">
                        <li><a href="#" class="active">📋 <span>My Bookings</span></a></li>
                        <li><a href="#">👤 <span>Profile</span></a></li>
                        <li><a href="#">🔔 <span>Notifications</span></a></li>
                        <li><a href="#">❤️ <span>Saved Hostels</span></a></li>
                        <li><a href="#">⭐ <span>Reviews</span></a></li>
                        <li><a href="#">⚙️ <span>Settings</span></a></li>
                        <li><a href="logout.php">🚪 <span>Logout</span></a></li>
                    </ul>
                </div>
                
                <div class="dashboard-content">
                    <div class="content-header">
                        <h2>My Bookings</h2>
                        <div class="search-box">
                            <input type="text" placeholder="Search bookings..." id="searchBookings">
                            🔍
                        </div>
                    </div>
                    
                    <div class="booking-tabs">
                        <div class="booking-tab active" data-tab="all">All Bookings</div>
                        <div class="booking-tab" data-tab="upcoming">Upcoming</div>
                        <div class="booking-tab" data-tab="past">Past Bookings</div>
                        <div class="booking-tab" data-tab="cancelled">Cancelled</div>
                    </div>
                    
                    <?php if(count($bookings) > 0): ?>
                        <div class="booking-list">
                            <?php foreach($bookings as $booking): ?>
                                <div class="booking-card" data-status="<?php echo $booking['booking_status']; ?>">
                                    <div class="booking-header">
                                        <div class="booking-id">Booking #<?php echo $booking['id']; ?></div>
                                        <div class="booking-status status-<?php echo $booking['booking_status']; ?>">
                                            <?php echo ucfirst($booking['booking_status']); ?>
                                        </div>
                                    </div>
                                    <div class="booking-body">
                                        <div class="booking-details">
                                            <h3><?php echo $booking['hostel_name']; ?></h3>
                                            <p><strong>Location:</strong> <?php echo $booking['hostel_location']; ?></p>
                                            <p><strong>Room Type:</strong> <?php echo $booking['room_type']; ?></p>
                                            <p><strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></p>
                                            <p><strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></p>
                                            <p><strong>Guests:</strong> <?php echo $booking['guests']; ?></p>
                                            <p><strong>Total Price:</strong> $<?php echo number_format($booking['total_price'], 2); ?></p>
                                            <p><strong>Booked on:</strong> <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></p>
                                        </div>
                                        <div class="booking-actions">
                                            <a href="booking.php?hostel_id=<?php echo $booking['hostel_id']; ?>" class="view-btn">View Details</a>
                                            
                                            <?php if($booking['booking_status'] != 'cancelled'): ?>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <button type="submit" name="cancel_booking" class="cancel-btn">Cancel Booking</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-bookings">
                            <h3>No Bookings Found</h3>
                            <p>You haven't made any bookings yet. Start exploring hostels to book your stay!</p>
                            <a href="search.php">Find Hostels</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <p>&copy; 2023 Hilton Hostel. All rights reserved.</p>
                <div class="footer-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Help Center</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // User menu dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const userMenuBtn = document.getElementById('userMenuBtn');
            const userMenuDropdown = document.getElementById('userMenuDropdown');
            
            userMenuBtn.addEventListener('click', function() {
                userMenuDropdown.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!userMenuBtn.contains(event.target) && !userMenuDropdown.contains(event.target)) {
                    userMenuDropdown.classList.remove('active');
                }
            });
            
            // Booking tabs
            const bookingTabs = document.querySelectorAll('.booking-tab');
            const bookingCards = document.querySelectorAll('.booking-card');
            
            bookingTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs
                    bookingTabs.forEach(t => t.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    const tabType = this.getAttribute('data-tab');
                    
                    // Show/hide booking cards based on tab
                    bookingCards.forEach(card => {
                        const status = card.getAttribute('data-status');
                        
                        if (tabType === 'all') {
                            card.style.display = 'block';
                        } else if (tabType === 'upcoming' && status === 'confirmed') {
                            card.style.display = 'block';
                        } else if (tabType === 'past' && status === 'completed') {
                            card.style.display = 'block';
                        } else if (tabType === 'cancelled' && status === 'cancelled') {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
            
            // Search bookings
            const searchInput = document.getElementById('searchBookings');
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                bookingCards.forEach(card => {
                    const bookingText = card.textContent.toLowerCase();
                    
                    if (bookingText.includes(searchTerm)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>
