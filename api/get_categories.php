<?php
header('Content-Type: application/json');
include 'config.php';

$main_category_id = isset($_POST['main_category_id']) ? (int)$_POST['main_category_id'] : 0;

$stmt = $conn->prepare("SELECT category_id, name FROM categories WHERE main_category_id = ? AND status = 'active'");
$stmt->bind_param("i", $main_category_id);
$stmt->execute();
$result = $stmt->get_result();

$options = '';
while ($row = $result->fetch_assoc()) {
    $options .= "<option value='{$row['category_id']}'>{$row['name']}</option>";
}

echo $options;
$stmt->close();
$conn->close();
?>