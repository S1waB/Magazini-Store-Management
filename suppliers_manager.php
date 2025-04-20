<?php
// suppliers_manager.php
require 'db.php';

function getTotalOffersBySupplier($supplier_id)
{
    global $pdo;
    try {
        $sql = "SELECT COUNT(*) AS total_offers FROM offers WHERE supplier_id = :supplier_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['supplier_id' => $supplier_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total_offers'];
    } catch (PDOException $e) {
        die("Error fetching total offers: " . $e->getMessage());
    }
}

function getAcceptedOffersCountBySupplier($supplier_id)
{
    global $pdo;
    try {
        $sql = "SELECT COUNT(*) AS accepted_offers FROM offers WHERE supplier_id = :supplier_id AND status = 'accepted'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['supplier_id' => $supplier_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['accepted_offers'];
    } catch (PDOException $e) {
        die("Error fetching accepted offers count: " . $e->getMessage());
    }
}

function getRejectedOffersCountBySupplier($supplier_id)
{
    global $pdo;
    try {
        $sql = "SELECT COUNT(*) AS rejected_offers FROM offers WHERE supplier_id = :supplier_id AND status = 'rejected'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['supplier_id' => $supplier_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['rejected_offers'];
    } catch (PDOException $e) {
        die("Error fetching rejected offers count: " . $e->getMessage());
    }
}
function getOffersBySupplier($supplier_id)
{
    global $pdo;
    try {
        $sql = "SELECT o.*, p.name AS product_name 
                FROM offers o
                LEFT JOIN products p ON o.product_id = p.id
                WHERE o.supplier_id = :supplier_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['supplier_id' => $supplier_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching offers: " . $e->getMessage());
    }
}
function getProductsBySupplier($supplier_id)
{
    global $pdo;
    try {
        $sql = "SELECT p.*, c.name AS category_name, u.name AS supplier_name 
                FROM products p
                JOIN categories c ON p.category_id = c.id
                JOIN users u ON p.supplier_id = u.id WHERE supplier_id = :supplier_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['supplier_id' => $supplier_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching products: " . $e->getMessage());
    }
}

function getTotalProductsBySupplier($supplier_id)
{
    global $pdo;
    try {
        $sql = "SELECT COUNT(*) AS total_products FROM products WHERE supplier_id = :supplier_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['supplier_id' => $supplier_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];
    } catch (PDOException $e) {
        die("Error fetching total products: " . $e->getMessage());
    }
}
