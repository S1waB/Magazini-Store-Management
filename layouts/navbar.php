<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : null; // No default role
require 'users_manager.php';
?>
<style>
    /* Navbar Styles */
    .navbar-bleu {
        background-color: #007bff;
        /* Blue background */
        padding: 10px 0;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .navbar-brand {
        color: white !important;
        /* White text for brand */
        font-size: 1.5rem;
        font-weight: bold;
    }

    .navbar-brand:hover {
        color: #e0e0e0 !important;
        /* Lighter white on hover */
    }

    .navbar-toggler {
        border-color: rgba(255, 255, 255, 0.5);
        /* Light border for toggler */
    }

    .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3e%3cpath stroke='rgba(255, 255, 255, 0.5)' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }

    .nav-link {
        color: white !important;
        /* White text for links */
        font-size: 1rem;
        margin: 0 10px;
        transition: color 0.3s ease;
    }

    .nav-link:hover {
        color: #e0e0e0 !important;
        /* Lighter white on hover */
    }

    /* Active Link Style */
    .nav-link.active {
        font-weight: bold;
        color: #ffffff !important;
        /* Bright white for active link */
        border-bottom: 2px solid white;
    }

    /* Dropdown Menu Styles (if applicable) */
    .navbar .dropdown-menu {
        background-color: #007bff;
        /* Blue background for dropdown */
        border: none;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .navbar .dropdown-item {
        color: white !important;
        /* White text for dropdown items */
    }

    .navbar .dropdown-item:hover {
        background-color: #0056b3;
        /* Darker blue on hover */
        color: white !important;
    }

    /* Profile Section Styles */
    .navbar-profile {
        display: flex;
        align-items: center;
        margin-left: 20px;
    }

    .navbar-profile img {
        border-radius: 50%;
        width: 40px;
        height: 40px;
        object-fit: cover;
        margin-right: 10px;
    }

    .navbar-profile .user-info {
        color: white;
        font-size: 1rem;
    }

    /* Responsive Design for Navbar */
    @media (max-width: 768px) {
        .navbar-nav {
            margin-top: 10px;
        }

        .nav-link {
            margin: 5px 0;
        }

        .navbar-profile {
            margin-left: 0;
            margin-top: 10px;
        }
    }
</style>
<nav class="navbar navbar-expand-lg navbar-bleu bg-bleu">
    <div class="container">
        <a class="navbar-brand" href="index.php">MagaZini</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <!-- Home Link (Visible to All) -->
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>

                <li class="nav-item"><a class="nav-link" href="index.php?page=products">Products</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=cart">Cart</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=orders">Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=profile&userId=<?= $_SESSION['user_id'] ?>">Profile</a></li>
                <!-- Contact Link -->
                <li class="nav-item"><a class="nav-link" href="index.php?page=contact">Contact</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <!-- Profile Section -->
                <li class="nav-item navbar-profile">
                    <?php $user = getUserById($_SESSION['user_id']); ?>
                    <img src="<?= $user['profile_pic'] ? $user['profile_pic'] : 'https://via.placeholder.com/40' ?>"
                        alt="Profile Picture">
                    <div class="user-info">
                        <?= $user['name'] . ' ' . $user['prenom'] ?>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>