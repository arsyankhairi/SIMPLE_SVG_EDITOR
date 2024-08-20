<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "svg_test";

// Directory to save SVG files
$uploadDir = 'svg_files/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true); // Create the directory if it does not exist
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $svgData = $_POST['svgData'];
    $svgId = $_POST['svgId'] ?? '';

    // Generate a unique file name
    $fileName = uniqid('svg_', true) . '.svg';
    $filePath = $uploadDir . $fileName;

    // Save SVG data to the file
    if (file_put_contents($filePath, $svgData) === false) {
        die("Failed to save SVG file.");
    }

    // Prepare SQL statement
    if (!empty($svgId)) {
        // Update existing SVG element with new file name
        $stmt = $conn->prepare("UPDATE svg_elements SET svg_element = ? WHERE id = ?");
        $stmt->bind_param("ss", $fileName, $svgId);
    } else {
        // Insert new SVG element with file name
        $elementId = uniqid('element_');
        $stmt = $conn->prepare("INSERT INTO svg_elements (element_id, svg_element) VALUES (?, ?)");
        $stmt->bind_param("ss", $elementId, $fileName);
    }

    // Execute the statement
    if ($stmt->execute()) {
        echo "SVG file saved successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>