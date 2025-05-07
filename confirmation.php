<?php
session_start();
include 'db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if booking_id is provided
if(!isset($_GET['booking_id'])) {
    header("Location: dashboard.php");
    exit();
}

$booking_id = intval($_GET['booking_id']);
$user_id = $_SESSION['user_id'];

// Get booking information
$stmt = $conn->prepare("
    SELECT b.*, h.name as hostel_name, h.location as hostel_location, h.image_url as hostel_image, 
    r.room_type, r.price_per_night, u.full_name, u.email 
    FROM bookings b 
    JOIN hostels h ON b.hostel_id = h.id 
    JOIN rooms r ON b.room_id = r.id 
    JOIN users u ON b.user_id = u.id 
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$booking) {
    header("Location: dashboard.php");
    exit();
}

// Calculate number of nights
$check_in = new DateTime($booking['check_in_date']);
$check_out = new DateTime($booking['check_out_date']);
$nights = $check_out->diff($check_in)->days;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - Hilton Hostel</title>
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
        
        .auth-buttons a {
            display: inline-block;
            padding: 8px 20px;
            background-color: #f5c710;
            color: #003580;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            margin-left: 10px;
            transition: all 0.3s;
        }
        
        .auth-buttons a:hover {
            background-color: #e0b60d;
            transform: translateY(-2px);
        }
        
        .confirmation-section {
            padding: 60px 0;
        }
        
        .confirmation-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
        }
        
        .confirmation-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .confirmation-title {
            font-size: 32px;
            color: #003580;
            margin-bottom: 15px;
        }
        
        .confirmation-message {
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
        }
        
        .booking-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .booking-id {
            font-size: 18px;
            color: #003580;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }
        
        .booking-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        
        .hostel-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .booking-info h3 {
            font-size: 24px;
            color: #003580;
            margin-bottom: 10px;
        }
        
        .booking-info p {
            margin-bottom: 10px;
            color: #666;
        }
        
        .booking-info p strong {
            color: #333;
        }
        
        .price-summary {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .price-total {
            display: flex;
            justify-content: space-between;
            font-weight: 600;
            font-size: 18px;
            color: #003580;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        
        .action-btn {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .primary-btn {
            background-color: #003580;
            color: white;
        }
        
        .primary-btn:hover {
            background-color: #002a66;
        }
        
        .secondary-btn {
            background-color: white;
            color: #003580;
            border: 1px solid #003580;
        }
        
        .secondary-btn:hover {
            background-color: #f8f9fa;
        }
        
        .contact-info {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
        }
        
        .contact-info p {
            margin-bottom: 10px;
            color: #666;
        }
        
        .contact-info a {
            color: #003580;
            text-decoration: none;
            font-weight: 600;
        }
        
        .contact-info a:hover {
            text-decoration: underline;
        }
        
        footer {
            background-color: #002a66;
            color: white;
            padding: 20px 0;
            margin-top: 40px;
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
            
            .auth-buttons {
                margin-top: 15px;
            }
            
            .booking-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 10px;
            }
            
            .action-btn {
                width: 100%;
                text-align: center;
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
            <div class="auth-buttons">
                <a href="dashboard.php">My Account</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </header>

    <section class="confirmation-section">
        <div class="container">
            <div class="confirmation-container">
                <div class="confirmation-icon">✅</div>
                <h1 class="confirmation-title">Booking Confirmed!</h1>
                <p class="confirmation-message">Your reservation has been successfully confirmed. We've sent a confirmation email to <?php echo $booking['email']; ?>.</p>
                
                <div class="booking-details">
                    <div class="booking-id">Booking #<?php echo $booking['id']; ?></div>
                    
                    <div class="booking-grid">
                        <img src="<?php echo $booking['hostel_image']; ?>" alt="<?php echo $booking['hostel_name']; ?>" class="hostel-image">
                        
                        <div class="booking-info">
                            <h3><?php echo $booking['hostel_name']; ?></h3>
                            <p><strong>Location:</strong> <?php echo $booking['hostel_location']; ?></p>
                            <p><strong>Room Type:</strong> <?php echo $booking['room_type']; ?></p>
                            <p><strong>Check-in:</strong> <?php echo date('F d, Y', strtotime($booking['check_in_date'])); ?></p>
                            <p><strong>Check-out:</strong> <?php echo date('F d, Y', strtotime($booking['check_out_date'])); ?></p>
                            <p><strong>Guests:</strong> <?php echo $booking['guests']; ?></p>
                            
                            <div class="price-summary">
                                <div class="price-row">
                                    <span>Room Price (<?php echo $nights; ?> nights)</span>
                                    <span>$<?php echo number_format($booking['price_per_night'] * $nights, 2); ?></span>
                                </div>
                                <div class="price-row">
                                    <span>Taxes & Fees</span>
                                    <span>$<?php echo number_format($booking['total_price'] - ($booking['price_per_night'] * $nights), 2); ?></span>
                                </div>
                                <div class="price-total">
                                    <span>Total</span>
                                    <span>$<?php echo number_format($booking['total_price'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="dashboard.php" class="action-btn primary-btn">View My Bookings</a>
                    <a href="search.php" class="action-btn secondary-btn">Book Another Hostel</a>
                </div>
                
                <div class="contact-info">
                    <p>If you have any questions about your booking, please contact us:</p>
                    <p>📧 <a href="mailto:support@hiltonhostel.com">support@hiltonhostel.com</a></p>
                    <p>📞 +1 (555) 123-4567</p>
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
</body>
</html>
