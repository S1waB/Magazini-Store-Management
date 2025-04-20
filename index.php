<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
// Include necessary manager files based on the page
if ($page == 'products') {
    require 'products_manager.php'; // Include the products manager
    require 'categories_manager.php';

    // Handle search and filter
    $search = $_GET['search'] ?? '';
    $category_filter = $_GET['category_filter'] ?? '';

    // Fetch all products with search and filter
    $products = getProducts($search, $category_filter);

    // Fetch all categories for the filter dropdown
    $categories = getCategories();
} elseif ($page == 'cart') {
    require_once 'cart_manager.php'; // Include the cart manager
    // Fetch cart contents
    $cartContents = getCartContents($_SESSION['user_id']);
    require_once 'orders_manager.php';
    // Fetch user orders
    $orders = getUserOrders($_SESSION['user_id']);
} elseif ($page == 'orders') {
    require 'orders_manager.php';
    // Fetch user orders
    $orders = getUserOrders($_SESSION['user_id']);
}
?>


<?php include 'layouts/header.php'; ?>



<div class="content">
    <?php if ($page == 'home'): ?>
        <div class="container mt-4">
            <!-- Store Name and Logo Section -->
            <section id="store-intro" class="text-center mb-5">
                <img src="images/MagaZini-logo.png" alt="MagaZiny Logo" class="img-fluid mb-3" style="max-height: 150px;">
                <h1>Welcome to MagaZiny</h1>
                <p class="lead">Your One-Stop Grocery Store</p>
            </section>

            <!-- Store Description and Image Section -->
            <section id="store-description" class="row align-items-center mb-5">
                <div class="col-md-6">
                    <img src="images/login-welcome.jpg" alt="Grocery Store" class="img-fluid rounded">
                </div>
                <div class="col-md-6">
                    <h2>About Our Store</h2>
                    <p>
                        MagaZiny is your one-stop shop for high-quality products. We pride ourselves on offering a wide range of items to meet your needs. Our mission is to provide exceptional value and service to our customers. Whether you're looking for everyday essentials or unique finds, MagaZiny has something for everyone.
                    </p>
                </div>
            </section>

            <!-- Services Carousel Section -->
            <section id="services" class="mb-5">
                <h2 class="text-center mb-4">Our Services</h2>
                <div id="servicesCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <img src="images/Bakery.jpg" class="d-block w-100" alt="Bakery">
                            <div class="carousel-caption d-none d-md-block">
                                <h5>Bakery</h5>
                                <p>Freshly baked bread, pastries, and cakes made daily.</p>
                            </div>
                        </div>
                        <div class="carousel-item">
                            <img src="images/Cosmetics.jpg" class="d-block w-100" alt="Cosmetics">
                            <div class="carousel-caption d-none d-md-block">
                                <h5>Cosmetics</h5>
                                <p>High-quality beauty and skincare products.</p>
                            </div>
                        </div>
                        <div class="carousel-item">
                            <img src="images/Dairy.jpg" class="d-block w-100" alt="Dairy">
                            <div class="carousel-caption d-none d-md-block">
                                <h5>Dairy</h5>
                                <p>Fresh milk, cheese, and other dairy products.</p>
                            </div>
                        </div>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#servicesCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#servicesCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </section>
        </div>
        <!-- Services Partners Section -->
        <section id="Partners" class="mb-5">
            <div class="container mt-4">
                <h2 class="text-center mb-4">Our Partners</h2>
                <div class="row">
                    <div class="col-md-3">
                        <div class="card shadow-lg border-0 text-center">
                            <img src="images/bioferma-logo.jpg" class="card-img-top p-3" alt="Bioferma">
                            <div class="card-body">
                                <h5 class="card-title">Bioferma</h5>
                                <p class="card-text">Organic and fresh farm products for a healthier life.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-lg border-0 text-center">
                            <img src="images/vitalait-logo.png" class="card-img-top p-3" alt="Vitalait">
                            <div class="card-body">
                                <h5 class="card-title">Vitalait</h5>
                                <p class="card-text">Premium dairy products with superior quality and taste.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-lg border-0 text-center">
                            <img src="images/takacim-logo.png" class="card-img-top p-3" alt="Takacim">
                            <div class="card-body">
                                <h5 class="card-title">Takacim</h5>
                                <p class="card-text">Trusted brand for top-quality grocery essentials.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-lg border-0 text-center">
                            <img src="images/delice-logo.jpg" class="card-img-top p-3" alt="Délice">
                            <div class="card-body">
                                <h5 class="card-title">Délice</h5>
                                <p class="card-text">A household name in dairy and beverage products.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php elseif ($page == 'products'): ?>
        <div class="container mt-5">
            <h1>Products</h1>

            <!-- Search and Filter Form -->
            <form action="index.php" method="GET" class="mb-4">
                <input type="hidden" name="page" value="products"> <!-- Add this line -->
                <div class="row">
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control" placeholder="Search by product name" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="category_filter" class="form-control">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>

            <!-- Product Cards -->
            <div class="row">
                <?php if (empty($products)): ?>
                    <div class="col-12">
                        <p>No products found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <?php if ($product['status'] == 'active'): ?>
                            <div class="col-md-3 mb-4">
                                <div class="card h-100">
                                    <img src="<?= $product['image'] ?>" class="card-img-top" alt="<?= $product['name'] ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= $product['name'] ?></h5>
                                        <p class="card-text"><?= $product['description'] ?></p>
                                        <p class="card-text"><strong>Price:</strong> $<?= $product['price'] ?></p>
                                        <p class="card-text"><strong>Category:</strong> <?= $product['category_name'] ?></p>
                                        <form action="cart_manager.php" method="POST">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
                                            <div class="mb-3">
                                                <label for="quantity" class="form-label">Quantity</label>
                                                <input type="number" name="quantity" class="form-control" value="1" min="1">
                                            </div>
                                            <button type="submit" name="add_to_cart" class="btn btn-primary w-100">Add to Cart</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($page == 'cart'): ?>
        <div class="container mt-5">

            <h1>Your Cart </h1>

            <?php if (empty($cartContents)): ?>
                <p>Your cart is empty.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartContents as $item): ?>
                            <tr>
                                <td>
                                    <img src="<?= $item['image'] ?>" alt="<?= $item['name'] ?>" style="width: 50px;">
                                    <?= htmlspecialchars($item['name']) ?>
                                </td>
                                <td>$<?= $item['price'] ?></td>
                                <td>
                                    <form action="cart_manager.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="cart_item_id" value="<?= $item['cart_item_id'] ?>">
                                        <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" class="form-control" style="width: 80px;">
                                        <button type="submit" name="update_cart" class="btn btn-sm btn-primary">Update</button>
                                    </form>
                                </td>
                                <td>$<?= $item['price'] * $item['quantity'] ?></td>
                                <td>
                                    <form action="cart_manager.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="cart_item_id" value="<?= $item['cart_item_id'] ?>">
                                        <button type="submit" name="remove_from_cart" class="btn btn-sm btn-danger">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Order Button -->
                <form action="orders_manager.php" method="POST">
                    <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
                    <button type="submit" name="create_order" class="btn btn-success">Order Now</button>
                </form>

                <!-- Clear Cart Button -->
                <form action="cart_manager.php" method="POST">
                    <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
                    <button type="submit" name="clear_cart" class="btn btn-danger">Clear Cart</button>
                </form>
            <?php endif; ?>
        </div>

    <?php elseif ($page == 'orders'): ?>
        <div class="container mt-5">
            <?php
            if (isset($_GET['success'])) {
                echo "<div class='alert alert-success'>" . htmlspecialchars($_GET['success']) . "</div>";
            }
            if (isset($_GET['error'])) {
                echo "<div class='alert alert-danger'>" . htmlspecialchars($_GET['error']) . "</div>";
            } ?>
            <h1>Your Orders</h1>

            <?php if (empty($orders)): ?>
                <p>You have no orders.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Total Price</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= $order['id'] ?></td>
                                <td>$<?= $order['total_price'] ?></td>
                                <td><?= $order['status'] ?></td>
                                <td><?= $order['created_at'] ?></td>
                                <td>
                                    <?php if ($order['status'] == 'pending'): ?>
                                        <form action="orders_manager.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <button type="submit" name="cancel_order" class="btn btn-sm btn-danger">Cancel</button>
                                        </form>
                                    <?php endif; ?>
                                    <form action="orders_manager.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
                                        <button type="submit" name="reorder" class="btn btn-sm btn-success">Reorder</button>
                                    </form>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php elseif ($page == 'profile'): ?>
        <?php include 'profile.php'; ?>
    <?php elseif ($page == 'contact'): ?>
        <div class="container mt-5">
            <div class="card mx-auto">
                <div class="card-header bg-primary text-white text-center">
                    <h2>Contact us</h2>
                </div>
                <div class="card-body">
                    <form>
                        <!-- First Row: Nom and Adresse e-mail -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="name" placeholder="Entrez votre nom">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Adresse e-mail</label>
                                <input type="email" class="form-control" id="email" placeholder="Entrez votre adresse e-mail">
                            </div>
                        </div>

                        <!-- Second Row: Sujet -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="subject" class="form-label">Sujet</label>
                                <input type="text" class="form-control" id="subject" placeholder="Entrez le sujet">
                            </div>
                        </div>

                        <!-- Third Row: Message -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" rows="4" placeholder="Écrivez votre message"></textarea>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary w-100">Envoyer</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php else: ?>
        <p>Page Not Found</p>
    <?php endif; ?>
</div>


<?php include 'layouts/footer.php'; ?>