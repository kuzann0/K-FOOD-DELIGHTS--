<?php
require_once 'config.php';
require_once 'functions.php';

// Function to add a new category
function addCategory($name, $description) {
    global $conn;
    $name = mysqli_real_escape_string($conn, $name);
    $description = mysqli_real_escape_string($conn, $description);
    
    $query = "INSERT INTO categories (name, description) VALUES ('$name', '$description')";
    return mysqli_query($conn, $query);
}

// Function to update a category
function updateCategory($id, $name, $description) {
    global $conn;
    $id = (int)$id;
    $name = mysqli_real_escape_string($conn, $name);
    $description = mysqli_real_escape_string($conn, $description);
    
    $query = "UPDATE categories SET name = '$name', description = '$description' WHERE id = $id";
    return mysqli_query($conn, $query);
}

// Function to delete a category
function deleteCategory($id) {
    global $conn;
    $id = (int)$id;
    
    // Check if category has products
    $checkQuery = "SELECT COUNT(*) as count FROM menu_items WHERE category_id = $id";
    $result = mysqli_query($conn, $checkQuery);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] > 0) {
        return false; // Category has products, cannot delete
    }
    
    $query = "DELETE FROM categories WHERE id = $id";
    return mysqli_query($conn, $query);
}

// Function to get all categories
function getAllCategories() {
    global $conn;
    $query = "SELECT * FROM categories ORDER BY name";
    $result = mysqli_query($conn, $query);
    
    $categories = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    
    return $categories;
}

// Function to get a single category by ID
function getCategoryById($id) {
    global $conn;
    $id = (int)$id;
    
    $query = "SELECT * FROM categories WHERE id = $id";
    $result = mysqli_query($conn, $query);
    
    return mysqli_fetch_assoc($result);
}
?>
