<?php
include 'config.php';

// Add IP
if ($_POST['action'] ?? '' == 'add_ip') {
    $ip = $conn->real_escape_string($_POST['ip_address']);
    $conn->query("INSERT INTO allowed_ips (ip_address, created_at, status) VALUES ('$ip', NOW(), 1)");
    header("Location: ../admin/manage_ips.php");
    exit;
}

// Update IP
if ($_POST['action'] ?? '' == 'update_ip') {
    $id = (int)$_POST['id'];
    $ip = $conn->real_escape_string($_POST['ip_address']);
    $status = (int)$_POST['status'];
    $conn->query("UPDATE allowed_ips SET ip_address='$ip', status=$status WHERE id=$id");
    header("Location: ../admin/manage_ips.php");
    exit;
}

// Toggle Status
if ($_GET['action'] ?? '' == 'toggle_status') {
    $id = (int)$_GET['id'];
    $res = $conn->query("SELECT status FROM allowed_ips WHERE id=$id");
    if ($row = $res->fetch_assoc()) {
        $new_status = $row['status']==1 ? 0 : 1;
        $conn->query("UPDATE allowed_ips SET status=$new_status WHERE id=$id");
    }
    header("Location: ../admin/manage_ips.php");
    exit;
}

// Delete IP
if ($_GET['action'] ?? '' == 'delete') {
    $id = (int)$_GET['id'];
    $conn->query("DELETE FROM allowed_ips WHERE id=$id");
    header("Location: ../admin/manage_ips.php");
    exit;
}

?>
