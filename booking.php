<?php
session_start();
include 'db.php';

// Check if hostel_id is provided
if(!isset($_GET['hostel_id'])) {
    header("Location: search.php");
    exit();
}

$hostel_id = intval($_GET['hostel_id']);
$check_in = isset($_GET['check_in']) ? $_GET['check_in'] : '';
$check_out = isset($_GET['check_out']) ? $_GET['check_out'] : '';
$guests = isset($_GET['guests']) ? intval($_GET['guests']) : 1;

// Get hostel information
$stmt = $conn->prepare("SELECT * FROM hostels WHERE id = ?");
$stmt->execute([$hostel_id]);
$hostel = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$hostel) {
    header("Location: search.php");
    exit();
}

// Get rooms for this hostel
$stmt = $conn->prepare("SELECT * FROM rooms WHERE hostel_id = ? ORDER BY price_per_night ASC");
$stmt->execute([$hostel_id]);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process booking form
$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_room'])) {
    // Check if user is logged in
    if(!isset($_SESSION['user_id'])) {
        header("Location: login.php?redirect=booking.php?hostel_id=$hostel_id");
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    $room_id = intval($_POST['room_id']);
    $check_in_date = $_POST['check_in_date'];
    $check_out_date = $_POST['check_out_date'];
    $guests = intval($_POST['guests']);
    $total_price = floatval($_POST['total_price']);
    
    // Validate input
    if(empty($check_in_date) || empty($check_out_date) || $guests < 1) {
        $error = "Please fill in all required fields";
    } else {
        // Check if room exists and is available
        $stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ? AND hostel_id = ?");
        $stmt->execute([$room_id, $hostel_id]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$room) {
            $error = "Selected room is not available";
        } elseif($room['available_rooms'] <= 0) {
            $error = "This room is fully booked";
        } else {
            // Create booking
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, hostel_id, room_id, check_in_date, check_out_date, guests, total_price, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')");
            $result = $stmt->execute([$user_id, $hostel_id, $room_id, $check_in_date, $check_out_date, $guests, $total_price]);
            
            if($result) {
                // Update room availability
                $stmt = $conn->prepare("UPDATE rooms SET available_rooms = available_rooms - 1 WHERE id = ?");
                $stmt->execute([$room_id]);
                
                $booking_id = $conn->lastInsertId();
                header("Location: confirmation.php?booking_id=$booking_id");
                exit();
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hostel['name']; ?> - Hilton Hostel</title>
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
            position: sticky;
            top: 0;
            z-index: 100;
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
        
        .hostel-header {
            background-color: #003580;
            color: white;
            padding: 60px 0;
        }
        
        .hostel-title {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .hostel-location {
            display: flex;
            align-items: center;
            font-size: 18px;
            margin-bottom: 20px;
        }
        
        .hostel-location::before {
            content: '📍';
            margin-right: 10px;
        }
        
        .hostel-rating {
            display: inline-block;
            background-color: white;
            color: #003580;
            padding: 5px 15px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 18px;
        }
        
        .booking-section {
            padding: 40px 0;
        }
        
        .booking-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .hostel-details {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .hostel-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
        }
        
        .hostel-content {
            padding: 30px;
        }
        
        .hostel-description {
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .hostel-amenities {
            margin-bottom: 30px;
        }
        
        .amenities-title {
            font-size: 24px;
            color: #003580;
            margin-bottom: 20px;
        }
        
        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .amenity {
            display: flex;
            align-items: center;
            font-size: 16px;
        }
        
        .amenity::before {
            margin-right: 10px;
        }
        
        .wifi::before {
            content: '📶';
        }
        
        .breakfast::before {
            content: '🍳';
        }
        
        .pool::before {
            content: '🏊';
        }
        
        .reception::before {
            content: '🔑';
        }
        
        .lounge::before {
            content: '🛋️';
        }
        
        .bar::before {
            content: '🍹';
        }
        
        .beach::before {
            content: '🏖️';
        }
        
        .hiking::before {
            content: '🥾';
        }
        
        .gym::before {
            content: '💪';
        }
        
        .workspace::before {
            content: '💻';
        }
        
        .cafe::before {
            content: '☕';
        }
        
        .fireplace::before {
            content: '🔥';
        }
        
        .booking-form {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
            position: sticky;
            top: 100px;
            height: fit-content;
        }
        
        .booking-form h2 {
            font-size: 24px;
            color: #003580;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #003580;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .price-summary {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
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
        
        .book-btn {
            width: 100%;
            background-color: #f5c710;
            color: #003580;
            border: none;
            padding: 15px;
            border-radius: 4px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .book-btn:hover {
            background-color: #e0b60d;
        }
        
        .rooms-section {
            padding: 40px 0;
        }
        
        .rooms-title {
            font-size: 32px;
            color: #003580;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .room-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s;
        }
        
        .room-card:hover {
            transform: translateY(-5px);
        }
        
        .room-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .room-details {
            padding: 20px;
        }
        
        .room-type {
            font-size: 20px;
            color: #003580;
            margin-bottom: 10px;
        }
        
        .room-capacity {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            color: #666;
        }
        
        .room-capacity::before {
            content: '👥';
            margin-right: 5px;
        }
        
        .room-description {
            color: #666;
            margin-bottom: 15px;
        }
        
        .room-price {
            font-size: 22px;
            font-weight: 600;
            color: #003580;
            margin-bottom: 15px;
        }
        
        .room-price span {
            font-size: 14px;
            color: #666;
            font-weight: normal;
        }
        
        .room-availability {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .availability-status {
            font-weight: 600;
        }
        
        .available {
            color: #28a745;
        }
        
        .limited {
            color: #ffc107;
        }
        
        .unavailable {
            color: #dc3545;
        }
        
        .select-btn {
            display: inline-block;
            background-color: #003580;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s;
            cursor: pointer;
            border: none;
        }
        
        .select-btn:hover {
            background-color: #002a66;
        }
        
        .select-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        .reviews-section {
            padding: 40px 0;
            background-color: #f8f9fa;
        }
        
        .reviews-title {
            font-size: 32px;
            color: #003580;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .review-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        .review-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .reviewer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
        }
        
        .reviewer-info h4 {
            margin-bottom: 5px;
        }
        
        .reviewer-location {
            font-size: 14px;
            color: #666;
        }
        
        .review-rating {
            color: #f5c710;
            margin-bottom: 10px;
        }
        
        .review-text {
            color: #666;
            line-height: 1.6;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        footer {
            background-color: #002a66;
            color: white;
            padding: 60px 0 20px;
            margin-top: 40px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-column h3 {
            font-size: 20px;
            margin-bottom: 20px;
            position: relative;
        }
        
        .footer-column h3::after {
            content: '';
            display: block;
            width: 40px;
            height: 3px;
            background-color: #f5c710;
            margin-top: 10px;
        }
        
        .footer-column p {
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: #ddd;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: #f5c710;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
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
            
            .hostel-image {
                height: 300px;
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
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php">My Account</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="signup.php">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <section class="hostel-header">
        <div class="container">
            <h1 class="hostel-title"><?php echo $hostel['name']; ?></h1>
            <p class="hostel-location"><?php echo $hostel['location']; ?></p>
            <div class="hostel-rating"><?php echo $hostel['rating']; ?>/5</div>
        </div>
    </section>

    <section class="booking-section">
        <div class="container">
            <div class="booking-grid">
                <div class="hostel-details">
                    <img src="<?php echo $hostel['image_url']; ?>" alt="<?php echo $hostel['name']; ?>" class="hostel-image">
                    <div class="hostel-content">
                        <div class="hostel-description">
                            <p><?php echo $hostel['description']; ?></p>
                        </div>
                        
                        <div class="hostel-amenities">
                            <h3 class="amenities-title">Amenities</h3>
                            <div class="amenities-grid">
                                <?php
                                    $amenities = explode(',', $hostel['amenities']);
                                    foreach($amenities as $amenity) {
                                        $amenity = trim($amenity);
                                        $class = strtolower(str_replace(' ', '-', $amenity));
                                        $class = str_replace(['/', '&'], '', $class);
                                        
                                        if(strpos($amenity, 'WiFi') !== false) {
                                            echo '<div class="amenity wifi">Free WiFi</div>';
                                        } elseif(strpos($amenity, 'Breakfast') !== false) {
                                            echo '<div class="amenity breakfast">Breakfast</div>';
                                        } elseif(strpos($amenity, 'Pool') !== false) {
                                            echo '<div class="amenity pool">Swimming Pool</div>';
                                        } elseif(strpos($amenity, 'Reception') !== false) {
                                            echo '<div class="amenity reception">24/7 Reception</div>';
                                        } elseif(strpos($amenity, 'Lounge') !== false) {
                                            echo '<div class="amenity lounge">Lounge</div>';
                                        } elseif(strpos($amenity, 'Bar') !== false) {
                                            echo '<div class="amenity bar">Bar</div>';
                                        } elseif(strpos($amenity, 'Beach') !== false) {
                                            echo '<div class="amenity beach">Beach Access</div>';
                                        } elseif(strpos($amenity, 'Hiking') !== false) {
                                            echo '<div class="amenity hiking">Hiking Trails</div>';
                                        } elseif(strpos($amenity, 'Gym') !== false) {
                                            echo '<div class="amenity gym">Fitness Center</div>';
                                        } elseif(strpos($amenity, 'Workspace') !== false) {
                                            echo '<div class="amenity workspace">Workspace</div>';
                                        } elseif(strpos($amenity, 'Cafe') !== false) {
                                            echo '<div class="amenity cafe">Cafe</div>';
                                        } elseif(strpos($amenity, 'Fireplace') !== false) {
                                            echo '<div class="amenity fireplace">Fireplace</div>';
                                        } else {
                                            echo '<div class="amenity">' . $amenity . '</div>';
                                        }
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="booking-form">
                    <h2>Book Your Stay</h2>
                    
                    <?php if(!empty($error)): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if(!empty($success)): ?>
                        <div class="success-message"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form action="booking.php?hostel_id=<?php echo $hostel_id; ?>" method="POST" id="bookingForm">
                        <input type="hidden" name="hostel_id" value="<?php echo $hostel_id; ?>">
                        
                        <div class="form-group">
                            <label for="room_id">Select Room</label>
                            <select id="room_id" name="room_id" required>
                                <option value="">-- Select Room --</option>
                                <?php foreach($rooms as $room): ?>
                                    <?php if($room['available_rooms'] > 0): ?>
                                        <option value="<?php echo $room['id']; ?>" data-price="<?php echo $room['price_per_night']; ?>">
                                            <?php echo $room['room_type']; ?> - $<?php echo number_format($room['price_per_night'], 2); ?> per night
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="check_in_date">Check In</label>
                            <input type="date" id="check_in_date" name="check_in_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo $check_in; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="check_out_date">Check Out</label>
                            <input type="date" id="check_out_date" name="check_out_date" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" value="<?php echo $check_out; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="guests">Guests</label>
                            <select id="guests" name="guests" required>
                                <option value="1" <?php echo $guests == 1 ? 'selected' : ''; ?>>1 Guest</option>
                                <option value="2" <?php echo $guests == 2 ? 'selected' : ''; ?>>2 Guests</option>
                                <option value="3" <?php echo $guests == 3 ? 'selected' : ''; ?>>3 Guests</option>
                                <option value="4" <?php echo $guests == 4 ? 'selected' : ''; ?>>4 Guests</option>
                                <option value="5" <?php echo $guests == 5 ? 'selected' : ''; ?>>5 Guests</option>
                            </select>
                        </div>
                        
                        <div class="price-summary">
                            <div class="price-row">
                                <span>Room Price</span>
                                <span id="roomPrice">$0.00</span>
                            </div>
                            <div class="price-row">
                                <span>Number of Nights</span>
                                <span id="numNights">0</span>
                            </div>
                            <div class="price-row">
                                <span>Taxes & Fees</span>
                                <span id="taxesFees">$0.00</span>
                            </div>
                            <div class="price-total">
                                <span>Total</span>
                                <span id="totalPrice">$0.00</span>
                            </div>
                        </div>
                        
                        <input type="hidden" name="total_price" id="totalPriceInput" value="0">
                        <button type="submit" name="book_room" class="book-btn">Book Now</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="rooms-section">
        <div class="container">
            <h2 class="rooms-title">Available Rooms</h2>
            <div class="rooms-grid">
                <?php foreach($rooms as $room): ?>
                    <div class="room-card">
                        <img src="https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=800&h=500" alt="<?php echo $room['room_type']; ?>" class="room-image">
                        <div class="room-details">
                            <h3 class="room-type"><?php echo $room['room_type']; ?></h3>
                            <p class="room-capacity">Max <?php echo $room['capacity']; ?> guests</p>
                            <p class="room-description"><?php echo $room['description']; ?></p>
                            <p class="room-price">$<?php echo number_format($room['price_per_night'], 2); ?> <span>per night</span></p>
                            <div class="room-availability">
                                <?php if($room['available_rooms'] > 5): ?>
                                    <span class="availability-status available">Available</span>
                                <?php elseif($room['available_rooms'] > 0): ?>
                                    <span class="availability-status limited">Only <?php echo $room['available_rooms']; ?> left</span>
                                <?php else: ?>
                                    <span class="availability-status unavailable">Sold Out</span>
                                <?php endif; ?>
                                
                                <button class="select-btn" onclick="selectRoom(<?php echo $room['id']; ?>, <?php echo $room['price_per_night']; ?>)" <?php echo $room['available_rooms'] <= 0 ? 'disabled' : ''; ?>>
                                    Select
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="reviews-section">
        <div class="container">
            <h2 class="reviews-title">Guest Reviews</h2>
            <div class="reviews-grid">
                <div class="review-card">
                    <div class="review-header">
                        <img src="https://randomuser.me/api/portraits/women/32.jpg" alt="Sarah Johnson" class="reviewer-avatar">
                        <div class="reviewer-info">
                            <h4>Sarah Johnson</h4>
                            <p class="reviewer-location">New York, USA</p>
                        </div>
                    </div>
                    <div class="review-rating">★★★★★</div>
                    <p class="review-text">"I had an amazing stay at this hostel! The location was perfect, the staff was friendly, and the facilities were clean. Would definitely recommend to anyone visiting the area."</p>
                </div>
                
                <div class="review-card">
                    <div class="review-header">
                        <img src="https://randomuser.me/api/portraits/men/45.jpg" alt="David Chen" class="reviewer-avatar">
                        <div class="reviewer-info">
                            <h4>David Chen</h4>
                            <p class="reviewer-location">Toronto, Canada</p>
                        </div>
                    </div>
                    <div class="review-rating">★★★★☆</div>
                    <p class="review-text">"Great value for the price. The common areas were spacious and well-designed. Met some amazing people during my stay. The only downside was the WiFi was a bit slow at times."</p>
                </div>
                
                <div class="review-card">
                    <div class="review-header">
                        <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Emma Rodriguez" class="reviewer-avatar">
                        <div class="reviewer-info">
                            <h4>Emma Rodriguez</h4>
                            <p class="reviewer-location">Barcelona, Spain</p>
                        </div>
                    </div>
                    <div class="review-rating">★★★★★</div>
                    <p class="review-text">"This hostel exceeded my expectations! The breakfast was delicious, the beds were comfortable, and the location was central to all the attractions. I'll definitely be back on my next trip."</p>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>About Hilton Hostel</h3>
                    <p>Hilton Hostel is a leading hostel booking platform offering affordable accommodations worldwide. We connect travelers with unique hostels for unforgettable experiences.</p>
                </div>
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="search.php">Search Hostels</a></li>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">FAQs</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Support</h3>
                    <ul class="footer-links">
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Cancellation Options</a></li>
                        <li><a href="#">Safety Resource Center</a></li>
                        <li><a href="#">Report an Issue</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <p>📧 info@hiltonhostel.com</p>
                    <p>📞 +1 (555) 123-4567</p>
                    <p>🏢 123 Booking Street, New York, NY 10001, USA</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 Hilton Hostel. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Calculate total price
            const roomSelect = document.getElementById('room_id');
            const checkInDate = document.getElementById('check_in_date');
            const checkOutDate = document.getElementById('check_out_date');
            const roomPriceElement = document.getElementById('roomPrice');
            const numNightsElement = document.getElementById('numNights');
            const taxesFeesElement = document.getElementById('taxesFees');
            const totalPriceElement = document.getElementById('totalPrice');
            const totalPriceInput = document.getElementById('totalPriceInput');
            
            function calculatePrice() {
                if(roomSelect.value && checkInDate.value && checkOutDate.value) {
                    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
                    const pricePerNight = parseFloat(selectedOption.getAttribute('data-price'));
                    
                    const checkIn = new Date(checkInDate.value);
                    const checkOut = new Date(checkOutDate.value);
                    
                    // Calculate number of nights
                    const timeDiff = checkOut.getTime() - checkIn.getTime();
                    const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
                    
                    if(nights > 0) {
                        const roomPrice = pricePerNight * nights;
                        const taxesFees = roomPrice * 0.1; // 10% taxes and fees
                        const totalPrice = roomPrice + taxesFees;
                        
                        roomPriceElement.textContent = '$' + roomPrice.toFixed(2);
                        numNightsElement.textContent = nights;
                        taxesFeesElement.textContent = '$' + taxesFees.toFixed(2);
                        totalPriceElement.textContent = '$' + totalPrice.toFixed(2);
                        totalPriceInput.value = totalPrice.toFixed(2);
                    }
                }
            }
            
            roomSelect.addEventListener('change', calculatePrice);
            checkInDate.addEventListener('change', function() {
                // Update check-out min date
                const checkIn = new Date(this.value);
                const nextDay = new Date(checkIn);
                nextDay.setDate(nextDay.getDate() + 1);
                
                const year = nextDay.getFullYear();
                const month = String(nextDay.getMonth() + 1).padStart(2, '0');
                const day = String(nextDay.getDate()).padStart(2, '0');
                
                checkOutDate.min = `${year}-${month}-${day}`;
                
                // If check-out date is before new min date, update it
                if(checkOutDate.value && new Date(checkOutDate.value) <= checkIn) {
                    checkOutDate.value = `${year}-${month}-${day}`;
                }
                
                calculatePrice();
            });
            checkOutDate.addEventListener('change', calculatePrice);
            
            // Select room from room cards
            window.selectRoom = function(roomId, price) {
                roomSelect.value = roomId;
                calculatePrice();
                
                // Scroll to booking form
                document.querySelector('.booking-form').scrollIntoView({ behavior: 'smooth' });
            };
            
            // Set default dates if not provided
            if(!checkInDate.value) {
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                
                checkInDate.value = `${year}-${month}-${day}`;
            }
            
            if(!checkOutDate.value) {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                
                const year = tomorrow.getFullYear();
                const month = String(tomorrow.getMonth() + 1).padStart(2, '0');
                const day = String(tomorrow.getDate()).padStart(2, '0');
                
                checkOutDate.value = `${year}-${month}-${day}`;
            }
            
            // Initial calculation
            calculatePrice();
        });
    </script>
</body>
</html>
