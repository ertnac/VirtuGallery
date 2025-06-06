﻿<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT username, email FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        session_unset();
        session_destroy();
        header('Location: ../index.php');
        exit;
    }

    $username = $user['username'] ?? $_SESSION['email'];
    $user_id = $_SESSION['user_id'];

    // Fetch posts with like count and user like status
    $stmt = $pdo->prepare('
        SELECT 
            p.id, 
            p.title, 
            p.description, 
            p.category, 
            p.post_type, 
            p.price, 
            p.tags, 
            p.image_path, 
            p.created_at, 
            u.username,
            (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS likes,
            EXISTS (SELECT 1 FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) AS is_liked,
            (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comments
        FROM posts p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC
    ');
    $stmt->execute([$user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtu Gallery</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;600&family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --primary-color: #202b1d;
            --secondary-color: #1a2318;
            --text-color: #333;
            --accent-color: #ecd5a6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            overflow: hidden;
            font-family: 'Montserrat', sans-serif;
            color: #333;
            line-height: 1.6;
            background-color: #f9f9f9;
        }

        h1,
        h2,
        h3 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* Navigation Bar Styles */
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
            transition: background-color 0.3s ease, padding 0.3s ease;
            height: 90px;
        }

        .nav-left,
        .nav-right {
            display: flex;
            align-items: center;
        }

        nav.scrolled {
            background-color: var(--primary-color);
            padding: 10px 5%;
        }

        nav.scrolled .logo,
        nav.scrolled .nav-links a,
        nav.scrolled .profile-icon {
            color: white;
        }

        nav.scrolled .nav-links a:hover {
            color: #ecd5a6;
        }

        nav.scrolled .profile-icon:hover {
            box-shadow: 0 0 8px rgba(255, 255, 255, 0.5);
        }

        nav.scrolled .profile-menu a:hover {
            color: #ecd5a6;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: #202b1d;
            font-weight: bold;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            transition: color 0.3s ease;
        }

        nav.scrolled .logo {
            color: white;
        }

        .nav-links {
            display: flex;
            list-style: none;
        }

        .nav-links li {
            margin-left: 20px;
            position: relative;
        }

        .nav-links a {
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
            position: relative;
        }

        .nav-links a:hover {
            color: #202b1d;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: #000000;
            transition: width 0.3s;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        nav.scrolled .nav-links a::after {
            background-color: #ecd5a6;
        }

        .profile-container {
            position: relative;
            margin-left: 10px;
        }

        .profile-icon {
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
            transition: all 0.3s ease;
        }

        .profile-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.2);
        }

        .profile-menu {
            position: absolute;
            top: 50px;
            right: 0;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: opacity 0.3s ease, transform 0.3s ease, visibility 0.3s;
            z-index: 1000;
            min-width: 180px;
        }

        .profile-container:hover .profile-menu,
        .profile-menu.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-menu a {
            display: block;
            padding: 12px 20px;
            color: #333;
            font-weight: 500;
            transition: background-color 0.3s, color 0.3s;
        }

        .profile-menu a:hover {
            background-color: #f9f9f9;
            color: #202b1d;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: #333;
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.3s;
        }

        nav.scrolled .mobile-menu-btn {
            color: white;
        }

        @media (max-width: 768px) {
            nav {
                padding: 10px 5%;
            }

            .logo {
                font-size: 1.5rem;
            }

            .nav-links {
                position: fixed;
                top: 60px;
                left: 0;
                width: 100%;
                background-color: #202b1d;
                flex-direction: column;
                align-items: center;
                padding: 20px 0;
                transform: translateY(-100%);
                opacity: 0;
                transition: all 0.3s;
                pointer-events: none;
            }

            .nav-links.active {
                transform: translateY(0);
                opacity: 1;
                pointer-events: all;
            }

            .nav-links li {
                margin: 15px 0;
            }

            .nav-links a {
                color: white;
            }

            .nav-links a:hover {
                color: #ecd5a6;
            }

            .mobile-menu-btn {
                display: block;
            }

            .profile-menu {
                top: 60px;
                right: 5%;
                min-width: 150px;
            }
        }

        /* Main Content Styles */
        .main-content {
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .content-wrapper {
            flex: 1;
            padding: 90px 5% 0;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            background-color: #f9f9f9;
            transition: background-color 0.5s ease;
            overflow: hidden;
            height: calc(100vh - 90px);
        }

        .parent {
            flex: 1;
            gap: 30px;
            height: 100%;
            overflow: hidden;
        }

        .content-container {
            display: grid;
            grid-template-columns: 20% 80%;
            grid-template-rows: auto 1fr;
            gap: 15px;
            height: 100%;
            width: 100%;
            box-sizing: border-box;
            padding: 20px;
            overflow: hidden;
        }

        .searchtab,
        .sidebartab,
        .post-container {
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s ease;
            background-color: white;
        }

        .searchtab {
            grid-column: 1 / -1;
            background-color: #f5f5f5;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px 25px;
            height: auto;
            min-height: 80px;
            display: flex;
            align-items: center;
        }

        .sidebartab {
            background-color: #f5f5f5;
            border: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .sidebarbuttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 10px;
            height: 100%;
            overflow-y: auto;
        }

        .sidebarbuttons a {
            background-color: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 15px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 500;
            color: #333;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
            text-decoration: none;
        }

        .sidebarbuttons a:hover {
            background-color: #f5f5f5;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
            border-color: #202b1d;
        }

        .sidebarbuttons a i {
            font-size: 1.1rem;
            color: #202b1d;
            width: 24px;
            text-align: center;
        }

        .sidebarbuttons a.active {
            background-color: var(--primary-color);
            color: white;
        }

        .sidebarbuttons a.active i {
            color: white;
        }

        .AddWork {
            background-color: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
        }

        .AddWork:hover {
            background-color: #f5f5f5;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
            border-color: #202b1d;
        }

        .add-work-container {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.7);
        }

        .add-work-container:hover {
            border-color: #202b1d;
            background-color: rgba(255, 255, 255, 0.9);
        }

        .add-work-placeholder {
            color: #666;
            font-size: 1rem;
            margin-bottom: 15px;
            text-align: center;
        }

        .add-image-btn {
            background-color: #202b1d;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .add-image-btn:hover {
            background-color: #1a2318;
            transform: scale(1.1);
        }

        /* Post Container and Feed Styles */
        .post-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            padding: 20px;
            overflow-y: auto;
            height: 100%;
            scrollbar-width: thin;
            scrollbar-color: #888 #f1f1f1;
        }

        .feed-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 0;
            overflow: hidden;
            width: 100%;
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 600px;
        }

        .feed-header {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .feed-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }

        .feed-user-info {
            flex-grow: 1;
        }

        .feed-username {
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }

        .feed-location {
            font-size: 0.75rem;
            color: #888;
        }

        .feed-image-container {
            position: relative;
            width: 100%;
            padding-top: 100%;
            overflow: hidden;
        }

        .feed-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .feed-actions {
            display: flex;
            justify-content: space-between;
            padding: 8px 12px;
        }

        .feed-action-left {
            display: flex;
            gap: 12px;
        }

        .feed-action-btn {
            font-size: 1.2rem;
            color: #333;
            background: none;
            border: none;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .feed-action-btn:hover {
            transform: scale(1.1);
        }

        .feed-action-btn.liked {
            color: #ed4956;
        }

        .feed-action-btn.bookmarked {
            color: #202b1d;
        }

        .feed-likes {
            padding: 0 12px 5px;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .feed-caption {
            padding: 0 12px 8px;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        .feed-caption-username {
            font-weight: 600;
            margin-right: 4px;
        }

        .feed-comments {
            padding: 0 12px 5px;
            color: #888;
            font-size: 0.8rem;
            margin-bottom: 5px;
            cursor: pointer;
        }

        .feed-time {
            padding: 0 12px 10px;
            color: #888;
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        .feed-comment-box {
            display: flex;
            border-top: 1px solid #eee;
            padding: 10px;
            margin-top: auto;
        }

        .feed-comment-input {
            flex-grow: 1;
            border: none;
            outline: none;
            font-size: 0.85rem;
        }

        .feed-comment-post {
            color: #0095f6;
            font-weight: 600;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .post-container::-webkit-scrollbar {
            width: 8px;
        }

        .post-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .post-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .post-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .search-tabs-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            gap: 30px;
        }

        .search-bar {
            display: flex;
            flex: 1;
            min-width: 200px;
            max-width: 500px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e0e0e0;
        }

        .search-bar input {
            flex: 1;
            padding: 10px 20px;
            border: none;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.95rem;
            background-color: white;
            color: #333;
        }

        .search-bar input::placeholder {
            color: #aaa;
        }

        .search-button {
            padding: 0 15px;
            border: none;
            background-color: #202b1d;
            color: white;
            cursor: pointer;
            font-family: 'Montserrat', sans-serif;
            transition: background-color 0.3s;
        }

        .search-button:hover {
            background-color: #1a2318;
        }

        .nav-tabs {
            display: flex;
            background-color: rgba(32, 43, 29, 0.1);
            border-radius: 8px;
            padding: 2px;
        }

        .tab-button {
            padding: 8px 25px;
            background: none;
            border: none;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.95rem;
            color: #333;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .tab-button.active {
            background-color: #202b1d;
            color: white;
            font-weight: 500;
        }

        .tab-button:hover:not(.active) {
            background-color: rgba(32, 43, 29, 0.2);
        }

        .follow-btn {
            padding: 6px 15px;
            background-color: #202b1d;
            color: white;
            border: none;
            border-radius: 4px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .follow-btn:hover {
            background-color: #1a2318;
            transform: translateY(-1px);
        }

        .follow-btn.following {
            background-color: #f5f5f5;
            color: #202b1d;
            border: 1px solid #e0e0e0;
        }

        /* Artist Profile Overlay Styles */
        .artist-profile-overlay,
        .comment-overlay,
        .likes-overlay {
            position: fixed;
            top: 0;
            right: -100%;
            width: 100%;
            max-width: 450px;
            height: 100%;
            background-color: #fff;
            z-index: 2000;
            transition: right 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            overflow-y: auto;
            box-shadow: -5px 0 25px rgba(0, 0, 0, 0.15);
            will-change: right;
        }

        .artist-profile-overlay.active,
        .comment-overlay.active,
        .likes-overlay.active {
            right: 0;
        }

        .overlay-content {
            width: 100%;
            height: 100%;
            padding: 25px;
            box-sizing: border-box;
            position: relative;
        }

        .artist-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
            position: relative;
        }

        .artist-avatar-container {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin-bottom: 15px;
            border: 3px solid #202b1d;
        }

        .artist-avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .artist-username {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: #202b1d;
            margin-bottom: 15px;
        }

        .artist-stats {
            display: flex;
            justify-content: space-around;
            width: 100%;
            margin-bottom: 20px;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .stat-number {
            font-weight: 600;
            font-size: 1.2rem;
            color: #202b1d;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #666;
        }

        .artist-bio {
            text-align: center;
            margin-bottom: 25px;
            padding: 0 20px;
            color: #333;
            line-height: 1.5;
        }

        .artist-tabs {
            display: flex;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 20px;
        }

        .artist-tab {
            flex: 1;
            padding: 12px 0;
            background: none;
            border: none;
            font-family: 'Montserrat', sans-serif;
            font-weight: 500;
            color: #666;
            cursor: pointer;
            position: relative;
        }

        .artist-tab.active {
            color: #202b1d;
        }

        .artist-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #202b1d;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .artist-posts-grid,
        .artist-bookmarks-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 5px;
        }

        .artist-post,
        .artist-bookmark {
            aspect-ratio: 1/1;
            overflow: hidden;
        }

        .artist-post img,
        .artist-bookmark img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .artist-post:hover img,
        .artist-bookmark:hover img {
            transform: scale(1.05);
        }

        .close-overlay-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f5f5f5;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close-overlay-btn:hover {
            background-color: #202b1d;
            color: white;
        }

        .close-overlay-btn i {
            font-size: 1.2rem;
        }

        /* Comment Overlay Styles */
        .comment-overlay .overlay-content {
            padding: 15px;
            display: flex;
            flex-direction: column;
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
        }

        .comment-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            color: #202b1d;
        }

        .comments-list {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 15px;
        }

        .comment-item {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .comment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }

        .comment-content {
            flex: 1;
        }

        .comment-user {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .comment-text {
            font-size: 0.85rem;
            line-height: 1.4;
            margin-bottom: 5px;
        }

        .comment-time {
            font-size: 0.7rem;
            color: #888;
        }

        .comment-delete {
            color: #ff4d4f;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.8rem;
            margin-left: 10px;
        }

        .comment-form {
            display: flex;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

        .comment-input {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 8px 15px;
            font-size: 0.9rem;
            outline: none;
        }

        .comment-submit {
            background-color: #202b1d;
            color: white;
            border: none;
            border-radius: 20px;
            padding: 8px 15px;
            margin-left: 10px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        /* Likes Overlay Styles */
        .likes-overlay .overlay-content {
            padding: 15px;
        }

        .likes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
        }

        .likes-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            color: #202b1d;
        }

        .likes-count {
            font-size: 0.9rem;
            color: #666;
        }

        .likes-list {
            overflow-y: auto;
        }

        .like-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .like-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }

        .like-user-info {
            flex: 1;
        }

        .like-username {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 3px;
        }

        .like-name {
            font-size: 0.8rem;
            color: #888;
        }

        .like-follow-btn {
            padding: 6px 12px;
            background-color: #202b1d;
            color: white;
            border: none;
            border-radius: 4px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.8rem;
            cursor: pointer;
        }

        .like-follow-btn.following {
            background-color: #f5f5f5;
            color: #202b1d;
            border: 1px solid #e0e0e0;
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #333;
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .toast.show {
            opacity: 1;
        }

        /* Overlay Backdrop */
        .overlay-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .overlay-backdrop.active {
            opacity: 1;
            visibility: visible;
        }

        .feed-avatar:active,
        .feed-username:active {
            transform: scale(0.98);
        }

        .feed-avatar,
        .feed-username {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .feed-username:hover {
            color: #202b1d;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="main-content">
        <nav id="navbar">
            <div class="nav-left">
                <ul class="nav-links">
                    <li><a href="../Pages/home.php">Home</a></li>
                    <li><a href="../index.php">Features</a></li>
                    <li><a href="../index.php">Gallery</a></li>
                    <li><a href="../Pages/Marketplace.php" class="marketplace-link">Marketplace</a></li>
                    <li><a href="../index.php#about">About Us</a></li>
                </ul>
            </div>
            <div class="logo">VirtuGallery</div>
            <div class="nav-right">
                <div class="profile-container">
                    <div class="profile-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="profile-menu">
                        <a href="../Pages/UserProfilePage.php">Profile</a>
                        <a href="../Pages/UserProfilePage.php">My Collection</a>
                        <a href="#" id="logout-btn">Logout</a>
                    </div>
                </div>
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav>

        <div class="content-wrapper">
            <div class="parent">
                <div class="content-container">
                    <div class="searchtab">
                        <div class="search-tabs-container">
                            <div class="search-bar">
                                <input type="text" id="search-input" placeholder="Search artworks, creators...">
                                <button class="search-button" id="search-button">Search</button>
                            </div>
                            <div class="nav-tabs">
                                <button class="tab-button active" id="for-you-tab">For You</button>
                                <button class="tab-button" id="following-tab">Following</button>
                            </div>
                        </div>
                    </div>
                    <div class="sidebartab">

                        <div class="AddWork">
                            <div class="add-work-container">
                                <div class="add-work-placeholder">Add Your Artwork</div>
                                <button class="add-image-btn" id="add-artwork-btn" onclick="location.href='../VirtuGallery/Pages/uploadArtworkPage.php'">+</button>
                            </div>
                        </div>
                        <div class="sidebarbuttons">
                            <a href="../Pages/home.php" class="Home active">
                                <i class="fas fa-home"></i>
                                <span>Home</span>
                            </a>
                            <a href="../Pages/notifications.php" class="Notifications">
                                <i class="fas fa-bell"></i>
                                <span>Notifications</span>
                            </a>
                            <a href="../Pages/ranking.php" class="Ranking">
                                <i class="fas fa-trophy"></i>
                                <span>Ranking</span>
                            </a>
                            <a href="../Pages/settings.php" class="Settings">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
                            </a>
                        </div>
                    </div>
                    <div class="post-container" id="artwork-grid">
                        <?php if (isset($error)): ?>
                            <div class="error"><?php echo htmlspecialchars($error); ?></div>
                        <?php elseif (empty($posts)): ?>
                            <div class="error">No posts available.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="artist-profile-overlay" id="artistProfileOverlay">
                    <div class="overlay-content">
                        <div class="artist-header">
                            <div class="artist-avatar-container">
                                <img src="../images/babaengghibli.png" class="artist-avatar" alt="Artist">
                            </div>
                            <h2 class="artist-username">chatgpt_artist</h2>
                            <button class="follow-btn">Follow</button>
                        </div>
                        <div class="artist-stats">
                            <div class="stat-item">
                                <span class="stat-number">1,024</span>
                                <span class="stat-label">Posts</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">12.3K</span>
                                <span class="stat-label">Followers</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">543</span>
                                <span class="stat-label">Following</span>
                            </div>
                        </div>
                        <div class="artist-bio">
                            Digital artist inspired by Studio Ghibli. Creating magical worlds one pixel at a time.
                        </div>
                        <div class="artist-tabs">
                            <button class="artist-tab active" data-tab="posts">Posts</button>
                            <button class="artist-tab" data-tab="bookmarks">Bookmarks</button>
                        </div>
                        <div class="artist-content">
                            <div class="tab-content active" id="posts-tab">
                                <div class="artist-posts-grid">
                                    <div class="artist-post">
                                        <img src="../photos/955e466e687fabb75cf25ef036cb9dd3.jpg" alt="Post" />
                                    </div>
                                    <div class="artist-post">
                                        <img src="../photos/797dab886187eb08eca67a0dffc1dbd4.jpg" alt="Post" />
                                    </div>
                                    <div class="artist-post">
                                        <img src="../photos/ee8f62b168540e94bf4307e9090e853d.jpg" alt="Post" />
                                    </div>
                                    <div class="artist-post">
                                        <img src="../photos/5b3807dc2bc563044d3e6f6ffba24c55.jpg" alt="Post">
                                    </div>
                                </div>
                            </div>
                            <div class="tab-content" id="bookmarks-tab">
                                <div class="artist-bookmarks-grid">
                                    <div class="artist-bookmark">
                                        <img src="../images/easaportrait.png" alt="Bookmark">
                                    </div>
                                    <div class="artist-bookmark">
                                        <img src="../images/mgamuning.jpg" alt="Bookmark">
                                    </div>
                                    <div class="artist-bookmark">
                                        <img src="../images/lalalalal.jpg" alt="Bookmark">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button class="close-overlay-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="comment-overlay" id="commentOverlay">
                    <div class="overlay-content">
                        <div class="comment-header">
                            <div class="comment-title">Comments</div>
                            <button class="close-overlay-btn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="comments-list">
                            <div class="comment-item">
                                <img src="../images/babaengghibli.png" class="comment-avatar" alt="User">
                                <div class="comment-content">
                                    <div class="comment-user">art_lover42</div>
                                    <div class="comment-text">This is absolutely stunning! The colors are so vibrant.</div>
                                    <div class="comment-time">2 hours ago</div>
                                </div>
                                <button class="comment-delete"><i class="fas fa-trash"></i></button>
                            </div>
                            <div class="comment-item">
                                <img src="../images/mgamuning.jpg" class="comment-avatar" alt="User">
                                <div class="comment-content">
                                    <div class="comment-user">digital_creator</div>
                                    <div class="comment-text">What software did you use for this? The textures are amazing!</div>
                                    <div class="comment-time">3 hours ago</div>
                                </div>
                                <button class="comment-delete"><i class="fas fa-trash"></i></button>
                            </div>
                            <div class="comment-item">
                                <img src="../images/lalalalal.jpg" class="comment-avatar" alt="User">
                                <div class="comment-content">
                                    <div class="comment-user">ghibli_fan</div>
                                    <div class="comment-text">This reminds me of Princess Mononoke! Beautiful work.</div>
                                    <div class="comment-time">5 hours ago</div>
                                </div>
                                <button class="comment-delete"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <div class="comment-form">
                            <input type="text" class="comment-input" placeholder="Add a comment...">
                            <button class="comment-submit">Post</button>
                        </div>
                    </div>
                </div>
                <div class="likes-overlay" id="likesOverlay">
                    <div class="overlay-content">
                        <div class="likes-header">
                            <div>
                                <div class="likes-title">Likes</div>
                                <div class="likes-count">1,024 likes</div>
                            </div>
                            <button class="close-overlay-btn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="likes-list">
                            <div class="like-item">
                                <img src="../images/babaengghibli.png" class="like-avatar" alt="User">
                                <div class="like-user-info">
                                    <div class="like-username">art_lover42</div>
                                    <div class="like-name">Art Enthusiast</div>
                                </div>
                                <button class="like-follow-btn">Follow</button>
                            </div>
                            <div class="like-item">
                                <img src="../images/mgamuning.jpg" class="like-avatar" alt="User">
                                <div class="like-user-info">
                                    <div class="like-username">digital_creator</div>
                                    <div class="like-name">Digital Artist</div>
                                </div>
                                <button class="like-follow-btn following">Following</button>
                            </div>
                            <div class="like-item">
                                <img src="../images/lalalalal.jpg" class="like-avatar" alt="User">
                                <div class="like-user-info">
                                    <div class="like-username">ghibli_fan</div>
                                    <div class="like-name">Studio Ghibli Fan</div>
                                </div>
                                <button class="like-follow-btn">Follow</button>
                            </div>
                            <div class="like-item">
                                <img src="../images/shanaya.jpg" class="like-avatar" alt="User">
                                <div class="like-user-info">
                                    <div class="like-username">art_collector</div>
                                    <div class="like-name">Contemporary Art Collector</div>
                                </div>
                                <button class="like-follow-btn">Follow</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="overlay-backdrop" id="overlayBackdrop"></div>
        <div class="toast" id="toast"></div>
    </div>

    <script>
        // Pass PHP posts to JavaScript
        const posts = <?php echo json_encode($posts ?: []); ?>;
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        const currentUserId = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
        let redirectAfterLogin = '';

        document.addEventListener('DOMContentLoaded', function() {
            initApp();

            // Navigation bar scroll effect
            const navbar = document.getElementById('navbar');
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });

            // Mobile menu toggle
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            const navLinks = document.querySelector('.nav-links');
            mobileMenuBtn.addEventListener('click', function() {
                navLinks.classList.toggle('active');
                const icon = mobileMenuBtn.querySelector('i');
                if (navLinks.classList.contains('active')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });

            // Close mobile menu when clicking a link
            document.querySelectorAll('.nav-links a').forEach(link => {
                link.addEventListener('click', function() {
                    if (navLinks.classList.contains('active')) {
                        navLinks.classList.remove('active');
                        const icon = mobileMenuBtn.querySelector('i');
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                });
            });

            // Tab switching functionality
            document.getElementById('for-you-tab').addEventListener('click', function() {
                this.classList.add('active');
                document.getElementById('following-tab').classList.remove('active');
                renderArtworks();
            });

            document.getElementById('following-tab').addEventListener('click', function() {
                this.classList.add('active');
                document.getElementById('for-you-tab').classList.remove('active');
                renderArtworks();
            });

            // Add Artwork button functionality
            document.getElementById('add-artwork-btn').addEventListener('click', function() {
                if (isLoggedIn) {
                    window.location.href = '../Pages/uploadArtworkPage.php';
                } else {
                    redirectAfterLogin = '../Pages/uploadArtworkPage.php';
                    showModal('login');
                }
            });

            // Logout functionality [NEW]
            document.getElementById('logout-btn').addEventListener('click', function(e) {
                e.preventDefault(); // Prevent default link behavior
                logoutUser();
            });
        });

        function initApp() {
            initEventListeners();
            renderArtworks();
        }

        function initEventListeners() {
            document.addEventListener('click', function(e) {
                // Like button functionality
                if (e.target.closest('.like-btn')) {
                    if (!isLoggedIn) {
                        redirectAfterLogin = window.location.href;
                        showModal('login');
                        return;
                    }

                    const btn = e.target.closest('.like-btn');
                    const feedContainer = btn.closest('.feed-container');
                    const artworkId = parseInt(feedContainer.dataset.artworkId);

                    fetch('../like_post.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                post_id: artworkId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const artwork = posts.find(a => a.id === artworkId);
                                if (artwork) {
                                    artwork.is_liked = data.is_liked;
                                    artwork.likes = data.like_count;

                                    const icon = btn.querySelector('i');
                                    const likesElement = feedContainer.querySelector('.feed-likes');

                                    if (artwork.is_liked) {
                                        icon.classList.remove('far');
                                        icon.classList.add('fas');
                                        btn.classList.add('liked');
                                    } else {
                                        icon.classList.remove('fas');
                                        icon.classList.add('far');
                                        btn.classList.remove('liked');
                                    }

                                    likesElement.textContent = artwork.likes.toLocaleString() + ' likes';
                                    showToast(artwork.is_liked ? 'Post liked' : 'Post unliked');
                                }
                            } else {
                                showToast(data.message || 'Error liking post');
                            }
                        })
                        .catch(() => showToast('Network error'));
                }

                // Bookmark button functionality
                if (e.target.closest('.bookmark-btn')) {
                    if (!isLoggedIn) {
                        redirectAfterLogin = window.location.href;
                        showModal('login');
                        return;
                    }

                    const btn = e.target.closest('.bookmark-btn');
                    const feedContainer = btn.closest('.feed-container');
                    const artworkId = parseInt(feedContainer.dataset.artworkId);
                    const artwork = posts.find(a => a.id === artworkId);

                    if (artwork) {
                        artwork.isBookmarked = !artwork.isBookmarked;

                        const icon = btn.querySelector('i');
                        if (artwork.isBookmarked) {
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                            btn.classList.add('bookmarked');
                            showToast('Added to your favorites');
                        } else {
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                            btn.classList.remove('bookmarked');
                            showToast('Removed from your favorites');
                        }
                    }
                }

                // Comment button functionality
                if (e.target.closest('.fa-comment') || e.target.closest('.feed-comments')) {
                    const feedContainer = e.target.closest('.feed-container');
                    const artworkId = parseInt(feedContainer.dataset.artworkId);
                    openCommentOverlay(artworkId);
                }

                // Artist profile functionality
                if (e.target.closest('.feed-username') || e.target.closest('.feed-avatar')) {
                    const username = e.target.closest('.feed-container').querySelector('.feed-username').textContent;
                    openArtistProfile(username);
                }
            });

            // Comment form submission
            document.querySelector('.comment-submit').addEventListener('click', function() {
                if (!isLoggedIn) {
                    redirectAfterLogin = window.location.href;
                    showModal('login');
                    return;
                }

                const commentInput = document.querySelector('.comment-input');
                const commentText = commentInput.value.trim();
                const postId = document.querySelector('.comment-overlay').dataset.postId;

                if (commentText && postId) {
                    fetch('../add_comment.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                post_id: postId,
                                comment_text: commentText
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const commentsList = document.querySelector('.comments-list');
                                const newComment = document.createElement('div');
                                newComment.className = 'comment-item';
                                const createdAt = new Date(data.comment.created_at);
                                const now = new Date();
                                const hoursDiff = Math.round((now - createdAt) / (1000 * 60 * 60));
                                const timeText = hoursDiff <= 24 ? `${hoursDiff} hours ago` : createdAt.toLocaleDateString('en-US', {
                                    month: 'short',
                                    day: 'numeric',
                                    year: 'numeric'
                                });

                                newComment.innerHTML = `
                        <img src="https://via.placeholder.com/40" class="comment-avatar" alt="User">
                        <div class="comment-content">
                            <div class="comment-user">${data.comment.username}</div>
                            <div class="comment-text">${data.comment.comment_text}</div>
                            <div class="comment-time">${timeText}</div>
                        </div>
                        ${data.comment.username === '<?php echo $username; ?>' ? '<button class="comment-delete"><i class="fas fa-trash"></i></button>' : ''}
                    `;

                                commentsList.prepend(newComment);
                                commentInput.value = '';
                                showToast('Comment added');

                                // Update comment count
                                const artwork = posts.find(a => a.id === parseInt(postId));
                                if (artwork) {
                                    artwork.comments = (artwork.comments || 0) + 1;
                                    const feedContainer = document.querySelector(`.feed-container[data-artwork-id="${postId}"]`);
                                    if (feedContainer) {
                                        feedContainer.querySelector('.feed-comments').textContent = `View all ${artwork.comments} comments`;
                                    }
                                }

                                // Re-attach delete event
                                newComment.querySelector('.comment-delete')?.addEventListener('click', function() {
                                    newComment.remove();
                                    updateCommentCount(postId, -1);
                                });
                            } else {
                                showToast(data.message || 'Error adding comment');
                            }
                        })
                        .catch(() => showToast('Network error'));
                }
            });

            // Search functionality
            const searchInput = document.getElementById('search-input');
            const searchButton = document.getElementById('search-button');

            searchButton.addEventListener('click', performSearch);
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
        }

        function renderArtworks() {
            const artworkGrid = document.getElementById('artwork-grid');
            artworkGrid.innerHTML = '';

            if (posts.length === 0) {
                artworkGrid.innerHTML = '<div class="error">No posts available.</div>';
                return;
            }

            posts.forEach(artwork => {
                const createdAt = new Date(artwork.created_at);
                const now = new Date();
                const hoursDiff = Math.round((now - createdAt) / (1000 * 60 * 60));
                const timestamp = hoursDiff <= 24 ? `${hoursDiff} hours ago` : createdAt.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });

                const artworkElement = document.createElement('div');
                artworkElement.className = 'feed-container';
                artworkElement.dataset.artworkId = artwork.id;

                artworkElement.innerHTML = `
            <div class="feed-header">
                <img src="https://via.placeholder.com/36" class="feed-avatar" alt="User">
                <div class="feed-user-info">
                    <div class="feed-username">${artwork.username || 'Anonymous'}</div>
                    <div class="feed-location">${artwork.category || 'Art'}</div>
                </div>
                <button class="follow-btn">Follow</button>
            </div>
            <div class="feed-image-container">
                <img src="../${artwork.image_path}" class="feed-image" alt="${artwork.title}">
            </div>
            <div class="feed-actions">
                <div class="feed-action-left">
                    <button class="feed-action-btn like-btn ${artwork.is_liked ? 'liked' : ''}">
                        <i class="${artwork.is_liked ? 'fas' : 'far'} fa-heart"></i>
                    </button>
                    <button class="feed-action-btn">
                        <i class="far fa-comment"></i>
                    </button>
                </div>
                <button class="feed-action-btn bookmark-btn ${artwork.isBookmarked ? 'bookmarked' : ''}">
                    <i class="${artwork.isBookmarked ? 'fas' : 'far'} fa-bookmark"></i>
                </button>
            </div>
            <div class="feed-likes">${(artwork.likes || 0).toLocaleString()} likes</div>
            <div class="feed-caption">
                <span class="feed-caption-username">${artwork.username || 'Anonymous'}</span>
                <span>${artwork.description}</span>
            </div>
            ${artwork.post_type === 'product' && artwork.price ? `<div class="feed-caption">Price: ₱${parseFloat(artwork.price).toFixed(2)}</div>` : ''}
            ${artwork.tags ? `<div class="feed-caption post-tags">${artwork.tags}</div>` : ''}
            <div class="feed-comments">View all ${(artwork.comments || 0)} comments</div>
            <div class="feed-time">${timestamp}</div>
            <div class="feed-comment-box">
                <input type="text" class="feed-comment-input" placeholder="Add a comment...">
                <button class="feed-comment-post">Post</button>
            </div>
        `;

                artworkGrid.appendChild(artworkElement);
            });
        }

        function performSearch() {
            const searchTerm = document.getElementById('search-input').value.trim().toLowerCase();
            if (searchTerm) {
                const filteredPosts = posts.filter(post =>
                    post.title.toLowerCase().includes(searchTerm) ||
                    post.username.toLowerCase().includes(searchTerm) ||
                    post.description.toLowerCase().includes(searchTerm) ||
                    (post.tags && post.tags.toLowerCase().includes(searchTerm))
                );

                posts.length = 0;
                posts.push(...filteredPosts);
                renderArtworks();

                showToast(filteredPosts.length > 0 ? `Found ${filteredPosts.length} results for "${searchTerm}"` : 'No results found');
                document.getElementById('search-input').value = '';
            }
        }

        function openArtistProfile(username) {
            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
            document.getElementById('overlayBackdrop').classList.add('active');
            setTimeout(() => {
                document.getElementById('artistProfileOverlay').classList.add('active');
            }, 10);
            document.querySelector('.artist-username').textContent = username;
        }

        function closeArtistProfile() {
            document.getElementById('artistProfileOverlay').classList.remove('active');
            setTimeout(() => {
                document.getElementById('overlayBackdrop').classList.remove('active');
                document.body.style.overflow = 'auto';
                document.documentElement.style.overflow = 'auto';
            }, 400);
        }

        function openCommentOverlay(postId) {
            if (!isLoggedIn) {
                redirectAfterLogin = window.location.href;
                showModal('login');
                return;
            }

            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
            document.getElementById('overlayBackdrop').classList.add('active');
            const commentOverlay = document.getElementById('commentOverlay');
            commentOverlay.dataset.postId = postId;

            // Fetch comments
            fetch(`../get_comments.php?post_id=${postId}`)
                .then(response => response.json())
                .then(data => {
                    const commentsList = document.querySelector('.comments-list');
                    commentsList.innerHTML = '';

                    if (data.success && data.comments.length > 0) {
                        data.comments.forEach(comment => {
                            const commentElement = document.createElement('div');
                            commentElement.className = 'comment-item';
                            const createdAt = new Date(comment.created_at);
                            const now = new Date();
                            const hoursDiff = Math.round((now - createdAt) / (1000 * 60 * 60));
                            const timeText = hoursDiff <= 24 ? `${hoursDiff} hours ago` : createdAt.toLocaleDateString('en-US', {
                                month: 'short',
                                day: 'numeric',
                                year: 'numeric'
                            });

                            commentElement.innerHTML = `
                    <img src="https://via.placeholder.com/40" class="comment-avatar" alt="User">
                    <div class="comment-content">
                        <div class="comment-user">${comment.username}</div>
                        <div class="comment-text">${comment.comment_text}</div>
                        <div class="comment-time">${timeText}</div>
                    </div>
                    ${comment.username === '<?php echo $username; ?>' ? '<button class="comment-delete"><i class="fas fa-trash"></i></button>' : ''}
                `;

                            commentsList.appendChild(commentElement);

                            // Add delete event for user's own comments
                            const deleteBtn = commentElement.querySelector('.comment-delete');
                            if (deleteBtn) {
                                deleteBtn.addEventListener('click', function() {
                                    commentElement.remove();
                                    updateCommentCount(postId, -1);
                                });
                            }
                        });
                    } else {
                        commentsList.innerHTML = '<div style="text-align: center; color: #888;">No comments yet.</div>';
                    }

                    setTimeout(() => {
                        commentOverlay.classList.add('active');
                    }, 10);
                })
                .catch(() => showToast('Network error'));
        }

        function closeCommentOverlay() {
            document.getElementById('commentOverlay').classList.remove('active');
            setTimeout(() => {
                document.getElementById('overlayBackdrop').classList.remove('active');
                document.body.style.overflow = 'auto';
                document.documentElement.style.overflow = 'auto';
            }, 400);
        }

        function openLikesOverlay() {
            if (!isLoggedIn) {
                redirectAfterLogin = window.location.href;
                showModal('login');
                return;
            }

            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
            document.getElementById('overlayBackdrop').classList.add('active');
            setTimeout(() => {
                document.getElementById('likesOverlay').classList.add('active');
            }, 10);
        }

        function closeLikesOverlay() {
            document.getElementById('likesOverlay').classList.remove('active');
            setTimeout(() => {
                document.getElementById('overlayBackdrop').classList.remove('active');
                document.body.style.overflow = 'auto';
                document.documentElement.style.overflow = 'auto';
            }, 400);
        }

        function updateCommentCount(postId, delta) {
            const artwork = posts.find(a => a.id === parseInt(postId));
            if (artwork) {
                artwork.comments = (artwork.comments || 0) + delta;
                const feedContainer = document.querySelector(`.feed-container[data-artwork-id="${postId}"]`);
                if (feedContainer) {
                    feedContainer.querySelector('.feed-comments').textContent = `View all ${artwork.comments} comments`;
                }
            }
        }

        // Logout function [NEW]
        function logoutUser() {
            fetch('../logout.php', {
                    method: 'GET', // logout.php expects GET
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Logged out successfully');
                        setTimeout(() => {
                            window.location.href = '../index.php'; // Redirect to login
                        }, 1000); // Delay for toast visibility
                    } else {
                        showToast(data.message || 'Logout failed');
                    }
                })
                .catch(() => {
                    showToast('Network error during logout');
                });
        }

        document.getElementById('overlayBackdrop').addEventListener('click', function() {
            if (document.getElementById('artistProfileOverlay').classList.contains('active')) {
                closeArtistProfile();
            }
            if (document.getElementById('commentOverlay').classList.contains('active')) {
                closeCommentOverlay();
            }
            if (document.getElementById('likesOverlay').classList.contains('active')) {
                closeLikesOverlay();
            }
        });

        document.querySelectorAll('.close-overlay-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const overlay = this.closest('.artist-profile-overlay, .comment-overlay, .likes-overlay');
                if (overlay.classList.contains('artist-profile-overlay')) {
                    closeArtistProfile();
                } else if (overlay.classList.contains('comment-overlay')) {
                    closeCommentOverlay();
                } else if (overlay.classList.contains('likes-overlay')) {
                    closeLikesOverlay();
                }
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (document.getElementById('artistProfileOverlay').classList.contains('active')) {
                    closeArtistProfile();
                } else if (document.getElementById('commentOverlay').classList.contains('active')) {
                    closeCommentOverlay();
                } else if (document.getElementById('likesOverlay').classList.contains('active')) {
                    closeLikesOverlay();
                }
            }
        });

        document.querySelectorAll('.artist-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.artist-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId + '-tab').classList.add('active');
            });
        });

        document.querySelector('.artist-header .follow-btn').addEventListener('click', function() {
            this.textContent = this.textContent === 'Follow' ? 'Following' : 'Follow';
            this.classList.toggle('following');
        });

        document.querySelectorAll('.like-follow-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                this.textContent = this.textContent === 'Follow' ? 'Following' : 'Follow';
                this.classList.toggle('following');
            });
        });

        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        function showModal(type) {
            showToast(`Please login to ${type === 'login' ? 'continue' : type}`);
        }
    </script>
</body>

</html>cript