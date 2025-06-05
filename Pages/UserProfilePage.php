<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Fetch user details from the database
try {
    $stmt = $pdo->prepare('
        SELECT username, email, first_name, last_name, bio, website, profile_picture
        FROM users 
        WHERE id = ?
    ');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // If user not found, destroy session and redirect
        session_unset();
        session_destroy();
        header('Location: index.php');
        exit;
    }

    // Set display values, with fallbacks
    $username = $user['username'] ?? $user['email'];
    $display_name = trim($user['first_name'] . ' ' . $user['last_name']);
    $bio = $user['bio'] ?? 'No bio provided';
    $website = $user['website'] ?? '#';
    $profile_picture = $user['profile_picture'] ?? 'https://source.unsplash.com/random/300x300?portrait';
} catch (PDOException $e) {
    // Handle database errors gracefully
    $error = "Error fetching user data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtu Gallery - My Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            color: #333;
            background-color: #f9f9f9;
            overflow-x: hidden;
            padding-top: 90px;
        }

        h1,
        h2,
        h3 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
        }

        /* Navigation Bar */
        nav {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: white;
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid #eee;
            height: 90px;
        }

        .nav-left,
        .nav-right {
            display: flex;
            align-items: center;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: #202b1d;
            font-weight: bold;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .profile-icon,
        .cart-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #202b1d;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            margin-left: 10px;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: #333;
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Main Content Container */
        .content-container {
            display: flex;
            min-height: calc(100vh - 90px);
        }

        /* Artist Suggestions Sidebar */
        .artist-suggestions {
            width: 400px;
            background-color: white;
            padding: 30px;
            border-right: 1px solid #eee;
            overflow-y: auto;
        }

        .back-nav {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }

        .back-nav i {
            margin-right: 10px;
            color: #202b1d;
            font-size: 1.2rem;
        }

        .back-nav h2 {
            font-family: 'Playfair Display', serif;
            color: #202b1d;
            font-size: 1.5rem;
        }

        .suggestions-title {
            font-family: 'Playfair Display', serif;
            color: #202b1d;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .suggestion {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            transition: all 0.3s;
            border: 1px solid #eee;
        }

        .suggestion:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .suggestion-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 1px solid #eee;
        }

        .suggestion-info {
            flex-grow: 1;
        }

        .suggestion-username {
            font-weight: 600;
            color: #202b1d;
            margin-bottom: 3px;
        }

        .suggestion-location {
            font-size: 0.85rem;
            color: #777;
            margin-bottom: 5px;
        }

        .suggestion-bio {
            font-size: 0.8rem;
            color: #555;
            margin-top: 5px;
        }

        .follow-btn {
            background: none;
            border: 1px solid #202b1d;
            color: #202b1d;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.8rem;
            padding: 5px 15px;
            border-radius: 4px;
            transition: all 0.3s;
        }

        .follow-btn:hover {
            background-color: #202b1d;
            color: white;
        }

        .follow-btn.following {
            background-color: #202b1d;
            color: white;
        }

        /* Profile Content */
        .profile-content {
            flex-grow: 1;
            padding: 30px 5%;
            background-color: #f9f9f9;
            overflow-y: auto;
        }

        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
        }

        /* Profile Header */
        .profile-header {
            display: flex;
            padding: 30px 0;
            border-bottom: 1px solid #eee;
        }

        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #eee;
            margin-right: 50px;
        }

        .profile-info {
            flex-grow: 1;
        }

        .profile-top {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .username {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: #202b1d;
            margin-right: 20px;
        }

        .edit-profile-btn {
            padding: 8px 20px;
            background-color: #202b1d;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            margin-right: 10px;
            transition: all 0.3s;
        }

        .edit-profile-btn:hover {
            background-color: #1a2318;
        }

        .settings-btn {
            font-size: 1.3rem;
            color: #202b1d;
            cursor: pointer;
            transition: color 0.3s;
        }

        .settings-btn:hover {
            color: #1a2318;
        }

        .profile-stats {
            display: flex;
            margin-bottom: 20px;
        }

        .stat {
            margin-right: 30px;
            font-size: 1rem;
        }

        .stat-count {
            font-weight: 600;
            color: #202b1d;
        }

        .profile-name {
            font-weight: 600;
            color: #202b1d;
            margin-bottom: 5px;
        }

        .profile-bio {
            margin-bottom: 5px;
            color: #555;
        }

        .profile-website {
            color: #202b1d;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.3s;
        }

        .profile-website:hover {
            color: #1a2318;
            text-decoration: underline;
        }

        /* Gallery Tabs */
        .gallery-tabs {
            display: flex;
            justify-content: center;
            border-bottom: 1px solid #eee;
            margin-top: 20px;
        }

        .tab {
            padding: 15px 0;
            margin: 0 30px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #999;
            cursor: pointer;
            position: relative;
        }

        .tab.active {
            color: #202b1d;
        }

        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #202b1d;
        }

        /* Gallery Grid */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .gallery-item {
            position: relative;
            padding-top: 100%;
            /* Square aspect ratio */
            overflow: hidden;
            cursor: pointer;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .gallery-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .item-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(32, 43, 29, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .gallery-item:hover .item-overlay {
            opacity: 1;
        }

        .item-stats {
            display: flex;
            color: white;
            font-weight: 600;
            gap: 20px;
        }

        .item-stat {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Orders Section */
        .orders-container {
            display: none;
            margin-top: 30px;
        }

        .order-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: #f5f5f5;
            border-bottom: 1px solid #eee;
        }

        .order-date {
            font-weight: 600;
            color: #202b1d;
        }

        .order-status {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-delivered {
            background-color: #e6f7e6;
            color: #2e7d32;
        }

        .status-shipped {
            background-color: #e3f2fd;
            color: #1565c0;
        }

        .order-items {
            padding: 20px;
        }

        .order-item {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .order-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }

        .item-details {
            flex-grow: 1;
        }

        .item-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: #202b1d;
        }

        .item-artist {
            font-size: 0.9rem;
            color: #777;
            margin-bottom: 5px;
        }

        .item-price {
            font-weight: 600;
        }

        .review-btn {
            background-color: #202b1d;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s;
            align-self: center;
        }

        .review-btn:hover {
            background-color: #1a2318;
        }

        .review-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .order-total {
            display: flex;
            justify-content: flex-end;
            padding: 15px 20px;
            background-color: #f9f9f9;
            font-weight: 600;
            border-top: 1px solid #eee;
        }

        /* Review Modal */
        .review-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .review-content {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .review-title {
            font-family: 'Playfair Display', serif;
            color: #202b1d;
            font-size: 1.5rem;
        }

        .close-review {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #777;
        }

        .review-form {
            display: flex;
            flex-direction: column;
        }

        .rating-container {
            margin-bottom: 20px;
        }

        .rating-stars {
            display: flex;
            margin-bottom: 10px;
        }

        .star {
            font-size: 1.5rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
            margin-right: 5px;
        }

        .star:hover,
        .star.active {
            color: #ffc107;
        }

        .review-text {
            width: 100%;
            height: 150px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 20px;
            resize: none;
            font-family: 'Montserrat', sans-serif;
        }

        .submit-review {
            background-color: #202b1d;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .submit-review:hover {
            background-color: #1a2318;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .artist-suggestions {
                width: 300px;
            }
        }

        @media (max-width: 992px) {
            .content-container {
                flex-direction: column;
            }

            .artist-suggestions {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #eee;
                padding: 20px;
            }

            .profile-content {
                padding: 20px;
            }

            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .profile-pic {
                margin-right: 0;
                margin-bottom: 20px;
            }

            .profile-top {
                justify-content: center;
            }

            .profile-stats {
                justify-content: center;
            }

            .gallery-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .order-item {
                flex-direction: column;
            }

            .item-image {
                width: 100%;
                height: auto;
                margin-bottom: 10px;
            }
        }

        @media (max-width: 768px) {
            nav {
                padding: 10px 5%;
            }

            .logo {
                font-size: 1.5rem;
            }

            .mobile-menu-btn {
                display: block;
            }

            .profile-container {
                padding: 20px;
            }

            .gallery-tabs {
                flex-wrap: wrap;
            }

            .tab {
                margin: 0 15px 10px;
            }

            .gallery-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .profile-stats {
                flex-wrap: wrap;
                justify-content: space-around;
            }

            .stat {
                margin: 0 10px 10px;
            }

            .edit-profile-btn {
                padding: 6px 12px;
                font-size: 0.9rem;
            }

            .tab {
                margin: 0 10px 5px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav>
        <div class="nav-left">
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <div class="logo">VirtuGallery</div>
        <div class="nav-right">
            <a href="cart.html" class="cart-icon">
                <i class="fas fa-shopping-cart"></i>
            </a>
            <div class="profile-icon">
                <i class="fas fa-user"></i>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="content-container">
        <!-- Artist Suggestions Sidebar -->
        <div class="artist-suggestions">
            <a class="back-nav" id="back-button">
                <i class="fas fa-arrow-left"></i>
                <h2>Virtu Gallery</h2>
            </a>

            <h3 class="suggestions-title">Artists You Might Like</h3>

            <div class="suggestion">
                <img src="https://source.unsplash.com/random/100x100?artist1" alt="Artist" class="suggestion-avatar">
                <div class="suggestion-info">
                    <div class="suggestion-username">change_artist</div>
                    <div class="suggestion-location">Tokyo, Japan</div>
                    <div class="suggestion-bio">Digital artist specializing in anime-style illustrations</div>
                </div>
                <button class="follow-btn">Follow</button>
            </div>

            <div class="suggestion">
                <img src="https://source.unsplash.com/random/100x100?artist2" alt="Artist" class="suggestion-avatar">
                <div class="suggestion-info">
                    <div class="suggestion-username">millicent_gogh</div>
                    <div class="suggestion-location">Paris, France</div>
                    <div class="suggestion-bio">Contemporary oil painter inspired by nature</div>
                </div>
                <button class="follow-btn">Follow</button>
            </div>

            <div class="suggestion">
                <img src="https://source.unsplash.com/random/100x100?artist3" alt="Artist" class="suggestion-avatar">
                <div class="suggestion-info">
                    <div class="suggestion-username">Karyessa</div>
                    <div class="suggestion-location">Gatanga, Philippines</div>
                    <div class="suggestion-bio">Mixed media artist focusing on Filipino culture</div>
                </div>
                <button class="follow-btn">Follow</button>
            </div>

            <div class="suggestion">
                <img src="https://source.unsplash.com/random/100x100?artist4" alt="Artist" class="suggestion-avatar">
                <div class="suggestion-info">
                    <div class="suggestion-username">hadihasathashadhazi</div>
                    <div class="suggestion-location">Dubai, UAE</div>
                    <div class="suggestion-bio">Abstract expressionist exploring new techniques</div>
                </div>
                <button class="follow-btn">Follow</button>
            </div>

            <div class="suggestion">
                <img src="https://source.unsplash.com/random/100x100?artist5" alt="Artist" class="suggestion-avatar">
                <div class="suggestion-info">
                    <div class="suggestion-username">art_visionary</div>
                    <div class="suggestion-location">New York, USA</div>
                    <div class="suggestion-bio">Street artist blending urban and classical styles</div>
                </div>
                <button class="follow-btn">Follow</button>
            </div>
        </div>

        <!-- Profile Content -->
        <div class="profile-content">
            <div class="profile-container">
                <!-- Profile Header -->
                <div class="profile-header">
                    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="profile-pic">
                    <div class="profile-info">
                        <div class="profile-top">
                            <h1 class="username"><?php echo htmlspecialchars($username); ?></h1>
                            <button class="edit-profile-btn" onclick="location.href='editProfile.php'">Edit Profile</button>
                            <i class="fas fa-cog settings-btn"></i>
                        </div>
                        <div class="profile-stats">
                            <div class="stat">
                                <span class="stat-count">24</span> posts
                            </div>
                            <div class="stat">
                                <span class="stat-count">1.2k</span> followers
                            </div>
                            <div class="stat">
                                <span class="stat-count">356</span> following
                            </div>
                        </div>
                        <h2 class="profile-name"><?php echo htmlspecialchars($display_name); ?></h2>
                        <p class="profile-bio"><?php echo htmlspecialchars($bio); ?></p>
                        <a href="<?php echo htmlspecialchars($website); ?>" class="profile-website"><?php echo htmlspecialchars($website === '#' ? 'No website provided' : $website); ?></a>
                    </div>
                </div>

                <!-- Gallery Tabs -->
                <div class="gallery-tabs">
                    <div class="tab active" data-tab="posts">
                        <i class="fas fa-th"></i> Posts
                    </div>
                    <div class="tab" data-tab="saved">
                        <i class="fas fa-bookmark"></i> Saved
                    </div>
                    <div class="tab" data-tab="shop">
                        <i class="fas fa-shopping-bag"></i> Shop
                    </div>
                    <div class="tab" data-tab="orders">
                        <i class="fas fa-receipt"></i> Orders
                    </div>
                </div>

                <!-- Posts Grid -->
                <div class="gallery-grid" id="posts-tab">
                    <!-- Gallery Item 1 -->
                    <div class="gallery-item">
                        <img src="https://source.unsplash.com/random/600x600?art1" alt="Artwork" class="gallery-img">
                        <div class="item-overlay">
                            <div class="item-stats">
                                <div class="item-stat">
                                    <i class="fas fa-heart"></i> 124
                                </div>
                                <div class="item-stat">
                                    <i class="fas fa-comment"></i> 12
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gallery Item 2 -->
                    <div class="gallery-item">
                        <img src="https://source.unsplash.com/random/600x600?art2" alt="Artwork" class="gallery-img">
                        <div class="item-overlay">
                            <div class="item-stats">
                                <div class="item-stat">
                                    <i class="fas fa-heart"></i> 89
                                </div>
                                <div class="item-stat">
                                    <i class="fas fa-comment"></i> 7
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gallery Item 3 -->
                    <div class="gallery-item">
                        <img src="https://source.unsplash.com/random/600x600?art3" alt="Artwork" class="gallery-img">
                        <div class="item-overlay">
                            <div class="item-stats">
                                <div class="item-stat">
                                    <i class="fas fa-heart"></i> 156
                                </div>
                                <div class="item-stat">
                                    <i class="fas fa-comment"></i> 23
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gallery Item 4 -->
                    <div class="gallery-item">
                        <img src="https://source.unsplash.com/random/600x600?art4" alt="Artwork" class="gallery-img">
                        <div class="item-overlay">
                            <div class="item-stats">
                                <div class="item-stat">
                                    <i class="fas fa-heart"></i> 201
                                </div>
                                <div class="item-stat">
                                    <i class="fas fa-comment"></i> 15
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gallery Item 5 -->
                    <div class="gallery-item">
                        <img src="https://source.unsplash.com/random/600x600?art5" alt="Artwork" class="gallery-img">
                        <div class="item-overlay">
                            <div class="item-stats">
                                <div class="item-stat">
                                    <i class="fas fa-heart"></i> 76
                                </div>
                                <div class="item-stat">
                                    <i class="fas fa-comment"></i> 8
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gallery Item 6 -->
                    <div class="gallery-item">
                        <img src="https://source.unsplash.com/random/600x600?art6" alt="Artwork" class="gallery-img">
                        <div class="item-overlay">
                            <div class="item-stats">
                                <div class="item-stat">
                                    <i class="fas fa-heart"></i> 142
                                </div>
                                <div class="item-stat">
                                    <i class="fas fa-comment"></i> 19
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Section -->
                <div class="orders-container" id="orders-tab">
                    <!-- Order 1 -->
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-date">Order #VG12345 - May 15, 2023</div>
                            <div class="order-status status-delivered">Delivered</div>
                        </div>
                        <div class="order-items">
                            <div class="order-item">
                                <img src="https://source.unsplash.com/random/200x200?painting1" alt="Artwork" class="item-image">
                                <div class="item-details">
                                    <h3 class="item-title">Sunset Over Manila Bay</h3>
                                    <p class="item-artist">by Juan Dela Cruz</p>
                                    <p class="item-price">₱12,500</p>
                                </div>
                                <button class="review-btn" data-artwork="Sunset Over Manila Bay" data-artist="Juan Dela Cruz">Write Review</button>
                            </div>
                            <div class="order-item">
                                <img src="https://source.unsplash.com/random/200x200?painting2" alt="Artwork" class="item-image">
                                <div class="item-details">
                                    <h3 class="item-title">Mountain Dreams</h3>
                                    <p class="item-artist">by Maria Santos</p>
                                    <p class="item-price">₱8,750</p>
                                </div>
                                <button class="review-btn" data-artwork="Mountain Dreams" data-artist="Maria Santos">Write Review</button>
                            </div>
                        </div>
                        <div class="order-total">
                            Total: ₱21,250
                        </div>
                    </div>

                    <!-- Order 2 -->
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-date">Order #VG12098 - April 28, 2023</div>
                            <div class="order-status status-delivered">Delivered</div>
                        </div>
                        <div class="order-items">
                            <div class="order-item">
                                <img src="https://source.unsplash.com/random/200x200?painting3" alt="Artwork" class="item-image">
                                <div class="item-details">
                                    <h3 class="item-title">Urban Rhythm</h3>
                                    <p class="item-artist">by Carlos Reyes</p>
                                    <p class="item-price">₱15,000</p>
                                </div>
                                <button class="review-btn" data-artwork="Urban Rhythm" data-artist="Carlos Reyes">Write Review</button>
                            </div>
                        </div>
                        <div class="order-total">
                            Total: ₱15,000
                        </div>
                    </div>

                    <!-- Order 3 -->
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-date">Order #VG11876 - March 10, 2023</div>
                            <div class="order-status status-shipped">Shipped</div>
                        </div>
                        <div class="order-items">
                            <div class="order-item">
                                <img src="https://source.unsplash.com/random/200x200?painting4" alt="Artwork" class="item-image">
                                <div class="item-details">
                                    <h3 class="item-title">Ocean Whisper</h3>
                                    <p class="item-artist">by Liza Tan</p>
                                    <p class="item-price">₱9,800</p>
                                </div>
                                <button class="review-btn" data-artwork="Ocean Whisper" data-artist="Liza Tan" disabled>Review after delivery</button>
                            </div>
                        </div>
                        <div class="order-total">
                            Total: ₱9,800
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div class="review-modal" id="reviewModal">
        <div class="review-content">
            <div class="review-header">
                <h2 class="review-title">Write a Review</h2>
                <button class="close-review">&times;</button>
            </div>
            <form class="review-form">
                <div class="rating-container">
                    <h3 id="review-artwork-title">Sunset Over Manila Bay</h3>
                    <p id="review-artist-name">by Juan Dela Cruz</p>
                    <div class="rating-stars">
                        <span class="star" data-rating="1">&#9733;</span>
                        <span class="star" data-rating="2">&#9733;</span>
                        <span class="star" data-rating="3">&#9733;</span>
                        <span class="star" data-rating="4">&#9733;</span>
                        <span class="star" data-rating="5">&#9733;</span>
                    </div>
                    <input type="hidden" id="rating-value" value="0">
                </div>
                <textarea class="review-text" placeholder="Share your thoughts about this artwork..."></textarea>
                <button type="submit" class="submit-review">Submit Review</button>
            </form>
        </div>
    </div>

    <script>
        // Tab Switching
        const tabs = document.querySelectorAll('.tab');
        const postsTab = document.getElementById('posts-tab');
        const ordersTab = document.getElementById('orders-tab');

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                const tabName = this.getAttribute('data-tab');

                // Hide all content sections
                postsTab.style.display = 'none';
                ordersTab.style.display = 'none';

                // Show the selected content section
                if (tabName === 'posts') {
                    postsTab.style.display = 'grid';
                } else if (tabName === 'orders') {
                    ordersTab.style.display = 'block';
                } else {
                    // For other tabs (saved, shop)
                    alert(tabName + ' content would load here');
                }
            });
        });

        // Initialize with posts tab showing
        postsTab.style.display = 'grid';
        ordersTab.style.display = 'none';

        // Edit Profile Button
        document.querySelector(".edit-profile-btn").addEventListener("click", function() {
            window.location.href = "editProfile.php";
        });

        // Settings Button
        const settingsBtn = document.querySelector('.settings-btn');

        settingsBtn.addEventListener('click', function() {
            // In a real app, this would open settings
            alert('Settings would appear here');
        });

        // Follow Buttons
        const followBtns = document.querySelectorAll('.follow-btn');

        followBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                if (this.textContent === 'Follow') {
                    this.textContent = 'Following';
                    this.classList.add('following');
                } else {
                    this.textContent = 'Follow';
                    this.classList.remove('following');
                }
            });
        });

        // Back Button
        const backButton = document.getElementById('back-button');
        backButton.addEventListener('click', function() {
            window.location.href = '../Pages/home.php';
        });

        // Mobile Menu Toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        mobileMenuBtn.addEventListener('click', function() {
            const icon = this.querySelector('i');
            if (icon.classList.contains('fa-bars')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });

        // Navbar scroll effect
        const navbar = document.querySelector('nav');
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.style.backgroundColor = '#202b1d';
                navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.2)';
            } else {
                navbar.style.backgroundColor = 'white';
                navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
            }
        });

        // Review Modal Functionality
        const reviewModal = document.getElementById('reviewModal');
        const reviewBtns = document.querySelectorAll('.review-btn');
        const closeReview = document.querySelector('.close-review');
        const stars = document.querySelectorAll('.star');
        const ratingValue = document.getElementById('rating-value');
        const reviewArtworkTitle = document.getElementById('review-artwork-title');
        const reviewArtistName = document.getElementById('review-artist-name');
        const reviewForm = document.querySelector('.review-form');

        reviewBtns.forEach(btn => {
            if (!btn.disabled) {
                btn.addEventListener('click', function() {
                    const artwork = this.getAttribute('data-artwork');
                    const artist = this.getAttribute('data-artist');

                    reviewArtworkTitle.textContent = artwork;
                    reviewArtistName.textContent = 'by ' + artist;

                    reviewModal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                });
            }
        });

        closeReview.addEventListener('click', function() {
            reviewModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                ratingValue.value = rating;

                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });

        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const rating = ratingValue.value;
            const reviewText = document.querySelector('.review-text').value;

            if (rating == 0) {
                alert('Please select a rating');
                return;
            }

            // In a real app, this would submit to a server
            alert(`Thank you for your ${rating}-star review!\n\n"${reviewText}"`);

            // Reset form and close modal
            ratingValue.value = 0;
            document.querySelector('.review-text').value = '';
            stars.forEach(star => star.classList.remove('active'));
            reviewModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target === reviewModal) {
                reviewModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    </script>
</body>

</html>