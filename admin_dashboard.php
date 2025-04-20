<?php
// admin_dashboard.php
session_start();
require 'db.php';

// Redirect if not logged in or not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
require 'users_manager.php'; // Include user manager to handle fetching users
if (isset($_GET['export'])) {
    require 'products_manager';
    require 'orders_manager.php';
    require 'categories_manager.php';
    require 'offers_manager.php';
    $exportType = $_GET['export'];

    switch ($exportType) {
        case 'users':
            $data = getUsers();
            $header = ['id', 'name', 'prenom', 'phone', 'email', 'password', 'address', 'profile_pic', 'role', 'status'];
            header('Content-Disposition: attachment; filename=users_export.csv');
            break;

        case 'products':
            $data = getProducts();
            $header = ['id', 'name', 'description', 'category_id', 'price', 'quantity', 'image', 'supplier_id', 'status', 'created_at'];
            header('Content-Disposition: attachment; filename=products_export.csv');
            break;

        case 'categories':
            $data = getCategories();
            $header = ['id', 'name'];
            header('Content-Disposition: attachment; filename=categories_export.csv');
            break;

        case 'offers':
            $data = getAllOffers();
            $header = ['id', 'supplier_id', 'product_id', 'product_name', 'product_description', 'product_price', 'quantity', 'price', 'status', 'admin_note', 'admin_created', 'created_at'];
            header('Content-Disposition: attachment; filename=offers_export.csv');
            break;

        case 'orders':
            $data = getAllOrders();
            $header = ['id', 'user_id', 'total_price', 'status', 'created_at'];
            header('Content-Disposition: attachment; filename=orders_export.csv');
            break;

        default:
            die("Invalid export type.");
    }
    // CSV Export Logic
    header('Content-Type: text/csv');
    $output = fopen('php://output', 'w');
    // Add headers
    if (!empty($data)) {
        fputcsv($output, $header);
        // Add data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
}
// Include necessary manager files based on the page
if ($page == 'users' || $page == 'edit_user') {
    require 'orders_manager.php';

    $users = getUsers(); // Fetch users using the function
} elseif ($page == 'categories') {
    require 'categories_manager.php'; // Include category manager to handle fetching categories
    $categories = getCategories(); // Fetch categories using the function
} elseif ($page == 'products' || $page == 'add_product' || $page == 'update_product') {
    require 'categories_manager.php'; // Include category manager to handle fetching categories
    $categories = getCategories(); // Fetch categories using the function
    require 'products_manager.php'; // Include product manager to handle fetching products
    if (!empty($_GET['id'])) {
        $product = getProductById($_GET['id']); // Fetch product by Id using the function
    }
    $products = getProducts(); // Fetch products using the function
    $suppliers = getSuppliers(); // Fetch Suppliers using the function
} elseif ($page == 'offers' || $page == 'add_offer' || $page == 'edit_offer') {
    require 'offers_manager.php';
    $offers = getAllOffers();
    $suppliers = getSuppliers(); // Fetch Suppliers using the function
    require 'products_manager.php'; // Include product manager to handle fetching products
    $products = getProducts(); // Fetch products using the function
    require 'categories_manager.php'; // Include category manager to handle fetching categories
    $categories = getCategories(); // Fetch categories using the function
} elseif ($page == 'home') {
    require 'categories_manager.php';
    require 'products_manager.php';
    require 'orders_manager.php';

    $TotalOrders = getTotalOrders();
    $TopProducts = getTopProducts();
    $total_users = getTotalUsers();
    $total_admins = getTotalAdmins();
    $total_clients = getTotalClients();
    $total_suppliers = getTotalSuppliers();
    $total_categories = getTotalCategories();
    $total_products = getTotalProducts();
    $total_active_products = getTotalActiveProducts();
    require 'offers_manager.php';
    // Fetch statistics using functions from statistics_manager.php
    $accepted_offers = getAcceptedOffersCount();
    $rejected_offers = getRejectedOffersCount();
    $offers_per_supplier = getOffersPerSupplier();
    $products_per_supplier = getProductsPerSupplier();
} elseif ($page == 'orders' || $page == 'view_order') {
    require 'orders_manager.php'; // Include orders manager to handle fetching orders
    $orders = getAllOrders(); // Fetch all orders
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    <style>
        body {
            display: flex;
            height: 100vh;
        }

        .sidebar {
            width: 20%;
            background-color: rgb(12, 69, 192);
            color: #fff;
            height: 100%;
            /* Full height */
            overflow-y: auto;
            /* Enable scrolling if content overflows */
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: rgb(193, 191, 201);
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
        <h4 class="text-center py-3"><?= $user['name'] . '' . $user['prenom'] ?></h4>
        <h1 class="text-center py-3">Admin Dashboard</h1>
        <a href="admin_dashboard.php?page=home" class="<?= $page == 'home' ? 'active' : ''; ?>">
            <i class="bi bi-house"></i> Home
        </a>
        <a href="admin_dashboard.php?page=users" class="<?= $page == 'users' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i> Users Manager
        </a>
        <a href="admin_dashboard.php?page=categories" class="<?= $page == 'categories' ? 'active' : ''; ?>">
            <i class="bi bi-list-task"></i> Categories
        </a>
        <a href="admin_dashboard.php?page=products" class="<?= $page == 'products' ? 'active' : ''; ?>">
            <i class="bi bi-box"></i> Products
        </a>
        <a href="admin_dashboard.php?page=offers" class="<?= $page == 'offers' ? 'active' : ''; ?>">
            <i class="bi bi-tags"></i> Offers
        </a>
        <a href="admin_dashboard.php?page=orders" class="<?= $page == 'orders' ? 'active' : ''; ?>">
            <i class="bi bi-cart"></i> Orders
        </a>
        <a href="admin_dashboard.php?page=profile" class="<?= $page == 'profile' ? 'active' : ''; ?>">
            <i class="bi bi-person"></i> Profile
        </a>
        <a href="logout.php">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>

    <div class="content">
        <?php if ($page == 'home'): ?>
            <h2>Welcome to the Admin Dashboard</h2>
            <p>Use the sidebar to manage users, categories, products, and offers.</p>

            <div class="container-fluid">
                <div class="row mt-4">
                    <div class="col-md-3 col-sm-3 mb-3">
                        <div class="card">
                            <div class="card-header">Total Users</div>
                            <div class="card-body">
                                <h5 class="card-title"><?= $total_users ?></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-3 mb-3">
                        <div class="card">
                            <div class="card-header">Total Admins</div>
                            <div class="card-body">
                                <h5 class="card-title"><?= $total_admins ?></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-3 mb-3">
                        <div class="card">
                            <div class="card-header">Total Suppliers</div>
                            <div class="card-body">
                                <h5 class="card-title"><?= $total_suppliers ?></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-3 mb-3">
                        <div class="card">
                            <div class="card-header">Total Clients</div>
                            <div class="card-body">
                                <h5 class="card-title"><?= $total_clients ?></h5>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-3 col-sm-3 mb-3">
                        <div class="card">
                            <div class="card-header">Total Categories</div>
                            <div class="card-body">
                                <h5 class="card-title"><?= $total_categories ?></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-3 mb-3">
                        <div class="card">
                            <div class="card-header">Total Products</div>
                            <div class="card-body">
                                <h5 class="card-title"><?= $total_products ?></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-3 mb-3">
                        <div class="card">
                            <div class="card-header">Total Active Products</div>
                            <div class="card-body">
                                <h5 class="card-title"><?= $total_active_products ?></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-3 mb-3">
                        <div class="card">
                            <div class="card-header">Total Orders</div>
                            <div class="card-body">
                                <h5 class="card-title"><?= $TotalOrders ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">

                    <div class="col-md-8 col-sm-12 mb-4">
                        <div class="card">
                            <div class="card-header">Top 5 Products</div>
                            <div class="card-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product Name</th>
                                            <th>Total Quantity Ordered</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($TopProducts as $product): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($product['name']) ?></td>
                                                <td><?= htmlspecialchars($product['total_quantity']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($page == 'users'): ?>
            <!-- Users List -->
            <h2>Users Manager</h2>
            <div class="col-12">
                <div class="row">
                    <div class="col-6">
                        <!-- Search and Filter Bar -->
                        <div class="search-filter">
                            <form action="admin_dashboard.php" method="GET" class="d-flex gap-2">
                                <input type="hidden" name="page" value="users">
                                <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" class="form-control">
                                <select name="filter" class="form-control">
                                    <option value="name" <?= ($_GET['filter'] ?? '') == 'name' ? 'selected' : '' ?>>Name</option>
                                    <option value="email" <?= ($_GET['filter'] ?? '') == 'email' ? 'selected' : '' ?>>Email</option>
                                    <option value="role" <?= ($_GET['filter'] ?? '') == 'role' ? 'selected' : '' ?>>Role</option>
                                </select>
                                <button type="submit" class="btn btn-primary">Search</button>
                            </form>
                        </div>
                    </div>

                    <div class="col-6 text-end">
                        <a href="admin_dashboard.php?page=add_user" class="btn btn-success mb-3">
                            <i class="bi bi-plus-circle"></i> Add User
                        </a>

                        <a href="admin_dashboard.php?export=users" class="btn btn-secondary mb-3">
                            <i class="bi bi-download"></i> Export Users
                        </a>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['role']) ?></td>
                                <td><?= htmlspecialchars($user['status']) ?></td>
                                <td>
                                    <a href="admin_dashboard.php?page=edit_user&id=<?= $user['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <form action="users_manager.php" method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user? This will also delete all related products, orders, offers, and cart items.?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($page == 'add_user'): ?>
            <!-- Add User Form -->
            <h2>Add User</h2>
            <form action="users_manager.php" method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-control" name="role" required>
                        <option value="client">Client</option>
                        <option value="supplier">Supplier</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" name="add_user" class="btn btn-success">Add User</button>
            </form>

        <?php elseif ($page == 'edit_user' && isset($_GET['id'])): ?>
            <!-- Edit User Form -->
            <?php $user_id = $_GET['id'];
            $user = getUserById($user_id); ?>
            <h2>Edit User</h2>
            <form action="users_manager.php" method="POST">
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password (leave blank to keep current password)</label>
                    <input type="password" class="form-control" name="password">
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-control" name="role" required>
                        <option value="client" <?= $user['role'] == 'client' ? 'selected' : '' ?>>Client</option>
                        <option value="supplier" <?= $user['role'] == 'supplier' ? 'selected' : '' ?>>Supplier</option>
                        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control" name="status" required>
                        <option value="active" <?= $user['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $user['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" name="update_user" class="btn btn-warning">Update User</button>
            </form>

        <?php elseif ($page == 'categories'): ?>
            <!-- Categories List -->
            <h2>Categories Manager</h2>
            <div class="col-12">
                <div class="row">
                    <div class="col-6">
                        <!-- Search Bar -->
                        <div class="search-filter">
                            <form action="admin_dashboard.php" method="GET" class="d-flex gap-2">
                                <input type="hidden" name="page" value="categories">
                                <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" class="form-control">
                                <button type="submit" class="btn btn-primary">Search</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-6 text-end">
                        <a href="admin_dashboard.php?page=add_category" class="btn btn-success mb-3">
                            <i class="bi bi-plus-circle"></i> Add Category
                        </a>
                        <a href="export_functions.php?export=categories" class="btn btn-secondary mb-3">
                            <i class="bi bi-download"></i> Export Categories
                        </a>
                    </div>
                </div>
            </div>

            <!-- Categories Table -->
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="3" class="text-center">No categories found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?= htmlspecialchars($category['id']) ?></td>
                                <td><?= htmlspecialchars($category['name']) ?></td>
                                <td>
                                    <a href="admin_dashboard.php?page=edit_category&id=<?= $category['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <form action="categories_manager.php" method="POST" class="d-inline">
                                        <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                        <button type="submit" name="delete_category" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure ,this will delete everything related to this category?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php elseif ($page == 'add_category'): ?>
            <h2>Add Category</h2>
            <form action="categories_manager.php" method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">Category Name</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <button type="submit" name="add_category" class="btn btn-success">Add Category</button>
            </form>
        <?php elseif ($page == 'edit_category' && isset($_GET['id'])): ?>
            <!-- Edit Category Form -->
            <?php
            $category_id = $_GET['id'];
            $sql = "SELECT * FROM categories WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $category_id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
            <h2>Edit Category</h2>
            <form action="categories_manager.php" method="POST">
                <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                <div class="mb-3">
                    <label for="name" class="form-label">Category Name</label>
                    <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($category['name']) ?>" required>
                </div>
                <button type="submit" name="update_category" class="btn btn-warning">Update Category</button>
            </form>
        <?php elseif ($page == 'products'): ?>
            <!-- Products List -->
            <h2>Products Manager</h2>
            <div class="col-12">
                <div class="row">
                    <div class="col-6">
                        <!-- Search Bar -->
                        <div class="search-filter">
                            <form action="admin_dashboard.php" method="GET" class="d-flex gap-2">
                                <input type="hidden" name="page" value="products">
                                <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" class="form-control">
                                <button type="submit" class="btn btn-primary">Search</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-6 text-end">
                        <a href="admin_dashboard.php?page=add_product" class="btn btn-success mb-3">
                            <i class="bi bi-plus-circle"></i> Add Product
                        </a>
                        <a href="export_functions.php?export=products" class="btn btn-secondary mb-3">
                            <i class="bi bi-download"></i> Export Products
                        </a>
                    </div>
                </div>
            </div>

            <!-- Products Table -->
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>image</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No products found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['id']) ?></td>
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
                                <td><?= htmlspecialchars($product['supplier_name']) ?></td>
                                <td><?= htmlspecialchars($product['status']) ?></td>
                                <td>
                                    <form action="products_manager.php" method="POST" class="d-inline">
                                        <a href="admin_dashboard.php?page=update_product&id=<?= $product['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <button type="submit" name="delete_product" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php elseif ($page == 'update_product' && isset($_GET['id'])): ?>
            <!-- Edit Product Form -->
            <h2>Edit Product</h2>
            <form action="products_manager.php" method="POST" enctype="multipart/form-data">

                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" name="description" required><?= htmlspecialchars($product['description']) ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-control" name="category_id" required>

                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= $category['id'] == $product['category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="price" class="form-label">Price</label>
                    <input type="number" step="0.01" class="form-control" name="price" value="<?= htmlspecialchars($product['price']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" name="quantity" value="<?= htmlspecialchars($product['quantity']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">Product Image</label>
                    <input type="file" class="form-control" name="image" accept="image/*">
                    <input type="hidden" name="existing_image" value="<?= htmlspecialchars($product['image']) ?>">
                    <?php if (!empty($product['image'])): ?>
                        <img src="<?= $product['image'] ?>" alt="Product Image" style="width: 100px; height: 100px; margin-top: 10px;">
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label for="supplier_id" class="form-label">Supplier</label>
                    <select class="form-control" name="supplier_id" required>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?= $supplier['id'] ?>" <?= $supplier['id'] == $product['supplier_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($supplier['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control" name="status" required>
                        <option value="active" <?= $product['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="out_of_stock" <?= $product['status'] == 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                    </select>
                </div>
                <button type="submit" name="update_product" class="btn btn-warning">Update Product</button>
            </form>
        <?php elseif ($page == 'add_product'): ?>
            <h2>Add Product</h2>
            <form action="products_manager.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" name="description" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-control" name="category_id" required>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">No categories found</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="price" class="form-label">Price</label>
                    <input type="number" step="0.01" class="form-control" name="price" required>
                </div>
                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" name="quantity" required>
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">Product Image</label>
                    <input type="file" class="form-control" name="image" accept="image/*">
                </div>
                <div class="mb-3">
                    <label for="supplier_id" class="form-label">Supplier</label>
                    <select class="form-control" name="supplier_id" required>
                        <?php if (!empty($suppliers)): ?>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">No supplier found</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control" name="status" required>
                        <option value="active">Active</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>
                </div>
                <button type="submit" name="add_product" class="btn btn-success">Add Product</button>
            </form>

        <?php elseif ($page == 'profile'): ?>
            <?php include 'profile.php' ?>
        <?php elseif ($page == 'offers'): ?>
            <div class="row">
                <div class="col-6 ">
                    <h2>Manage Offers</h2>
                </div>

                <div class="col-6 text-end">
                    <a href="admin_dashboard.php?page=add_offer" class="btn btn-success mb-3">
                        <i class="bi bi-plus-circle"></i> Add Offer
                    </a>
                    <a href="export_functions.php?export=offers" class="btn btn-secondary mb-3">
                        <i class="bi bi-download"></i> Export Offers
                    </a>
                </div>
            </div>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Supplier</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <?php foreach ($offers as $offer): ?>
                    <tr>
                        <td><?= $offer['id'] ?></td>
                        <?php $supplier = getUserById($offer['supplier_id']); ?>
                        <td><?= $supplier['name']; ?></td>
                        <td><?= $offer['product_name'] ?? 'N/A' ?></td>
                        <td><?= $offer['quantity'] ?></td>
                        <td><?= $offer['price'] ?></td>
                        <td><?= $offer['status'] ?></td>
                        <td>
                            <?php if ($offer['status'] === 'pending' && $offer['admin_created'] == true): ?>
                                <!-- Edit Button -->
                                <a href="admin_dashboard.php?page=edit_offer&id=<?= $offer['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <!-- Delete Button -->
                                <form action="offers_manager.php" method="POST" class="d-inline">
                                    <input type="hidden" name="offer_id" value="<?= $offer['id'] ?>">
                                    <input type="hidden" name="current_user_role" value="<?= $_SESSION['role'] ?>">
                                    <input type="hidden" name="supplier_id" value="<?= $offer['supplier_id'] ?>">
                                    <button type="submit" name="delete_offer" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this offer?')">Delete</button>
                                </form>
                            <?php elseif ($offer['status'] === 'pending' && $offer['admin_created'] == false): ?>
                                <!-- Accept/Reject Buttons -->
                                <input type="hidden" name="current_user_role" value="<?= $_SESSION['role'] ?>">
                                <a href="offers_manager.php?action=accept&id=<?= $offer['id'] ?>&role=<?= $_SESSION['role'] ?>&supplier_id=<?= $offer['supplier_id'] ?>" class="btn btn-success btn-sm">Accept</a>
                                <a href="offers_manager.php?action=reject&id=<?= $offer['id'] ?>&role=<?= $_SESSION['role'] ?>&supplier_id=<?= $offer['supplier_id'] ?>" class="btn btn-danger btn-sm">Reject</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($page == 'add_offer'): ?>
            <h2>Add Offer</h2>
            <form action="offers_manager.php" method="POST">
                <div class="mb-3">
                    <label for="supplier_id" class="form-label">Select Supplier</label>
                    <select class="form-control" name="supplier_id" required>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?= $supplier['id'] ?>"><?= $supplier['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="product_id" class="form-label">Select Product</label>
                    <select class="form-control" name="product_id" id="product_id" required onchange="toggleProductFields()">
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
        <?php elseif ($page == 'orders'): ?>
            <!-- Orders Page Content -->
            <h2>Orders Manager</h2>
            <div class="col-12">
                <div class="row">
                    <div class="col-6">
                        <!-- Search Bar -->
                        <div class="search-filter">
                            <form action="admin_dashboard.php" method="GET" class="d-flex gap-2">
                                <input type="hidden" name="page" value="orders">
                                <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" class="form-control">
                                <button type="submit" class="btn btn-primary">Search</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th>Order Date</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No orders found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= htmlspecialchars($order['id']) ?></td>
                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td><?= htmlspecialchars($order['order_date']) ?></td>
                                <td><?= htmlspecialchars($order['total_amount']) ?></td>
                                <td><?= htmlspecialchars($order['status']) ?></td>
                                <td>
                                    <a href="admin_dashboard.php?page=view_order&id=<?= $order['id'] ?>" class="btn btn-info btn-sm">View</a>
                                    <form action="orders_manager.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button type="submit" name="delete_order" class="btn btn-sm btn-danger">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php elseif ($page == 'view_order' && isset($_GET['id'])): ?>
            <!-- View Order Details -->
            <?php
            $order_id = $_GET['id'];
            $order = getOrderById($order_id); // Fetch order details
            $order_items = getOrderItems($order_id); // Fetch order items
            ?>
            <h2>Order Details</h2>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Order ID: <?= htmlspecialchars($order['id']) ?></h5>
                    <p class="card-text">Customer Name: <?= htmlspecialchars($order['customer_name']) ?></p>
                    <p class="card-text">Order Date: <?= htmlspecialchars($order['order_date']) ?></p>
                    <p class="card-text">Total Amount: <?= htmlspecialchars($order['total_amount']) ?></p>
                    <p class="card-text">Status: <?= htmlspecialchars($order['status']) ?></p>
                </div>
            </div>

            <h3 class="mt-4">Order Items</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td><?= htmlspecialchars($item['quantity']) ?></td>
                            <td><?= htmlspecialchars($item['price']) ?></td>
                            <td><?= htmlspecialchars($item['quantity'] * $item['price']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php else: ?>
            <p>Page Not Found</p>
        <?php endif; ?>
        <footer class="bg-light text-center text-lg-start mt-4">
            <div class="text-center p-3">
                &copy; <?php echo date('Y'); ?> MagaZini. All Rights Reserved.
            </div>
        </footer>
    </div>

</body>


<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>


</html>