<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "svg_test";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT svg_element FROM svg_elements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($svgElement);
    $stmt->fetch();
    
    echo $svgElement;

    $stmt->close();
}

$conn->close();
?>
