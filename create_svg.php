<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create and Save SVG</title>
    <style>
        #svgCanvas {
            border: 1px solid #000;
            position: relative;
        }
        .background-image {
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none;
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
    <div class="button-container">
        <button id="toggleBackground">Toggle Background</button>
        <input type="file" id="uploadImage" accept="image/*" />
        <svg id="svgCanvas" width="800" height="600">
            <!-- Initial SVG content, if any -->
        </svg>
    </div>
    <div class="button-container">
        <button id="undoSvg">Undo</button>
        <button id="redoSvg">Redo</button>
        <button id="saveSvg">Save SVG</button>
        <button id="clearSvg">Clear SVG</button>
        <form id="svgForm" method="POST" action="save_svg.php">
            <input type="hidden" id="svgData" name="svgData" />
            <button type="submit" id="submitSvg">Submit SVG</button>
        </form>
        <select id="shapeSelector">
            <option value="rect">Rectangle</option>
            <option value="circle">Circle</option>
            <option value="ellipse">Ellipse</option>
            <option value="line">Line</option>
            <option value="polygon">Polygon</option>
        </select>
    </div>

    <script>
const svgCanvas = document.getElementById('svgCanvas');
const shapeSelector = document.getElementById('shapeSelector');
const uploadImage = document.getElementById('uploadImage');
const toggleBackground = document.getElementById('toggleBackground');
const clearSvg = document.getElementById('clearSvg');
let isDrawing = false;
let startX, startY;
let currentElement;
let undoStack = [];
let redoStack = [];
let shapeType = 'rect';
let backgroundImageElement;
let pointsArray = []; // Array to store points for the polygon
let polygonElement = null; // Store the polygon element being drawn
let startingPoint = null; // To store the starting point for polygon
let idCounter = 1; // Counter for generating unique IDs

// Helper functions

function generateUniqueId(prefix) {
    return `${prefix}_${Date.now()}_${idCounter++}`;
}

function addToUndoStack(action) {
    undoStack.push(action);
    redoStack = []; // Clear the redo stack
}

function createElement(type, attributes) {
    const element = document.createElementNS('http://www.w3.org/2000/svg', type);
    const id = generateUniqueId(type); // Generate unique ID with type as prefix
    element.setAttribute('id', id); // Set ID
    for (const attr in attributes) {
        element.setAttribute(attr, attributes[attr]);
    }
    element.setAttribute('fill', 'transparent'); // No fill color (transparent)
    element.setAttribute('stroke', 'black'); // Black border
    element.setAttribute('stroke-width', '1'); // Border thickness
    return element;
}

function updateBackgroundImage(url) {
    if (backgroundImageElement) {
        svgCanvas.removeChild(backgroundImageElement);
    }
    backgroundImageElement = createElement('image', {
        href: url,
        x: 0,
        y: 0,
        width: svgCanvas.getAttribute('width'),
        height: svgCanvas.getAttribute('height'),
        class: 'background-image'
    });
    svgCanvas.appendChild(backgroundImageElement);
}

function toggleBackgroundVisibility() {
    if (backgroundImageElement) {
        const isHidden = backgroundImageElement.getAttribute('visibility') === 'hidden';
        backgroundImageElement.setAttribute('visibility', isHidden ? 'visible' : 'hidden');
    }
}

function clearSvgCanvas() {
    // Remove all child elements but keep the background image
    Array.from(svgCanvas.children).forEach(child => {
        if (child !== backgroundImageElement) {
            addToUndoStack({ action: 'remove', element: child });
            svgCanvas.removeChild(child);
        }
    });
    pointsArray = []; // Reset points array
    polygonElement = null; // Reset polygon element
    startingPoint = null; // Reset starting point
}

function distanceBetweenPoints(x1, y1, x2, y2) {
    return Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
}

// Event listeners

shapeSelector.addEventListener('change', (event) => {
    shapeType = event.target.value;
    if (shapeType !== 'polygon') {
        pointsArray = []; // Reset points array for non-polygon shapes
        polygonElement = null; // Reset polygon element
        startingPoint = null; // Reset starting point
    }
});

svgCanvas.addEventListener('mousedown', (event) => {
    event.preventDefault(); // Prevent the default context menu on right-click

    if (event.button === 2) { // Right-click
        if (shapeType === 'polygon' && polygonElement && pointsArray.length > 2) {
            // Close the polygon
            pointsArray.push(pointsArray[0]); // Close the polygon by adding the first point
            polygonElement.setAttribute('points', pointsArray.join(' '));
            addToUndoStack({ action: 'create', element: polygonElement });
            pointsArray = []; // Reset points array
            polygonElement = null; // Reset polygon element
            startingPoint = null; // Reset starting point
        }
    } else if (event.target === svgCanvas && event.button === 0) { // Left-click
        const rect = svgCanvas.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;

        if (shapeType === 'polygon') {
            if (!startingPoint) {
                startingPoint = { x, y }; // Set starting point
            }

            pointsArray.push(`${x},${y}`);

            if (!polygonElement) {
                polygonElement = createElement('polygon', {
                    points: pointsArray.join(' '),
                    class: 'selected'
                });
                svgCanvas.appendChild(polygonElement);
                addToUndoStack({ action: 'create', element: polygonElement });
            } else {
                polygonElement.setAttribute('points', pointsArray.join(' '));
            }
        } else if (shapeType === 'line') {
            startX = x;
            startY = y;

            isDrawing = true;

            currentElement = createElement('line', {
                x1: startX,
                y1: startY,
                x2: startX, // Initial x2 same as x1
                y2: startY  // Initial y2 same as y1
            });

            svgCanvas.appendChild(currentElement);
            addToUndoStack({ action: 'create', element: currentElement });
        } else {
            startX = x;
            startY = y;

            isDrawing = true;

            currentElement = createElement(shapeType, {
                x: startX,
                y: startY
            });

            if (shapeType === 'circle') {
                currentElement.setAttribute('cx', startX);
                currentElement.setAttribute('cy', startY);
                currentElement.setAttribute('r', 0);
            } else if (shapeType === 'ellipse') {
                currentElement.setAttribute('cx', startX);
                currentElement.setAttribute('cy', startY);
                currentElement.setAttribute('rx', 0);
                currentElement.setAttribute('ry', 0);
            } else if (shapeType === 'rect') {
                currentElement.setAttribute('x', startX);
                currentElement.setAttribute('y', startY);
                currentElement.setAttribute('width', 0);
                currentElement.setAttribute('height', 0);
            }

            svgCanvas.appendChild(currentElement);
            addToUndoStack({ action: 'create', element: currentElement });
        }
    }
});

svgCanvas.addEventListener('mousemove', (event) => {
    if (isDrawing) {
        const rect = svgCanvas.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;

        if (shapeType === 'line') {
            currentElement.setAttribute('x2', x);
            currentElement.setAttribute('y2', y);
        } else if (shapeType === 'circle') {
            const r = Math.sqrt(Math.pow(x - startX, 2) + Math.pow(y - startY, 2));
            currentElement.setAttribute('r', r);
        } else if (shapeType === 'ellipse') {
            const rx = Math.abs(x - startX);
            const ry = Math.abs(y - startY);
            currentElement.setAttribute('rx', rx);
            currentElement.setAttribute('ry', ry);
        } else if (shapeType === 'rect') {
            currentElement.setAttribute('width', Math.abs(x - startX));
            currentElement.setAttribute('height', Math.abs(y - startY));
            currentElement.setAttribute('x', Math.min(x, startX));
            currentElement.setAttribute('y', Math.min(y, startY));
        }
    }
});

svgCanvas.addEventListener('mouseup', (event) => {
    if (isDrawing) {
        isDrawing = false;
        if (shapeType === 'rect') {
            const rectWidth = Math.abs(event.clientX - svgCanvas.getBoundingClientRect().left - startX);
            const rectHeight = Math.abs(event.clientY - svgCanvas.getBoundingClientRect().top - startY);
            currentElement.setAttribute('width', rectWidth);
            currentElement.setAttribute('height', rectHeight);
        }
        currentElement = null;
    }
});

document.getElementById('saveSvg').addEventListener('click', () => {
    const svgData = new XMLSerializer().serializeToString(svgCanvas);
    document.getElementById('svgData').value = svgData;
});

document.getElementById('clearSvg').addEventListener('click', () => {
    clearSvgCanvas();
});

document.getElementById('undoSvg').addEventListener('click', () => {
    if (undoStack.length > 0) {
        const action = undoStack.pop();
        if (action.action === 'create') {
            svgCanvas.removeChild(action.element);
            redoStack.push(action);
        } else if (action.action === 'remove') {
            svgCanvas.appendChild(action.element);
            redoStack.push(action);
        }
    }
});

document.getElementById('redoSvg').addEventListener('click', () => {
    if (redoStack.length > 0) {
        const action = redoStack.pop();
        if (action.action === 'create') {
            svgCanvas.appendChild(action.element);
            undoStack.push(action);
        } else if (action.action === 'remove') {
            svgCanvas.removeChild(action.element);
            undoStack.push(action);
        }
    }
});

document.getElementById('uploadImage').addEventListener('change', (event) => {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            updateBackgroundImage(e.target.result);
        };
        reader.readAsDataURL(file);
    }
});

toggleBackground.addEventListener('click', () => {
    toggleBackgroundVisibility();
});

document.getElementById('svgForm').addEventListener('submit', (event) => {
    const svgData = new XMLSerializer().serializeToString(svgCanvas);
    if (!svgData) {
        event.preventDefault(); // Prevent form submission if SVG data is empty
        alert('No SVG data to submit.');
    } else {
        document.getElementById('svgData').value = svgData;
    }
});

// Handle Escape key to stop drawing
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        if (isDrawing) {
            isDrawing = false;
            if (currentElement) {
                svgCanvas.removeChild(currentElement);
                currentElement = null;
            }
        }
        pointsArray = []; // Reset points array
        polygonElement = null; // Reset polygon element
        startingPoint = null; // Reset starting point
    }
});
    </script>
</body>
</html>
