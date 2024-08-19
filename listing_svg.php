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

// Fetch SVG data
$sql = "SELECT id, element_id, svg_element FROM svg_elements";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SVG Listing</title>
    <style>
        .svg-listing {
            display: flex;
            flex-wrap: wrap;
        }
        .svg-item {
            border: 1px solid #ccc;
            padding: 10px;
            margin: 10px;
            max-width: 300px;
        }
        .svg-item svg {
            width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <h1>SVG Listings</h1>
    <div class="svg-listing">
        <?php
        if ($result->num_rows > 0) {
            // Output data of each row
            while ($row = $result->fetch_assoc()) {
                $svgData = $row['svg_element'];
                $svgId = htmlspecialchars($row['id']);
                ?>
                <div class="svg-item">
                    <h3>SVG ID: <?php echo $svgId; ?></h3>
                    <a href="process_svg.php?id=<?php echo $svgId; ?>">
                        <div class="svg-content">
                            <?php echo $svgData; ?>
                        </div>
                    </a>
                </div>
                <?php
            }
        } else {
            echo "<p>No SVGs found.</p>";
        }
        ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>
