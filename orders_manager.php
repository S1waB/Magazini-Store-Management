<?php
require 'db.php'; // Database connection

function createOrder($user_id)
{
    global $pdo;

    try {
        // Start a transaction
        $pdo->beginTransaction();

        // Fetch the user's cart items
        $sql = "SELECT ci.product_id, ci.quantity  AS requested_quantity, p.price, p.quantity AS available_quantity ,p.name AS name
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                JOIN carts c ON ci.cart_id = c.id
                WHERE c.user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cartItems)) {
            // Redirect with an error message if the cart is empty
            header("Location: index.php?page=orders&error=Your+cart+is+empty");
            exit();
        }

        // Calculate the total price of the order
        $totalPrice = 0;
        foreach ($cartItems as $item) {
            // Check if there is enough stock
            if ($item['available_quantity'] < $item['requested_quantity']) {
                // Redirect with an error message if stock is insufficient
                header("Location: index.php?page=orders&error=Insufficient+stock+for+product+" . $item['name']);
                exit();
            }
            $totalPrice += $item['price'] * $item['requested_quantity'];
        }

        // Create the order
        $sql = "INSERT INTO orders (user_id, total_price) VALUES (:user_id, :total_price)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id, 'total_price' => $totalPrice]);
        $order_id = $pdo->lastInsertId();

        // Add items to the order
        foreach ($cartItems as $item) {
            // Insert into order_items
            $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                    VALUES (:order_id, :product_id, :quantity, :price)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'order_id' => $order_id,
                'product_id' => $item['product_id'],
                'quantity' => $item['requested_quantity'],
                'price' => $item['price']
            ]);

            // Deduct stock
            $sql = "UPDATE products SET quantity = quantity - :quantity WHERE id = :product_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['quantity' => $item['requested_quantity'], 'product_id' => $item['product_id']]);
        }

        // Clear the user's cart
        $sql = "DELETE FROM cart_items WHERE cart_id = (SELECT id FROM carts WHERE user_id = :user_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);

        // Commit the transaction
        $pdo->commit();

        // Redirect to the orders page with a success message
        header("Location: index.php?page=orders&success=Order+created+successfully");
        exit();
    } catch (Exception $e) {
        // Rollback the transaction in case of an error
        $pdo->rollBack();
        // Redirect with an error message
        header("Location: index.php?page=orders&error=" . urlencode($e->getMessage()));
        exit();
    }
}

// Function to cancel an order
function cancelOrder($order_id)
{
    global $pdo;

    try {
        // Check if the order is still within the 2-hour window
        $sql = "SELECT created_at FROM orders WHERE id = :order_id AND status = 'pending'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['order_id' => $order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new Exception("Order cannot be canceled.");
        }

        $created_at = strtotime($order['created_at']);
        $current_time = time();
        $time_diff = $current_time - $created_at;

        if ($time_diff > 7200) { // 2 hours = 7200 seconds
            throw new Exception("Order cannot be canceled after 2 hours.");
        }

        // Update the order status to 'cancelled'
        $sql = "UPDATE orders SET status = 'cancelled' WHERE id = :order_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['order_id' => $order_id]);

        // Restore product quantity
        $sql = "SELECT product_id, quantity FROM order_items WHERE order_id = :order_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['order_id' => $order_id]);
        $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($orderItems as $item) {
            $sql = "UPDATE products SET quantity = quantity + :quantity WHERE id = :product_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['quantity' => $item['quantity'], 'product_id' => $item['product_id']]);
        }
    } catch (PDOException $e) {
        die("Error canceling order: " . $e->getMessage());
    }
}

function reorder($order_id, $user_id)
{
    global $pdo;

    try {
        // Start a transaction
        $pdo->beginTransaction();

        // Fetch the canceled order items
        $sql = "SELECT product_id, quantity, price 
                FROM order_items 
                WHERE order_id = :order_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['order_id' => $order_id]);
        $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($orderItems)) {
            throw new Exception("No items found in the canceled order.");
        }

        // Calculate the total price of the new order
        $totalPrice = 0;
        foreach ($orderItems as $item) {
            $totalPrice += $item['price'] * $item['quantity'];
        }

        // Create a new order
        $sql = "INSERT INTO orders (user_id, total_price) VALUES (:user_id, :total_price)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id, 'total_price' => $totalPrice]);
        $new_order_id = $pdo->lastInsertId();

        // Add items to the new order
        foreach ($orderItems as $item) {
            // Check if there is enough stock
            $sql = "SELECT quantity FROM products WHERE id = :product_id FOR UPDATE";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['product_id' => $item['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product['quantity'] < $item['quantity']) {
                throw new Exception("Insufficient stock for product ID: " . $item['product_id']);
            }

            // Insert into order_items
            $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                    VALUES (:order_id, :product_id, :quantity, :price)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'order_id' => $new_order_id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ]);

            // Deduct stock
            $sql = "UPDATE products SET quantity = quantity - :quantity WHERE id = :product_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['quantity' => $item['quantity'], 'product_id' => $item['product_id']]);
        }

        // Commit the transaction
        $pdo->commit();

        return $new_order_id;
    } catch (Exception $e) {
        // Rollback the transaction in case of an error
        $pdo->rollBack();
        die("Error reordering: " . $e->getMessage());
    }
}
function getUserOrders($user_id)
{
    global $pdo;

    try {
        $sql = "SELECT o.id, o.total_price, o.status, o.created_at 
                FROM orders o
                WHERE o.user_id = :user_id
                ORDER BY o.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching orders: " . $e->getMessage());
    }
}
// Function to get total number of orders
function getTotalOrders()
{
    global $pdo;
    $sql = "SELECT COUNT(*) as total_orders FROM orders";
    $stmt = $pdo->query($sql);
    return $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];
}

// Function to get top 5 products by number of orders
function getTopProducts()
{
    global $pdo;
    $sql = "SELECT p.name, SUM(oi.quantity) as total_quantity 
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            GROUP BY p.id
            ORDER BY total_quantity DESC
            LIMIT 5";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getOrderById($order_id)
{
    global $pdo;
    $sql = "SELECT o.id, u.name AS customer_name, o.created_at AS order_date, o.total_price AS total_amount, o.status 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.id = :order_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['order_id' => $order_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getOrderItems($order_id)
{
    global $pdo;
    $sql = "SELECT p.name AS product_name, oi.quantity, oi.price 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = :order_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['order_id' => $order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function deleteOrder($order_id)
{
    global $pdo;
    $sql = "DELETE FROM orders WHERE id = :order_id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute(['order_id' => $order_id]);
}
function getAllOrders()
{
    global $pdo;
    $search = $_GET['search'] ?? '';

    // Base SQL query
    $sql = "SELECT o.id, u.name AS customer_name, o.created_at AS order_date, o.total_price AS total_amount, o.status 
            FROM orders o 
            JOIN users u ON o.user_id = u.id";

    // Add search condition if a search term is provided
    if (!empty($search)) {
        $sql .= " WHERE u.name LIKE :search OR o.id LIKE :search";
    }

    $stmt = $pdo->prepare($sql);

    // Bind the search parameter if it exists
    if (!empty($search)) {
        $stmt->execute(['search' => "%$search%"]);
    } else {
        $stmt->execute();
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle delete order request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_order'])) {
    $order_id = $_POST['order_id'];
    if (deleteOrder($order_id)) {
        header("Location: admin_dashboard.php?page=orders");
        exit();
    } else {
        echo "Error deleting order.";
    }
}
// Handle order creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_order'])) {
    $order_id = createOrder($_POST['user_id']);
    header("Location: index.php?page=orders");
    exit();
}

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_order'])) {
    $order_id = $_POST['order_id'];
    cancelOrder($order_id);
    header("Location: index.php?page=orders");
    exit();
}

// Handle reordering
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reorder'])) {
    $order_id = $_POST['order_id'];
    reorder($order_id, $_POST['user_id']);
    header("Location: index.php?page=orders");
    exit();
}
