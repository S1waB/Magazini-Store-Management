<?php
// categories_manager.php
require 'db.php';

// Function to fetch categories with search
function getCategories() {
    global $pdo;
    
    $search = $_GET['search'] ?? '';

    try {
        $sql = "SELECT * FROM categories WHERE name LIKE :search";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['search' => "%$search%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching categories: " . $e->getMessage());
    }
}
function getTotalCategories() {
    global $pdo;
    try {
        $sql = "SELECT COUNT(*) AS total_categories FROM categories";
        $stmt = $pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total_categories'];
    } catch (PDOException $e) {
        die("Error fetching total categories: " . $e->getMessage());
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        // Add Category
        $name = htmlspecialchars($_POST['name']);

        $sql = "INSERT INTO categories (name) VALUES (:name)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['name' => $name]);

        header("Location: admin_dashboard.php?page=categories");
        exit();
    } elseif (isset($_POST['update_category'])) {
        // Update Category
        $category_id = $_POST['category_id'];
        $name = htmlspecialchars($_POST['name']);

        $sql = "UPDATE categories SET name = :name WHERE id = :category_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['name' => $name, 'category_id' => $category_id]);

        header("Location: admin_dashboard.php?page=categories");
        exit();
    } elseif (isset($_POST['delete_category'])) {
        // Delete Category
        $category_id = $_POST['category_id'];

        $sql = "DELETE FROM categories WHERE id = :category_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['category_id' => $category_id]);

        header("Location: admin_dashboard.php?page=categories");
        exit();
    }
}

// Redirect if accessed directly
if (!isset($_GET['page'])) {
    header("Location: admin_dashboard.php?page=categories");
    exit();
}