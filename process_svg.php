<?php
// Database connection settings
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

// Get SVG ID from query string
$svgId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch the SVG content
$sql = "SELECT svg_element FROM svg_elements WHERE id = $svgId";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $svgContent = $row['svg_element'];
} else {
    die('SVG not found.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $elementName = $conn->real_escape_string($_POST['elementName']);
    $elementId = $conn->real_escape_string($_POST['elementId']);
    $x = $conn->real_escape_string($_POST['elementX']);
    $y = $conn->real_escape_string($_POST['elementY']);
    $width = $conn->real_escape_string($_POST['elementWidth']);
    $height = $conn->real_escape_string($_POST['elementHeight']);
    $svg_element_id = $conn->real_escape_string($_POST['svg_element_id']);

    // Check if the element already exists
    $sqlCheck = "SELECT * FROM svg_clicks WHERE element_id = '$elementId' AND svg_element_id='$svg_element_id'";
    $resultCheck = $conn->query($sqlCheck);

    if ($resultCheck->num_rows > 0) {
        // Update existing record
        $sql = "UPDATE svg_clicks SET name='$elementName', x='$x', y='$y', width='$width', height='$height' WHERE element_id='$elementId' AND svg_element_id='$svg_element_id'";
    } else {
        // Insert new record
        $sql = "INSERT INTO svg_clicks (name, element_id, x, y, width, height,svg_element_id) VALUES ('$elementName', '$elementId', '$x', '$y', '$width', '$height','$svg_element_id')";
    }

    if ($conn->query($sql) === TRUE) {
        $message = 'Data saved successfully!';
    } else {
        $message = 'Error: ' . $conn->error;
    }

    // Reload SVG content after updating
    $sql = "SELECT svg_element FROM svg_elements WHERE id = $svgId";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $svgContent = $row['svg_element'];
    }
}

// Fetch the labels from the database
$sql = "SELECT x, y, width, height, name, element_id FROM svg_clicks WHERE svg_element_id=$svgId";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $dom = new DOMDocument();
    $dom->loadXML($svgContent);
    $svg = $dom->getElementsByTagName('svg')->item(0);

    // Remove existing text elements to avoid duplication
    foreach ($svg->getElementsByTagName('text') as $textElement) {
        $svg->removeChild($textElement);
    }

    while ($row = $result->fetch_assoc()) {
        $textElement = $dom->createElement('text', htmlspecialchars($row['name']));
        $textElement->setAttribute('x', $row['x'] + $row['width'] / 2);
        $textElement->setAttribute('y', $row['y'] + $row['height'] / 2);
        $textElement->setAttribute('text-anchor', 'middle');
        $textElement->setAttribute('dominant-baseline', 'central');
        $textElement->setAttribute('class', 'label');

        $svg->appendChild($textElement);
    }

    $svgContent = $dom->saveXML();
}

// Close the connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interactive SVG Example with Form</title>
    <style>
        .interactive {
            cursor: pointer;
            fill: #fff;
            stroke: #000;
        }
        .interactive:hover {
            fill: #f0f0f0;
        }
        #formContainer {
            margin-top: 20px;
            display: none;
        }
        .label {
            font-size: 12px;
            fill: #000;
        }
    </style>
</head>
<body>
    <h1>Interactive SVG Example with Form</h1>

    <!-- Container where the SVG will be loaded -->
    <div id="svgContainer"><?php echo $svgContent; ?></div>

    <!-- Form to input additional details -->
    <div id="formContainer">
        <h2>Enter Details for the Selected Area</h2>
        <form id="dataForm">
            <label for="elementName">Element Name:</label>
            <input type="text" id="elementName" name="elementName" required><br><br>
            <input type="hidden" id="elementId" name="elementId">
            <input type="hidden" id="elementX" name="elementX">
            <input type="hidden" id="elementY" name="elementY">
            <input type="hidden" id="elementWidth" name="elementWidth">
            <input type="hidden" id="elementHeight" name="elementHeight">
            <input type="hidden" id="svg_element_id" name="svg_element_id" value="<?php echo $svgId ?>">
            
            <button type="submit">Save Data</button>
        </form>
    </div>

    <script>
        // Ensure the SVG is properly loaded and interactive
        document.addEventListener('DOMContentLoaded', () => {
            const svgContainer = document.getElementById('svgContainer');
            const svgDoc = svgContainer.querySelector('svg');

            // Ensure the SVG is valid
            if (!svgDoc) {
                console.error('SVG element not found.');
                return;
            }

            // Add interactivity to all SVG shapes
            const interactiveShapes = ['rect', 'circle', 'ellipse', 'line', 'polygon'];

            interactiveShapes.forEach(shape => {
                svgDoc.querySelectorAll(shape).forEach(element => {
                    element.classList.add('interactive'); // Add the interactive class

                    element.addEventListener('click', function() {
                        const id = this.getAttribute('id');
                        const bbox = this.getBBox();

                        let selectedArea = {
                            x: bbox.x,
                            y: bbox.y,
                            width: bbox.width,
                            height: bbox.height
                        };

                        // Handle special cases for different shapes
                        if (shape === 'circle') {
                            selectedArea = {
                                x: this.getAttribute('cx'),
                                y: this.getAttribute('cy'),
                                width: this.getAttribute('r'),
                                height: this.getAttribute('r')
                            };
                        } else if (shape === 'ellipse') {
                            selectedArea = {
                                x: this.getAttribute('cx'),
                                y: this.getAttribute('cy'),
                                width: this.getAttribute('rx'),
                                height: this.getAttribute('ry')
                            };
                        } else if (shape === 'line') {
                            selectedArea = {
                                x: this.getAttribute('x1'),
                                y: this.getAttribute('y1'),
                                width: Math.abs(this.getAttribute('x2') - this.getAttribute('x1')),
                                height: Math.abs(this.getAttribute('y2') - this.getAttribute('y1'))
                            };
                        } else if (shape === 'polygon') {
                            const points = this.getAttribute('points').trim().split(' ');
                            const xCoords = points.map(p => parseFloat(p.split(',')[0]));
                            const yCoords = points.map(p => parseFloat(p.split(',')[1]));

                            const minX = Math.min(...xCoords);
                            const minY = Math.min(...yCoords);
                            const maxX = Math.max(...xCoords);
                            const maxY = Math.max(...yCoords);

                            selectedArea = {
                                x: minX,
                                y: minY,
                                width: maxX - minX,
                                height: maxY - minY
                            };
                        }

                        // Show the form and fill in the hidden inputs
                        document.getElementById('formContainer').style.display = 'block';
                        document.getElementById('elementId').value = id;
                        document.getElementById('elementX').value = selectedArea.x;
                        document.getElementById('elementY').value = selectedArea.y;
                        document.getElementById('elementWidth').value = selectedArea.width;
                        document.getElementById('elementHeight').value = selectedArea.height;
                    });
                });
            });
        });

        // Handle form submission
        document.getElementById('dataForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log('Success:', data);
                alert('Data saved successfully!');
                // Reload the page to see the updated SVG
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>
