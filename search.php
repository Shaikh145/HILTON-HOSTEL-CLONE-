<?php
session_start();
include 'db.php';

// Get search parameters
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$check_in = isset($_GET['check_in']) ? $_GET['check_in'] : '';
$check_out = isset($_GET['check_out']) ? $_GET['check_out'] : '';
$guests = isset($_GET['guests']) ? intval($_GET['guests']) : 1;
$min_price = isset($_GET['min_price']) ? intval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? intval($_GET['max_price']) : 1000;
$rating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;

// Build query
$query = "SELECT h.*, MIN(r.price_per_night) as min_price FROM hostels h JOIN rooms r ON h.id = r.hostel_id WHERE 1=1";
$params = [];

if(!empty($location)) {
    $query .= " AND h.location LIKE ?";
    $params[] = "%$location%";
}

if($rating > 0) {
    $query .= " AND h.rating >= ?";
    $params[] = $rating;
}

$query .= " AND r.price_per_night BETWEEN ? AND ?";
$params[] = $min_price;
$params[] = $max_price;

if($guests > 0) {
    $query .= " AND r.capacity >= ?";
    $params[] = $guests;
}

$query .= " GROUP BY h.id ORDER BY h.rating DESC";

// Execute query
$stmt = $conn->prepare($query);
$stmt->execute($params);
$hostels = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Hostels - Hilton Hostel</title>
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
        
        .search-section {
            background-color: #003580;
            padding: 30px 0;
            color: white;
        }
        
        .search-container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .form-group {
            flex: 1 1 200px;
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
        
        .search-btn {
            background-color: #003580;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .search-btn:hover {
            background-color: #002a66;
        }
        
        .results-section {
            padding: 40px 0;
        }
        
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .results-header h2 {
            font-size: 24px;
            color: #003580;
        }
        
        .results-count {
            color: #666;
        }
        
        .sort-options {
            display: flex;
            align-items: center;
        }
        
        .sort-options label {
            margin-right: 10px;
            font-weight: 600;
            color: #003580;
        }
        
        .sort-options select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .results-grid {
            display: grid;
            grid-template-columns: 1fr 3fr;
            gap: 30px;
        }
        
        .filters {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            position: sticky;
            top: 100px;
            height: fit-content;
        }
        
        .filter-section {
            margin-bottom: 25px;
        }
        
        .filter-section h3 {
            font-size: 18px;
            color: #003580;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .price-range {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .price-input {
            flex: 1;
        }
        
        .price-input input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .rating-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .rating-option {
            display: flex;
            align-items: center;
        }
        
        .rating-option input {
            margin-right: 10px;
        }
        
        .rating-stars {
            color: #f5c710;
        }
        
        .amenities-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .amenity-option {
            display: flex;
            align-items: center;
        }
        
        .amenity-option input {
            margin-right: 10px;
        }
        
        .filter-btn {
            width: 100%;
            background-color: #003580;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }
        
        .filter-btn:hover {
            background-color: #002a66;
        }
        
        .reset-btn {
            width: 100%;
            background-color: white;
            color: #003580;
            border: 1px solid #003580;
            padding: 10px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .reset-btn:hover {
            background-color: #f8f9fa;
        }
        
        .hostel-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .hostel-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 2fr;
        }
        
        .hostel-image {
            height: 100%;
            overflow: hidden;
        }
        
        .hostel-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .hostel-image img:hover {
            transform: scale(1.05);
        }
        
        .hostel-details {
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        
        .hostel-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .hostel-name {
            font-size: 22px;
            color: #003580;
            margin-bottom: 5px;
        }
        
        .hostel-location {
            color: #666;
            display: flex;
            align-items: center;
        }
        
        .hostel-location::before {
            content: '📍';
            margin-right: 5px;
        }
        
        .hostel-rating {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }
        
        .rating-score {
            background-color: #003580;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 600;
        }
        
        .rating-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .hostel-amenities {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .amenity {
            font-size: 14px;
            color: #666;
            display: flex;
            align-items: center;
        }
        
        .amenity::before {
            margin-right: 5px;
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
        
        .hostel-description {
            color: #666;
            margin-bottom: 15px;
            flex-grow: 1;
        }
        
        .hostel-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }
        
        .hostel-price {
            font-size: 22px;
            font-weight: 600;
            color: #003580;
        }
        
        .hostel-price span {
            font-size: 14px;
            color: #666;
            font-weight: normal;
        }
        
        .view-rooms-btn {
            display: inline-block;
            background-color: #f5c710;
            color: #003580;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .view-rooms-btn:hover {
            background-color: #e0b60d;
        }
        
        .no-results {
            text-align: center;
            padding: 50px 0;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .no-results h3 {
            font-size: 24px;
            color: #003580;
            margin-bottom: 15px;
        }
        
        .no-results p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .no-results a {
            display: inline-block;
            background-color: #003580;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .no-results a:hover {
            background-color: #002a66;
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
            
            .results-grid {
                grid-template-columns: 1fr;
            }
            
            .hostel-card {
                grid-template-columns: 1fr;
            }
            
            .hostel-image {
                height: 200px;
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

    <section class="search-section">
        <div class="container">
            <div class="search-container">
                <form action="search.php" method="GET" class="search-form">
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" placeholder="Where are you going?" value="<?php echo htmlspecialchars($location); ?>">
                    </div>
                    <div class="form-group">
                        <label for="check-in">Check In</label>
                        <input type="date" id="check-in" name="check_in" min="<?php echo date('Y-m-d'); ?>" value="<?php echo $check_in; ?>">
                    </div>
                    <div class="form-group">
                        <label for="check-out">Check Out</label>
                        <input type="date" id="check-out" name="check_out" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" value="<?php echo $check_out; ?>">
                    </div>
                    <div class="form-group">
                        <label for="guests">Guests</label>
                        <select id="guests" name="guests">
                            <option value="1" <?php echo $guests == 1 ? 'selected' : ''; ?>>1 Guest</option>
                            <option value="2" <?php echo $guests == 2 ? 'selected' : ''; ?>>2 Guests</option>
                            <option value="3" <?php echo $guests == 3 ? 'selected' : ''; ?>>3 Guests</option>
                            <option value="4" <?php echo $guests == 4 ? 'selected' : ''; ?>>4 Guests</option>
                            <option value="5" <?php echo $guests == 5 ? 'selected' : ''; ?>>5+ Guests</option>
                        </select>
                    </div>
                    <button type="submit" class="search-btn">Search Hostels</button>
                </form>
            </div>
        </div>
    </section>

    <section class="results-section">
        <div class="container">
            <div class="results-header">
                <div>
                    <h2>Search Results</h2>
                    <p class="results-count"><?php echo count($hostels); ?> hostels found</p>
                </div>
                <div class="sort-options">
                    <label for="sort">Sort by:</label>
                    <select id="sort" name="sort">
                        <option value="rating">Rating (High to Low)</option>
                        <option value="price_low">Price (Low to High)</option>
                        <option value="price_high">Price (High to Low)</option>
                        <option value="name">Name (A to Z)</option>
                    </select>
                </div>
            </div>
            
            <div class="results-grid">
                <div class="filters">
                    <form action="search.php" method="GET" id="filterForm">
                        <input type="hidden" name="location" value="<?php echo htmlspecialchars($location); ?>">
                        <input type="hidden" name="check_in" value="<?php echo $check_in; ?>">
                        <input type="hidden" name="check_out" value="<?php echo $check_out; ?>">
                        <input type="hidden" name="guests" value="<?php echo $guests; ?>">
                        
                        <div class="filter-section">
                            <h3>Price Range</h3>
                            <div class="price-range">
                                <div class="price-input">
                                    <label for="min_price">Min ($)</label>
                                    <input type="number" id="min_price" name="min_price" min="0" max="1000" value="<?php echo $min_price; ?>">
                                </div>
                                <div class="price-input">
                                    <label for="max_price">Max ($)</label>
                                    <input type="number" id="max_price" name="max_price" min="0" max="1000" value="<?php echo $max_price; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="filter-section">
                            <h3>Rating</h3>
                            <div class="rating-options">
                                <label class="rating-option">
                                    <input type="radio" name="rating" value="0" <?php echo $rating == 0 ? 'checked' : ''; ?>>
                                    Any Rating
                                </label>
                                <label class="rating-option">
                                    <input type="radio" name="rating" value="4" <?php echo $rating == 4 ? 'checked' : ''; ?>>
                                    <span class="rating-stars">★★★★</span> & up
                                </label>
                                <label class="rating-option">
                                    <input type="radio" name="rating" value="3" <?php echo $rating == 3 ? 'checked' : ''; ?>>
                                    <span class="rating-stars">★★★</span> & up
                                </label>
                                <label class="rating-option">
                                    <input type="radio" name="rating" value="2" <?php echo $rating == 2 ? 'checked' : ''; ?>>
                                    <span class="rating-stars">★★</span> & up
                                </label>
                            </div>
                        </div>
                        
                        <div class="filter-section">
                            <h3>Amenities</h3>
                            <div class="amenities-options">
                                <label class="amenity-option">
                                    <input type="checkbox" name="amenities[]" value="wifi">
                                    Free WiFi
                                </label>
                                <label class="amenity-option">
                                    <input type="checkbox" name="amenities[]" value="breakfast">
                                    Breakfast Included
                                </label>
                                <label class="amenity-option">
                                    <input type="checkbox" name="amenities[]" value="pool">
                                    Swimming Pool
                                </label>
                                <label class="amenity-option">
                                    <input type="checkbox" name="amenities[]" value="parking">
                                    Free Parking
                                </label>
                                <label class="amenity-option">
                                    <input type="checkbox" name="amenities[]" value="gym">
                                    Fitness Center
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="filter-btn">Apply Filters</button>
                        <button type="button" class="reset-btn" id="resetBtn">Reset Filters</button>
                    </form>
                </div>
                
                <div class="hostel-results">
                    <?php if(count($hostels) > 0): ?>
                        <div class="hostel-list">
                            <?php foreach($hostels as $hostel): ?>
                                <div class="hostel-card">
                                    <div class="hostel-image">
                                        <img src="<?php echo $hostel['image_url']; ?>" alt="<?php echo $hostel['name']; ?>">
                                    </div>
                                    <div class="hostel-details">
                                        <div class="hostel-header">
                                            <div>
                                                <h3 class="hostel-name"><?php echo $hostel['name']; ?></h3>
                                                <p class="hostel-location"><?php echo $hostel['location']; ?></p>
                                            </div>
                                            <div class="hostel-rating">
                                                <div class="rating-score"><?php echo $hostel['rating']; ?></div>
                                                <div class="rating-text">
                                                    <?php
                                                        if($hostel['rating'] >= 4.5) echo "Exceptional";
                                                        elseif($hostel['rating'] >= 4) echo "Excellent";
                                                        elseif($hostel['rating'] >= 3.5) echo "Very Good";
                                                        elseif($hostel['rating'] >= 3) echo "Good";
                                                        else echo "Average";
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="hostel-amenities">
                                            <?php
                                                $amenities = explode(',', $hostel['amenities']);
                                                if(strpos($hostel['amenities'], 'WiFi') !== false): ?>
                                                    <div class="amenity wifi">Free WiFi</div>
                                                <?php endif; ?>
                                                
                                                <?php if(strpos($hostel['amenities'], 'Breakfast') !== false): ?>
                                                    <div class="amenity breakfast">Breakfast</div>
                                                <?php endif; ?>
                                                
                                                <?php if(strpos($hostel['amenities'], 'Pool') !== false): ?>
                                                    <div class="amenity pool">Pool</div>
                                                <?php endif; ?>
                                        </div>
                                        
                                        <p class="hostel-description"><?php echo substr($hostel['description'], 0, 150); ?>...</p>
                                        
                                        <div class="hostel-footer">
                                            <div class="hostel-price">
                                                From $<?php echo number_format($hostel['min_price'], 2); ?> <span>per night</span>
                                            </div>
                                            <a href="booking.php?hostel_id=<?php echo $hostel['id']; ?><?php echo !empty($check_in) ? '&check_in='.$check_in : ''; ?><?php echo !empty($check_out) ? '&check_out='.$check_out : ''; ?><?php echo $guests > 0 ? '&guests='.$guests : ''; ?>" class="view-rooms-btn">View Rooms</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-results">
                            <h3>No Hostels Found</h3>
                            <p>We couldn't find any hostels matching your search criteria. Try adjusting your filters or search for a different location.</p>
                            <a href="index.php">Back to Home</a>
                        </div>
                    <?php endif; ?>
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
            // Sort hostels
            const sortSelect = document.getElementById('sort');
            const hostelList = document.querySelector('.hostel-list');
            const hostelCards = Array.from(document.querySelectorAll('.hostel-card'));
            
            sortSelect.addEventListener('change', function() {
                const sortValue = this.value;
                
                hostelCards.sort((a, b) => {
                    if(sortValue === 'rating') {
                        const ratingA = parseFloat(a.querySelector('.rating-score').textContent);
                        const ratingB = parseFloat(b.querySelector('.rating-score').textContent);
                        return ratingB - ratingA;
                    } else if(sortValue === 'price_low') {
                        const priceA = parseFloat(a.querySelector('.hostel-price').textContent.replace(/[^0-9.]/g, ''));
                        const priceB = parseFloat(b.querySelector('.hostel-price').textContent.replace(/[^0-9.]/g, ''));
                        return priceA - priceB;
                    } else if(sortValue === 'price_high') {
                        const priceA = parseFloat(a.querySelector('.hostel-price').textContent.replace(/[^0-9.]/g, ''));
                        const priceB = parseFloat(b.querySelector('.hostel-price').textContent.replace(/[^0-9.]/g, ''));
                        return priceB - priceA;
                    } else if(sortValue === 'name') {
                        const nameA = a.querySelector('.hostel-name').textContent;
                        const nameB = b.querySelector('.hostel-name').textContent;
                        return nameA.localeCompare(nameB);
                    }
                });
                
                // Remove all cards
                hostelCards.forEach(card => card.remove());
                
                // Append sorted cards
                hostelCards.forEach(card => hostelList.appendChild(card));
            });
            
            // Reset filters
            const resetBtn = document.getElementById('resetBtn');
            
            resetBtn.addEventListener('click', function() {
                document.getElementById('min_price').value = 0;
                document.getElementById('max_price').value = 1000;
                document.querySelector('input[name="rating"][value="0"]').checked = true;
                
                const amenityCheckboxes = document.querySelectorAll('input[name="amenities[]"]');
                amenityCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                
                document.getElementById('filterForm').submit();
            });
            
            // Set minimum dates for check-in and check-out
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            const checkInInput = document.getElementById('check-in');
            const checkOutInput = document.getElementById('check-out');
            
            // Format dates for input fields
            const formatDate = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };
            
            // Set min attributes
            checkInInput.min = formatDate(today);
            checkOutInput.min = formatDate(tomorrow);
            
            // Update check-out min date when check-in changes
            checkInInput.addEventListener('change', function() {
                const newMinDate = new Date(this.value);
                newMinDate.setDate(newMinDate.getDate() + 1);
                checkOutInput.min = formatDate(newMinDate);
                
                // If current check-out date is before new min date, update it
                if (new Date(checkOutInput.value) <= new Date(this.value)) {
                    checkOutInput.value = formatDate(newMinDate);
                }
            });
        });
    </script>
</body>
</html>
