<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create and Save SVG</title>
    <style>
        #svgCanvas {
            border: 1px solid #000;
        }
        .selected {
            stroke: red;
            stroke-width: 2;
            fill: rgba(255, 0, 0, 0.2);
        }
        .button-container {
            margin-top: 10px;
        }
        .interactive {
            cursor: pointer;
            fill: #fff;
            stroke: #000;
        }
        .interactive:hover {
            fill: #f0f0f0;
        }
    </style>
</head>
<body>
    <h1>Create and Save SVG</h1>
    <svg id="svgCanvas" width="800" height="600">
        <!-- Initial SVG content, if any -->
    </svg>

    <div class="button-container">
        <button id="saveSvg">Save SVG</button>
        <button id="clearSvg">Clear SVG</button>
        <form id="svgForm" method="POST" action="save_svg.php">
            <input type="hidden" id="svgData" name="svgData" />
            <button type="submit" id="submitSvg">Submit SVG</button>
        </form>
    </div>

    <script>
        const svgCanvas = document.getElementById('svgCanvas');
        let isDrawing = false;
        let startX, startY;
        let currentElement;
        let selectedElementId = null;

        // Start drawing on mousedown
        svgCanvas.addEventListener('mousedown', (event) => {
            if (event.target === svgCanvas) {
                isDrawing = true;
                const rect = svgCanvas.getBoundingClientRect();
                startX = event.clientX - rect.left;
                startY = event.clientY - rect.top;
                currentElement = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                currentElement.setAttribute('x', startX);
                currentElement.setAttribute('y', startY);
                currentElement.setAttribute('width', 0);
                currentElement.setAttribute('height', 0);
                currentElement.classList.add('selected');
                const id = `rect_${Date.now()}`;
                currentElement.setAttribute('id', id);
                svgCanvas.appendChild(currentElement);

                // Add interactivity to the new element
               
            }
        });

        // Draw rectangle on mousemove
        svgCanvas.addEventListener('mousemove', (event) => {
            if (isDrawing) {
                const rect = svgCanvas.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;
                const width = Math.abs(x - startX);
                const height = Math.abs(y - startY);
                currentElement.setAttribute('width', width);
                currentElement.setAttribute('height', height);
                currentElement.setAttribute('x', Math.min(x, startX));
                currentElement.setAttribute('y', Math.min(y, startY));
            }
        });

        // Finish drawing on mouseup
        svgCanvas.addEventListener('mouseup', () => {
            if (isDrawing) {
                isDrawing = false;
            }
        });

        // Save SVG as file
        document.getElementById('saveSvg').addEventListener('click', () => {
            const svgData = new XMLSerializer().serializeToString(svgCanvas);
            const blob = new Blob([svgData], {type: 'image/svg+xml'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'drawing.svg';
            a.click();
            URL.revokeObjectURL(url);

            // Set SVG data in the form for submission
            document.getElementById('svgData').value = svgData;
        });

        // Clear SVG content
        document.getElementById('clearSvg').addEventListener('click', () => {
            while (svgCanvas.firstChild) {
                svgCanvas.removeChild(svgCanvas.firstChild);
            }
        });
        document.getElementById('saveSvg').addEventListener('click', () => {
    const svgData = new XMLSerializer().serializeToString(svgCanvas);
    const blob = new Blob([svgData], {type: 'image/svg+xml'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'drawing.svg';
    a.click();
    URL.revokeObjectURL(url);

    // Set SVG data in the form for submission
    document.getElementById('svgData').value = svgData;
});

document.getElementById('submitSvg').addEventListener('click', (event) => {
    event.preventDefault(); // Prevent default form submission
    const svgData = new XMLSerializer().serializeToString(svgCanvas);
    document.getElementById('svgData').value = svgData;
    document.getElementById('svgForm').submit(); // Submit the form
});


       
    </script>
</body>
</html>
