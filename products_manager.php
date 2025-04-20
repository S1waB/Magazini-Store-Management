<?php
// products_manager.php
require 'db.php';

// Function to fetch products with search
function getProducts()
{
    global $pdo;

    $search = $_GET['search'] ?? '';
    $filter = $_GET['filter'] ?? 'name'; // Default filter is 'name'
    try {
        $sql = "SELECT p.*, c.name AS category_name, u.name AS supplier_name 
                FROM products p
                JOIN categories c ON p.category_id = c.id
                JOIN users u ON p.supplier_id = u.id
                WHERE p.name LIKE :search OR c.name LIKE :search OR u.name LIKE :search AND c.name LIKE :filter" ;
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['search' => "%$search%", 'filter' => $filter]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching products: " . $e->getMessage());
    }
}
// Function to fetch a single product by ID
function getProductById($product_id)
{
    global $pdo;

    try {
        $sql = "SELECT * FROM products WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $product_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching product: " . $e->getMessage());
    }
}
function getTotalProducts()
{
    global $pdo;
    try {
        $sql = "SELECT COUNT(*) AS total_products FROM products";
        $stmt = $pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];
    } catch (PDOException $e) {
        die("Error fetching total products: " . $e->getMessage());
    }
}
// Function to get the count of products per supplier
function getProductsPerSupplier()
{
    global $pdo;
    try {
        $sql = "SELECT u.name AS supplier_name, COUNT(p.id) AS products_count 
                FROM products p 
                JOIN users u ON p.supplier_id = u.id 
                GROUP BY u.name";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching products per supplier: " . $e->getMessage());
    }
}

function getTotalActiveProducts()
{
    global $pdo;
    try {
        $sql = "SELECT COUNT(*) AS total_active_products FROM products WHERE status = 'active'";
        $stmt = $pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total_active_products'];
    } catch (PDOException $e) {
        die("Error fetching total active products: " . $e->getMessage());
    }
}


function getProductsByCategory($search = '', $category_filter = '') {
    global $pdo;

    try {
        // Base query
        $sql = "SELECT p.*, c.name AS category_name 
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE 1=1"; // Start with a condition that is always true

        // Add search filter if search term is not empty
        if (!empty($search)) {
            $sql .= " AND (p.name LIKE :search OR c.name LIKE :search)";
        }

        // Add category filter if category is selected
        if (!empty($category_filter)) {
            $sql .= " AND p.category_id = :category_filter";
        }

        // Prepare the SQL statement
        $stmt = $pdo->prepare($sql);

        // Bind parameters
        $params = [];
        if (!empty($search)) {
            $params['search'] = "%$search%";
        }
        if (!empty($category_filter)) {
            $params['category_filter'] = $category_filter;
        }

        // Execute the query
        $stmt->execute($params);

        // Return the results
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching products: " . $e->getMessage());
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        // Add Product
        $name = htmlspecialchars($_POST['name']);
        $description = htmlspecialchars($_POST['description']);
        $category_id = intval($_POST['category_id']);
        $price = floatval($_POST['price']);
        $quantity = intval($_POST['quantity']);
        $supplier_id = intval($_POST['supplier_id']);
        $status = htmlspecialchars($_POST['status']);

        // Handle image upload
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true); // Create the directory if it doesn't exist
            }
            $target_file = $target_dir . basename($_FILES["image"]["name"]);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Check if the file is an image
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check !== false) {
                // Allow certain file formats
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($imageFileType, $allowed_types)) {
                    // Generate a unique filename to avoid conflicts
                    $unique_filename = uniqid() . '.' . $imageFileType;
                    $target_file = $target_dir . $unique_filename;

                    // Try to move the uploaded file to the target directory
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                        $image = $target_file; // Save the file path
                    } else {
                        die("Sorry, there was an error uploading your file.");
                    }
                } else {
                    die("Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
                }
            } else {
                die("File is not an image.");
            }
        }

        try {
            $sql = "INSERT INTO products (name, description, category_id, price, quantity, image, supplier_id, status) 
                    VALUES (:name, :description, :category_id, :price, :quantity, :image, :supplier_id, :status)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'name' => $name,
                'description' => $description,
                'category_id' => $category_id,
                'price' => $price,
                'quantity' => $quantity,
                'image' => $image,
                'supplier_id' => $supplier_id,
                'status' => $status
            ]);
            header("Location: admin_dashboard.php?page=products");
            exit();
        } catch (PDOException $e) {
            die("Error adding product: " . $e->getMessage());
        }
    } elseif (isset($_POST['update_product'])) {
        // Update Product
        $product_id = $_POST['product_id'];
        $name = htmlspecialchars($_POST['name']);
        $description = htmlspecialchars($_POST['description']);
        $category_id = intval($_POST['category_id']);
        $price = floatval($_POST['price']);
        $quantity = intval($_POST['quantity']);
        $supplier_id = intval($_POST['supplier_id']);
        $status = htmlspecialchars($_POST['status']);

        // Handle image upload
        $image = $_POST['existing_image']; // Default to existing image
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true); // Create the directory if it doesn't exist
            }
            $target_file = $target_dir . basename($_FILES["image"]["name"]);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Check if the file is an image
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check !== false) {
                // Allow certain file formats
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($imageFileType, $allowed_types)) {
                    // Generate a unique filename to avoid conflicts
                    $unique_filename = uniqid() . '.' . $imageFileType;
                    $target_file = $target_dir . $unique_filename;

                    // Try to move the uploaded file to the target directory
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                        $image = $target_file; // Save the file path
                    } else {
                        die("Sorry, there was an error uploading your file.");
                    }
                } else {
                    die("Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
                }
            } else {
                die("File is not an image.");
            }
        }

        try {
            $sql = "UPDATE products 
                    SET name = :name, description = :description, category_id = :category_id, price = :price, 
                        quantity = :quantity, image = :image, supplier_id = :supplier_id, status = :status 
                    WHERE id = :product_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'name' => $name,
                'description' => $description,
                'category_id' => $category_id,
                'price' => $price,
                'quantity' => $quantity,
                'image' => $image,
                'supplier_id' => $supplier_id,
                'status' => $status,
                'product_id' => $product_id
            ]);
            header("Location: admin_dashboard.php?page=products");
            exit();
        } catch (PDOException $e) {
            die("Error updating product: " . $e->getMessage());
        }
    } elseif (isset($_POST['delete_product'])) {
        // Delete Product
        $product_id = $_POST['product_id'];

        try {
            $sql = "DELETE FROM products WHERE id = :product_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['product_id' => $product_id]);
            header("Location: admin_dashboard.php?page=products");
            exit();
        } catch (PDOException $e) {
            die("Error deleting product: " . $e->getMessage());
        }
    }
}

// Redirect if accessed directly
if (!isset($_GET['page'])) {
    header("Location: admin_dashboard.php?page=products");
    exit();
}
