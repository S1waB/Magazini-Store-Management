<?php
// suppliers_dashboard.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'supplier') {
    header("Location: login.php");
    exit();
}

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
// Include suppliers manager to handle fetching supplier
require 'suppliers_manager.php';

// Include necessary manager files based on the page
if ($page == 'products' || $page == 'my_product') {
    require 'products_manager.php';
    require 'categories_manager.php'; // Include category manager to handle fetching categories
    $categories = getCategories(); // Fetch categories using the function
    $allproducts = getProducts();
    $supplier_products = getProductsBySupplier($_SESSION['user_id']); // Fetch products for the logged-in supplier
} elseif ($page == 'offers' || $page == 'add_offer' || $page == 'add_offer_product' || $page == 'edit_offer') {
    require 'products_manager.php';
    require 'categories_manager.php'; // Include category manager to handle fetching categories
    require 'offers_manager.php';
    $categories = getCategories(); // Fetch categories using the function
    $offers = getOffersBySupplier($_SESSION['user_id']); // Fetch offers for the logged-in supplier
    $products = getProductsBySupplier($_SESSION['user_id']); // Fetch products for the logged-in supplier
} elseif ($page == 'home') {
    $total_products = getTotalProductsBySupplier($_SESSION['user_id']);
    $total_offers = getTotalOffersBySupplier($_SESSION['user_id']);
    $accepted_offers = getAcceptedOffersCountBySupplier($_SESSION['user_id']);
    $rejected_offers = getRejectedOffersCountBySupplier($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            display: flex;
            height: 100vh;
        }

        .sidebar {
            width: 20%;
            background-color: rgb(12, 69, 192);
            color: #fff;
            height: 100vh;
        }

        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: rgb(157, 179, 161);
            color: #fff;
        }

        .content {
            flex: 1;
            padding: 20px;
        }

        .table {
            width: 100%;
        }

        .search-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-filter input,
        .search-filter select {
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Profile Picture -->
        <div class="text-center">
            <?php $user = getUserById($_SESSION['user_id']); ?>
            <img src="<?= $user['profile_pic'] ? $user['profile_pic'] : 'https://via.placeholder.com/150' ?>"
                alt="Profile Picture"
                style="margin-top:10px;border-radius: 50%; width: 150px; height: 150px; object-fit: cover;">
        </div>
        <h4 class="text-center py-3"><?=$user['name'].' '.$user['prenom']?></h4>
        <h1 class="text-center py-3">Supplier Dashboard</h1>
        <a href="suppliers_dashboard.php?page=home" class="<?= $page == 'home' ? 'active' : ''; ?>">
            <i class="bi bi-house"></i> Home
        </a>
        <a href="suppliers_dashboard.php?page=products" class="<?= $page == 'products' ? 'active' : ''; ?>">
            <i class="bi bi-box"></i> Products
        </a>
        <a href="suppliers_dashboard.php?page=offers" class="<?= $page == 'offers' ? 'active' : ''; ?>">
            <i class="bi bi-tags"></i> Offers
        </a>
        <a href="suppliers_dashboard.php?page=profile" class="<?= $page == 'profile' ? 'active' : ''; ?>">
            <i class="bi bi-person"></i> Profile
        </a>
        <a href="logout.php">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>

    <div class="content">
        <?php if ($page == 'home'): ?>
            <h2>Welcome to the Supplier Dashboard</h2>
            <p>Use the sidebar to manage your products and offers.</p>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card text-white mb-3">
                        <div class="card-header">Total Products</div>
                        <div class="card-body">
                            <h5 class="card-title"><?= $total_products ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card text-white  mb-3">
                        <div class="card-header">Total Offers</div>
                        <div class="card-body">
                            <h5 class="card-title"><?= $total_offers ?></h5>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card text-white  mb-3">
                            <div class="card-header">Accepted Offers</div>
                            <div class="card-body">
                                <h5 class="card-title"><?= $accepted_offers ?></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card text-white  mb-3">
                            <div class="card-header">Rejected Offers</div>
                            <div class="card-body">
                                <h5 class="card-title"><?= $rejected_offers ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($page == 'products'): ?>
            <!-- Products List -->
            <h2>Products Manager</h2>
            <div class="col-12">
                <div class="row">
                    <div class="col-6">
                        <!-- Search Bar -->
                        <div class="search-filter">
                            <form action="suppliers_dashboard.php" method="GET" class="d-flex gap-2">
                                <input type="hidden" name="page" value="products">
                                <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" class="form-control">
                                <button type="submit" class="btn btn-primary">Search</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-6 text-end">
                        <a href="suppliers_dashboard.php?page=my_products&id=<?= $_SESSION['user_id'] ?>" class="btn btn-primary mb-3">
                            <i class="bi bi-list"></i> My Products
                        </a>
                    </div>
                </div>
            </div>

            <!-- Products Table -->
            <table class="table table-bordered">
                <thead>
                    <tr>

                        <th>Image</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allproducts)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No products found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allproducts as $product): ?>
                            <tr>

                                <td>
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="<?= $product['image'] ?>" alt="Product Image" style="width: 50px; height: 50px;">
                                    <?php else: ?>
                                        No Image
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['description']) ?></td>
                                <td><?= htmlspecialchars($product['category_name']) ?></td>
                                <td><?= htmlspecialchars($product['price']) ?></td>
                                <td><?= htmlspecialchars($product['quantity']) ?></td>
                                <td><?= htmlspecialchars($product['status']) ?></td>
                                <td>
                                    <a href="suppliers_dashboard.php?page=add_offer_product&id=<?= $product['id'] ?>" class="btn btn-success mb-3">
                                        <i class="bi bi-plus-circle"></i> add Offer
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php elseif ($page == 'my_products' && isset($_GET['id'])): ?>

            <h2>My Products </h2>
            <!-- Products of current supplier Table -->
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty(getProductsBySupplier($_GET['id']))): ?>
                        <tr>
                            <td colspan="8" class="text-center">No products found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (getProductsBySupplier($_GET['id']) as $product): ?>
                            <tr>
                                <td><?php if (!empty($product['image'])): ?>
                                        <img src="<?= $product['image'] ?>" alt="Product Image" style="width: 50px; height: 50px;">
                                    <?php else: ?>
                                        No Image
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['description']) ?></td>
                                <td><?= htmlspecialchars($product['category_name']) ?></td>
                                <td><?= htmlspecialchars($product['price']) ?></td>
                                <td><?= htmlspecialchars($product['quantity']) ?></td>
                                <td><?= htmlspecialchars($product['status']) ?></td>
                                <td>
                                <td>
                                    <a href="suppliers_dashboard.php?page=add_offer_product&id=<?= $product['id'] ?>" class="btn btn-success mb-3">
                                        <i class="bi bi-plus-circle"></i> add Offer
                                    </a>
                                </td>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php elseif ($page == 'offers'): ?>
            <div class="row">
                <div class="col-6 text-end">
                    <h2>Manage Offers</h2>
                </div>

                <div class="col-6 text-end">
                    <a href="suppliers_dashboard.php?page=add_offer" class="btn btn-success mb-3">
                        <i class="bi bi-plus-circle"></i> Add Offer
                    </a>
                </div>
            </div>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($offers as $offer): ?>
                        <tr>
                            <td><?= $offer['id'] ?></td>
                            <td><?= $offer['product_name'] ?? 'N/A' ?></td>
                            <td><?= $offer['quantity'] ?></td>
                            <td><?= $offer['price'] ?></td>
                            <td><?= $offer['status'] ?></td>
                            <td>
                                <?php if ($offer['status'] === 'pending' &&  $offer['admin_created'] == false): ?>
                                    <!-- Edit Button -->
                                    <a href="suppliers_dashboard.php?page=edit_offer&id=<?= $offer['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <!-- Delete Button -->
                                    <form action="offers_manager.php" method="POST" class="d-inline">
                                        <input type="hidden" name="offer_id" value="<?= $offer['id'] ?>">
                                        <input type="hidden" name="current_user_role" value="<?= $_SESSION['role'] ?>">
                                        <input type="hidden" name="supplier_id" value="<?= $offer['supplier_id'] ?>">
                                        <button type="submit" name="delete_offer" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this offer?')">Delete</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($offer['status'] === 'pending' && $offer['admin_created']): ?>
                                    <!-- Accept/Reject Buttons -->
                                    <a href="offers_manager.php?action=accept&id=<?= $offer['id'] ?>&role=<?= $_SESSION['role'] ?>" class="btn btn-success btn-sm">Accept</a>
                                    <a href="offers_manager.php?action=reject&id=<?= $offer['id'] ?>&role=<?= $_SESSION['role'] ?>" class="btn btn-danger btn-sm">Reject</a> <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($page == 'add_offer_product' && isset($_GET['id'])): ?>
            <?php $product = getProductById($_GET['id']); ?>
            <h2> offer about : <?= $product['name'] ?></h2>
            <form action="offers_manager.php" method="POST">
                <!-- Common Fields -->
                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" name="quantity" required>
                </div>
                <div class="mb-3">
                    <label for="price" class="form-label">Price</label>
                    <input type="number" step="0.01" class="form-control" name="price" required>
                </div>
                <!-- Hidden fields for logged-in user information -->
                <input type="hidden" name="supplier_id" value="<?= $_SESSION['user_id'] ?>">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="hidden" name="category_id" value="<?= $product['category_id'] ?>">
                <input type="hidden" name="role" value="<?= $_SESSION['role'] ?>">
                <button type="submit" name="add_offer" class="btn btn-success">Add Offer</button>
            </form>
        <?php elseif ($page == 'add_offer'): ?>
            <h2>Add Offer</h2>
            <form action="offers_manager.php" method="POST">
                <div class="mb-3">
                    <label for="product_id" class="form-label">Select Product</label>
                    <select class="form-control" name="product_id" id="product_id" onchange="toggleProductFields()">
                        <option value="">select a Option</option>
                        <option value="">New Product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= $product['id'] ?>"><?= $product['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Fields for New Product -->
                <div id="new_product_fields">
                    <div class="mb-3">
                        <label for="product_name" class="form-label">Product Name</label>
                        <input type="text" class="form-control" name="product_name" id="product_name">
                    </div>
                    <div class="mb-3">
                        <label for="product_description" class="form-label">Product Description</label>
                        <textarea class="form-control" name="product_description" id="product_description"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-control" name="category_id" id="category_id">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= $category['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="product_price" class="form-label">Product Price</label>
                        <input type="number" step="0.01" class="form-control" name="product_price" id="product_price">
                    </div>
                </div>
                <!-- Common Fields -->
                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" name="quantity" required>
                </div>
                <div class="mb-3">
                    <label for="price" class="form-label">Price</label>
                    <input type="number" step="0.01" class="form-control" name="price" required>
                </div>
                <!-- Hidden fields for logged-in user information -->
                <input type="hidden" name="supplier_id" value="<?= $_SESSION['user_id'] ?>">
                <input type="hidden" name="role" value="<?= $_SESSION['role'] ?>">
                <button type="submit" name="add_offer" class="btn btn-success">Add Offer</button>
            </form>

        <?php elseif ($page == 'edit_offer' && isset($_GET['id'])): ?>
            <?php
            // Fetch the offer details
            $offer_id = $_GET['id'];
            $offer = getOfferById($offer_id); // Ensure this function exists in offers_manager.php
            ?>
            <h2>Edit Offer</h2>
            <form action="offers_manager.php" method="POST">
                <input type="hidden" name="offer_id" value="<?= $offer['id'] ?>">
                <input type="hidden" name="role" value="<?= $_SESSION['role'] ?>">
                <input type="hidden" name="supplier_id" value="<?= $offer['supplier_id'] ?>">

                <!-- Quantity -->
                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" name="quantity" value="<?= $offer['quantity'] ?>" required>
                </div>

                <!-- Price -->
                <div class="mb-3">
                    <label for="price" class="form-label">Price</label>
                    <input type="number" step="0.01" class="form-control" name="price" value="<?= $offer['price'] ?>" required>
                </div>
                <!-- Submit Button -->
                <button type="submit" name="update_offer" class="btn btn-warning">Update Offer</button>
            </form>
        <?php elseif ($page == 'profile'): ?>
            <?php include 'profile.php' ?>
        <?php else: ?>
            <p>Page Not Found</p>


        <?php endif; ?>
    </div>
</body>
<script>
    function toggleProductFields() {
        const productId = document.getElementById('product_id').value;
        const newProductFields = document.getElementById('new_product_fields');

        if (productId === "") {
            // If "New Product" is selected, show the fields
            newProductFields.style.display = 'block';
        } else {
            // If an existing product is selected, hide the fields
            newProductFields.style.display = 'none';
        }
    }

    // Call the function on page load to set the initial state
    toggleProductFields();
</script>

</html>