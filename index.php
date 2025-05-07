<?php
session_start();
include 'db.php';

// Fetch featured hostels
$stmt = $conn->prepare("SELECT * FROM hostels ORDER BY rating DESC LIMIT 4");
$stmt->execute();
$featured_hostels = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hilton Hostel - Your Home Away From Home</title>
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
        
        .hero {
            background-image: linear-gradient(rgba(0, 53, 128, 0.7), rgba(0, 53, 128, 0.7)), url('https://images.unsplash.com/photo-1566073771259-6a8506099945');
            background-size: cover;
            background-position: center;
            height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 0 20px;
        }
        
        .hero-content {
            max-width: 800px;
        }
        
        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .hero p {
            font-size: 18px;
            margin-bottom: 30px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }
        
        .search-box {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-top: -50px;
            position: relative;
            z-index: 10;
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
            margin-top: 24px;
        }
        
        .search-btn:hover {
            background-color: #002a66;
        }
        
        .section {
            padding: 80px 0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 50px;
            color: #003580;
            font-size: 32px;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background-color: #f5c710;
            margin: 15px auto 0;
        }
        
        .featured-hostels {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }
        
        .hostel-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .hostel-card:hover {
            transform: translateY(-10px);
        }
        
        .hostel-img {
            height: 200px;
            width: 100%;
            object-fit: cover;
        }
        
        .hostel-details {
            padding: 20px;
        }
        
        .hostel-name {
            font-size: 20px;
            margin-bottom: 10px;
            color: #003580;
        }
        
        .hostel-location {
            color: #666;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .hostel-location::before {
            content: '📍';
            margin-right: 5px;
        }
        
        .hostel-rating {
            display: inline-block;
            background-color: #003580;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .hostel-price {
            font-size: 18px;
            font-weight: 600;
            color: #003580;
            margin-bottom: 15px;
        }
        
        .hostel-price span {
            font-size: 14px;
            color: #666;
            font-weight: normal;
        }
        
        .view-btn {
            display: inline-block;
            background-color: #f5c710;
            color: #003580;
            padding: 8px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .view-btn:hover {
            background-color: #e0b60d;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            font-size: 48px;
            color: #003580;
            margin-bottom: 20px;
        }
        
        .feature-title {
            font-size: 20px;
            margin-bottom: 15px;
            color: #003580;
        }
        
        .testimonials {
            background-color: #003580;
            color: white;
            padding: 80px 0;
        }
        
        .testimonials .section-title {
            color: white;
        }
        
        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .testimonial-card {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 8px;
            position: relative;
        }
        
        .testimonial-text {
            font-style: italic;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
        }
        
        .author-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
        }
        
        .author-name {
            font-weight: 600;
        }
        
        .author-location {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .cta {
            background-image: linear-gradient(rgba(0, 53, 128, 0.9), rgba(0, 53, 128, 0.9)), url('https://images.unsplash.com/photo-1520250497591-112f2f40a3f4');
            background-size: cover;
            background-position: center;
            padding: 100px 0;
            text-align: center;
            color: white;
        }
        
        .cta h2 {
            font-size: 36px;
            margin-bottom: 20px;
        }
        
        .cta p {
            font-size: 18px;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-btn {
            display: inline-block;
            background-color: #f5c710;
            color: #003580;
            padding: 12px 30px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            font-size: 18px;
            transition: all 0.3s;
        }
        
        .cta-btn:hover {
            background-color: #e0b60d;
            transform: translateY(-3px);
        }
        
        footer {
            background-color: #002a66;
            color: white;
            padding: 60px 0 20px;
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
        
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            text-align: center;
            line-height: 40px;
            transition: background-color 0.3s;
        }
        
        .social-links a:hover {
            background-color: #f5c710;
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
            
            .hero h1 {
                font-size: 36px;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-btn {
                width: 100%;
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

    <section class="hero">
        <div class="hero-content">
            <h1>Find Your Perfect Hostel</h1>
            <p>Discover amazing hostels around the world at unbeatable prices</p>
        </div>
    </section>

    <div class="container">
        <div class="search-box">
            <form action="search.php" method="GET" class="search-form">
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" placeholder="Where are you going?">
                </div>
                <div class="form-group">
                    <label for="check-in">Check In</label>
                    <input type="date" id="check-in" name="check_in" min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="check-out">Check Out</label>
                    <input type="date" id="check-out" name="check_out" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
                <div class="form-group">
                    <label for="guests">Guests</label>
                    <select id="guests" name="guests">
                        <option value="1">1 Guest</option>
                        <option value="2">2 Guests</option>
                        <option value="3">3 Guests</option>
                        <option value="4">4 Guests</option>
                        <option value="5">5+ Guests</option>
                    </select>
                </div>
                <button type="submit" class="search-btn">Search Hostels</button>
            </form>
        </div>
    </div>

    <section class="section">
        <div class="container">
            <h2 class="section-title">Featured Hostels</h2>
            <div class="featured-hostels">
                <?php foreach($featured_hostels as $hostel): ?>
                    <div class="hostel-card">
                        <img src="<?php echo $hostel['image_url']; ?>" alt="<?php echo $hostel['name']; ?>" class="hostel-img">
                        <div class="hostel-details">
                            <h3 class="hostel-name"><?php echo $hostel['name']; ?></h3>
                            <p class="hostel-location"><?php echo $hostel['location']; ?></p>
                            <div class="hostel-rating"><?php echo $hostel['rating']; ?>/5</div>
                            <p class="hostel-price">From $29.99 <span>per night</span></p>
                            <a href="booking.php?hostel_id=<?php echo $hostel['id']; ?>" class="view-btn">View Rooms</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2 class="section-title">Why Choose Hilton Hostel?</h2>
            <div class="features">
                <div class="feature-card">
                    <div class="feature-icon">🏆</div>
                    <h3 class="feature-title">Best Prices Guaranteed</h3>
                    <p>We offer the best prices for hostels worldwide. If you find a better price, we'll match it!</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3 class="feature-title">Secure Booking</h3>
                    <p>Book with confidence knowing your personal information is protected with our secure booking system.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⭐</div>
                    <h3 class="feature-title">Verified Reviews</h3>
                    <p>All our reviews are from real guests who have stayed at our hostels, ensuring authentic feedback.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🌍</div>
                    <h3 class="feature-title">Global Network</h3>
                    <p>With hostels in over 100 countries, you'll find the perfect place to stay wherever you go.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="testimonials">
        <div class="container">
            <h2 class="section-title">What Our Guests Say</h2>
            <div class="testimonial-grid">
                <div class="testimonial-card">
                    <p class="testimonial-text">"I've stayed at Hilton Hostels in three different countries and each experience was amazing. Clean facilities, friendly staff, and great locations!"</p>
                    <div class="testimonial-author">
                        <img src="https://randomuser.me/api/portraits/women/32.jpg" alt="Sarah Johnson" class="author-img">
                        <div>
                            <div class="author-name">Sarah Johnson</div>
                            <div class="author-location">New York, USA</div>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <p class="testimonial-text">"The best hostel experience I've ever had. Met so many amazing people and the staff went above and beyond to make my stay comfortable."</p>
                    <div class="testimonial-author">
                        <img src="https://randomuser.me/api/portraits/men/45.jpg" alt="David Chen" class="author-img">
                        <div>
                            <div class="author-name">David Chen</div>
                            <div class="author-location">Toronto, Canada</div>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <p class="testimonial-text">"Affordable luxury! The amenities and services at Hilton Hostel were comparable to hotels twice the price. Will definitely stay again."</p>
                    <div class="testimonial-author">
                        <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Emma Rodriguez" class="author-img">
                        <div>
                            <div class="author-name">Emma Rodriguez</div>
                            <div class="author-location">Barcelona, Spain</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <h2>Ready for Your Next Adventure?</h2>
            <p>Join thousands of happy travelers who have found their perfect hostel with us. Sign up today and get exclusive deals!</p>
            <a href="signup.php" class="cta-btn">Sign Up Now</a>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>About Hilton Hostel</h3>
                    <p>Hilton Hostel is a leading hostel booking platform offering affordable accommodations worldwide. We connect travelers with unique hostels for unforgettable experiences.</p>
                    <div class="social-links">
                        <a href="#">📱</a>
                        <a href="#">📘</a>
                        <a href="#">📸</a>
                        <a href="#">🐦</a>
                    </div>
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
        // Set minimum dates for check-in and check-out
        document.addEventListener('DOMContentLoaded', function() {
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
        
        // Smooth scroll for navigation links
        document.querySelectorAll('nav a').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href.startsWith('#')) {
                    e.preventDefault();
                    const targetElement = document.querySelector(href);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 100,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });
    </script>
</body>
</html>
