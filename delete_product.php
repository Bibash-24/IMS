<?php 
session_start();
$conn = @new mysqli("localhost","root","","ims");
if($conn->connect_error) {
    $conn = @new mysqli("localhost","root","1234","ims");
}
if($conn->connect_error) die("DB Connection Error: " . $conn->connect_error);
$id = $_GET['id'];

$stmt = $conn->prepare("DELETE FROM products WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();

header("Location: inventory.php");
?>
