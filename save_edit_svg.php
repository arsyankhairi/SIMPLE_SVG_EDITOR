<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "svg_test";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$svgId = $_POST['svgId'] ?? '';
$svgData = $_POST['svgData'] ?? '';

if ($svgId && $svgData) {
    $stmt = $conn->prepare("UPDATE svg_elements SET svg_element = ? WHERE id = ?");
    $stmt->bind_param("si", $svgData, $svgId);
    $stmt->execute();
    $stmt->close();
}

$conn->close();

// Redirect back to the listing page
header("Location: listing_svg.php");
exit();
?>
