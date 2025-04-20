<?php
// cart_manager.php
require 'db.php'; // Ensure the database connection is available

// Function to add a product to the cart
function addToCart($user_id, $product_id, $quantity) {
    global $pdo;

    try {
        // Check if the user already has a cart
        $sql = "SELECT id FROM carts WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cart) {
            // Create a new cart for the user if it doesn't exist
            $sql = "INSERT INTO carts (user_id) VALUES (:user_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            $cart_id = $pdo->lastInsertId();
        } else {
            $cart_id = $cart['id'];
        }

        // Check if the product already exists in the cart
        $sql = "SELECT * FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['cart_id' => $cart_id, 'product_id' => $product_id]);
        $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingItem) {
            // Update the quantity if the product already exists in the cart
            $newQuantity = $existingItem['quantity'] + $quantity;
            $sql = "UPDATE cart_items SET quantity = :quantity WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['quantity' => $newQuantity, 'id' => $existingItem['id']]);
        } else {
            // Add the product to the cart if it doesn't exist
            $sql = "INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (:cart_id, :product_id, :quantity)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['cart_id' => $cart_id, 'product_id' => $product_id, 'quantity' => $quantity]);
        }
    } catch (PDOException $e) {
        die("Error adding to cart: " . $e->getMessage());
    }
}

// Function to update the quantity of a product in the cart
function updateCartQuantity($cart_item_id, $quantity) {
    global $pdo;

    try {
        $sql = "UPDATE cart_items SET quantity = :quantity WHERE id = :cart_item_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['quantity' => $quantity, 'cart_item_id' => $cart_item_id]);
    } catch (PDOException $e) {
        die("Error updating cart: " . $e->getMessage());
    }
}

// Function to remove a product from the cart
function removeFromCart($cart_item_id) {
    global $pdo;

    try {
        $sql = "DELETE FROM cart_items WHERE id = :cart_item_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['cart_item_id' => $cart_item_id]);
    } catch (PDOException $e) {
        die("Error removing from cart: " . $e->getMessage());
    }
}

function clearCart($user_id) {
    global $pdo;

    try {
        // Find the user's cart
        $sql = "SELECT id FROM carts WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cart) {
            // Delete all items in the cart
            $sql = "DELETE FROM cart_items WHERE cart_id = :cart_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['cart_id' => $cart['id']]);
        }
    } catch (PDOException $e) {
        die("Error clearing cart: " . $e->getMessage());
    }
}

// Function to fetch the cart contents for a user
function getCartContents($user_id) {
    global $pdo;

    try {
        $sql = "SELECT ci.id AS cart_item_id, p.id AS product_id, p.name, p.price, p.image, ci.quantity 
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                JOIN carts c ON ci.cart_id = c.id
                WHERE c.user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching cart contents: " . $e->getMessage());
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_to_cart'])) {
        // Add product to cart
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        addToCart($_POST['user_id'], $product_id, $quantity);
        header("Location: index.php?page=products");
    exit();
    } elseif (isset($_POST['update_cart'])) {
        // Update cart quantity
        $cart_item_id = $_POST['cart_item_id'];
        $quantity = $_POST['quantity'];
        updateCartQuantity($cart_item_id, $quantity);
        header("Location: index.php?page=cart");
        exit();
    } elseif (isset($_POST['remove_from_cart'])) {
        // Remove product from cart
        $cart_item_id = $_POST['cart_item_id'];
        removeFromCart($cart_item_id);
        header("Location: index.php?page=cart");
        exit();
    } elseif (isset($_POST['clear_cart'])) {
        // Clear the cart
        clearCart($_POST['user_id']);
        header("Location: index.php?page=cart");
        exit();
    }
}
?>