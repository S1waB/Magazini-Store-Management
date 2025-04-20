<?php
// offers_manager.php
require 'db.php';

// // Redirect if not logged in
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }

// Function to fetch all offers (for admins)
function getAllOffers()
{
    global $pdo;
    try {
        $sql = "SELECT o.*, u.name AS supplier_name, p.name AS product_name 
                FROM offers o
                JOIN users u ON o.supplier_id = u.id
                LEFT JOIN products p ON o.product_id = p.id";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching offers: " . $e->getMessage());
    }
}

// Function to fetch an offer by ID
function getOfferById($offer_id)
{
    global $pdo;
    try {
        $sql = "SELECT * FROM offers WHERE id = :offer_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['offer_id' => $offer_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching offer: " . $e->getMessage());
    }
}
// Function to get the count of rejected offers
function getRejectedOffersCount()
{
    global $pdo;
    try {
        $sql = "SELECT COUNT(*) AS rejected_offers FROM offers WHERE status = 'rejected'";
        $stmt = $pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC)['rejected_offers'];
    } catch (PDOException $e) {
        die("Error fetching rejected offers count: " . $e->getMessage());
    }
}

// Function to get the count of offers per supplier
function getOffersPerSupplier()
{
    global $pdo;
    try {
        $sql = "SELECT u.name AS supplier_name, COUNT(o.id) AS offers_count 
                FROM offers o 
                JOIN users u ON o.supplier_id = u.id 
                GROUP BY u.name";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching offers per supplier: " . $e->getMessage());
    }
}
// Function to get the count of accepted offers
function getAcceptedOffersCount()
{
    global $pdo;
    try {
        $sql = "SELECT COUNT(*) AS accepted_offers FROM offers WHERE status = 'accepted'";
        $stmt = $pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC)['accepted_offers'];
    } catch (PDOException $e) {
        die("Error fetching accepted offers count: " . $e->getMessage());
    }
}




// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_offer'])) {
        // Add Offer
        $supplier_id = intval($_POST['supplier_id']);
        $product_id = !empty($_POST['product_id']) ? intval($_POST['product_id']) : null;
        $product_name = htmlspecialchars($_POST['product_name'] ?? '');
        $product_description = htmlspecialchars($_POST['product_description'] ?? '');
        $product_price = floatval($_POST['product_price'] ?? 0);
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $quantity = intval($_POST['quantity']);
        $price = floatval($_POST['price']);
        $admin_created = ($_POST['role'] == 'admin');

        try {
            // Insert the offer
            $sql = "INSERT INTO offers (supplier_id, product_id, product_name, product_description, product_price, quantity, price, admin_created) 
                    VALUES (:supplier_id, :product_id, :product_name, :product_description, :product_price, :quantity, :price, :admin_created)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'supplier_id' => $supplier_id,
                'product_id' => $product_id,
                'product_name' => $product_name,
                'product_description' => $product_description,
                'product_price' => $product_price,
                'quantity' => $quantity,
                'price' => $price,
                'admin_created' => $admin_created
            ]);

            if ($_POST['role'] == 'admin') {
                header("Location: admin_dashboard.php?page=offers");
                exit();
            } elseif ($_POST['role'] == 'supplier') {
                header("Location: suppliers_dashboard.php?page=offers");
                exit();
            }
        } catch (PDOException $e) {
            die("Error adding offer: " . $e->getMessage());
        }
    } elseif (isset($_POST['update_offer'])) {
        $offer_id = intval($_POST['offer_id']);
        $current_user_role = $_POST['role']; // 'admin' or 'supplier'
        $supplier_id = $_POST['supplier_id']; // Supplier ID of the offer
        $quantity = intval($_POST['quantity']);
        $price = floatval($_POST['price']);


        try {
            // Admins can edit any offer
            if ($current_user_role === 'admin') {
                $sql = "UPDATE offers SET quantity = :quantity, price = :price WHERE id = :offer_id";
            }
            // Suppliers can only edit their own offers
            elseif ($current_user_role === 'supplier') {
                $sql = "UPDATE offers SET quantity = :quantity, price = :price WHERE id = :offer_id AND supplier_id = :supplier_id";
            } else {
                die("Unauthorized action.");
            }

            $stmt = $pdo->prepare($sql);
            $params = [
                'quantity' => $quantity,
                'price' => $price,
                'offer_id' => $offer_id
            ];
            if ($current_user_role === 'supplier') {
                $params['supplier_id'] = $supplier_id;
            }
            $stmt->execute($params);

            // Redirect based on role
            if ($current_user_role === 'admin') {
                header("Location: admin_dashboard.php?page=offers");
            } elseif ($current_user_role === 'supplier') {
                header("Location: suppliers_dashboard.php?page=offers");
            }
            exit();
        } catch (PDOException $e) {
            die("Error updating offer: " . $e->getMessage());
        }
    } elseif (isset($_POST['delete_offer'])) {
        // Delete Offer
        $offer_id = intval($_POST['offer_id']);
        $current_user_role = $_POST['current_user_role'];
        try {
            $sql = "DELETE FROM offers WHERE id = :offer_id  AND (status = 'pending' OR status = 'rejected')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['offer_id' => $offer_id]);
            if ($current_user_role == 'admin') {
                header("Location: admin_dashboard.php?page=offers");
                exit();
            } elseif ($current_user_role == 'supplier') {
                header("Location: suppliers_dashboard.php?page=offers");
                exit();
            }
        } catch (PDOException $e) {
            die("Error deleting offer: " . $e->getMessage());
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['action']) && isset($_GET['id'])) {

        $action = $_GET['action']; // 'accept' or 'reject'
        $offer_id = intval($_GET['id']); // Offer ID
        $current_user_role = $_GET['role']; // 'admin' or 'supplier'

        try {
            // Validate the action
            if ($action !== 'accept' && $action !== 'reject') {
                die("Invalid action.");
            }

            // Determine the new status
            $status = ($action === 'accept') ? 'accepted' : 'rejected';
            $sql = "UPDATE offers SET status = :status WHERE id = :offer_id ";
            // Update the offer status
            $stmt = $pdo->prepare($sql);
            $params = [
                'status' => $status,
                'offer_id' => $offer_id
            ];
            $stmt->execute($params);

            // If the offer is accepted, update the product quantity or create a new product
            if ($action === 'accept') {
                // Fetch the offer details
                $sql = "SELECT * FROM offers WHERE id = :offer_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['offer_id' => $offer_id]);
                $offer = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($offer['product_id']) {
                    // Update the product quantity in the products table
                    $sql = "UPDATE products SET quantity = quantity + :quantity WHERE id = :product_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'quantity' => $offer['quantity'],
                        'product_id' => $offer['product_id']
                    ]);
                } else {
                    // If the offer is for a new product, create the product
                    $sql = "INSERT INTO products (name, description, price, category_id, quantity, supplier_id, status) 
                        VALUES (:name, :description, :price, :category_id, :quantity, :supplier_id, 'active')";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'name' => $offer['product_name'],
                        'description' => $offer['product_description'],
                        'price' => $offer['product_price'],
                        'category_id' => $offer['category_id'],
                        'quantity' => $offer['quantity'],
                        'supplier_id' => $offer['supplier_id']
                    ]);
                }
            }

            // Redirect based on the user's role
            if ($current_user_role === 'admin') {
                header("Location: admin_dashboard.php?page=offers");
                exit();
            } elseif ($current_user_role === 'supplier') {
                header("Location: suppliers_dashboard.php?page=offers");
                exit();
            }
        } catch (PDOException $e) {
            die("Error updating offer: " . $e->getMessage());
        }
    }
}
