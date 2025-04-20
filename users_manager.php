<?php
// users_manager.php
require 'db.php';


// Function to fetch users with search and filter
function getUsers()
{
    global $pdo;

    $search = $_GET['search'] ?? '';
    $filter = $_GET['filter'] ?? 'name'; // Default filter is 'name'

    try {
        $sql = "SELECT * FROM users WHERE $filter LIKE :search";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['search' => "%$search%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching users: " . $e->getMessage());
    }
}
function getUserById($user_id)
{
    global $pdo;
    try {
        $sql = "SELECT * FROM users WHERE id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching user: " . $e->getMessage());
    }
}
function getSuppliers() {
    global $pdo;
    
    try {
        $sql = "SELECT id, name FROM users WHERE role = 'supplier'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching suppliers: " . $e->getMessage());
    }
}
function getTotalUsers() {
    global $pdo;
    try {
        $sql = "SELECT COUNT(*) AS total_users FROM users";
        $stmt = $pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
    } catch (PDOException $e) {
        die("Error fetching total users: " . $e->getMessage());
    }
}

function getTotalAdmins() {
    global $pdo;
    try {
        $sql = "SELECT COUNT(*) AS total_admins FROM users WHERE role = 'admin'";
        $stmt = $pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total_admins'];
    } catch (PDOException $e) {
        die("Error fetching total admins: " . $e->getMessage());
    }
}
function getTotalClients() {
    global $pdo;
    try {
        $sql = "SELECT COUNT(*) AS total_clients FROM users WHERE role = 'client'";
        $stmt = $pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total_clients'];
    } catch (PDOException $e) {
        die("Error fetching total admins: " . $e->getMessage());
    }
}

function getTotalSuppliers() {
    global $pdo;
    try {
        $sql = "SELECT COUNT(*) AS total_suppliers FROM users WHERE role = 'supplier'";
        $stmt = $pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total_suppliers'];
    } catch (PDOException $e) {
        die("Error fetching total suppliers: " . $e->getMessage());
    }
}
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        // Add User
        $name = htmlspecialchars($_POST['name']);
        $email = htmlspecialchars($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = htmlspecialchars($_POST['role']);

        try {
            $sql = "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['name' => $name, 'email' => $email, 'password' => $password, 'role' => $role]);
            header("Location: admin_dashboard.php?page=users");
            exit();
        } catch (PDOException $e) {
            die("Error adding user: " . $e->getMessage());
        }
    } elseif (isset($_POST['update_user'])) {
        // Update User
        $user_id = $_POST['user_id'];
        $name = htmlspecialchars($_POST['name']);
        $email = htmlspecialchars($_POST['email']);
        $role = htmlspecialchars($_POST['role']);
        $status = htmlspecialchars($_POST['status']);
    
        try {
            // Check if password is provided
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $sql = "UPDATE users SET name = :name, email = :email, role = :role, status = :status, password = :password WHERE id = :user_id";
                $params = ['status' => $status, 'name' => $name, 'email' => $email, 'role' => $role, 'password' => $password, 'user_id' => $user_id];
            } else {
                $sql = "UPDATE users SET name = :name, email = :email, role = :role, status = :status WHERE id = :user_id";
                $params = ['status' => $status, 'name' => $name, 'email' => $email, 'role' => $role, 'user_id' => $user_id];
            }
    
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            header("Location: admin_dashboard.php?page=users");
            exit();
        } catch (PDOException $e) {
            die("Error updating user: " . $e->getMessage());
        }
    
    
    } elseif (isset($_POST['delete_user'])) {
        // Delete User
        $user_id = $_POST['user_id'];


        try {


            $sql = "DELETE FROM users WHERE id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            header("Location: admin_dashboard.php?page=users");
            exit();
        } catch (PDOException $e) {
            die("Error deleting user: " . $e->getMessage());
        }
    }
}

// Redirect if accessed directly
if (!isset($_GET['page'])) {
    header("Location: admin_dashboard.php?page=users");
    exit();
}
