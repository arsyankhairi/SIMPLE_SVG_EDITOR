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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $svgData = $_POST['svgData'];
    $elementId = uniqid('element_'); // Generate a unique ID for the element

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO svg_elements (element_id, svg_element) VALUES (?, ?) ON DUPLICATE KEY UPDATE svg_element = VALUES(svg_element)");
    $stmt->bind_param("ss", $elementId, $svgData);

    // Execute the statement
    if ($stmt->execute()) {
        echo "SVG data saved successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
