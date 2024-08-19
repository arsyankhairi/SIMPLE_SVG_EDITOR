<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create and Save SVG</title>
    <style>
        #svgCanvas {
            border: 1px solid #000;
            position: relative; /* Ensure positioning of the background image */
        }
        .background-image {
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none; /* Allow interactions with SVG elements above the image */
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
        .hidden {
    display: none;
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

// Helper functions

function addToUndoStack(action) {
    undoStack.push(action);
    redoStack = []; // Clear the redo stack
}

function createElement(type, attributes) {
    const element = document.createElementNS('http://www.w3.org/2000/svg', type);
    for (const attr in attributes) {
        element.setAttribute(attr, attributes[attr]);
    }
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
}

// Event listeners

shapeSelector.addEventListener('change', (event) => {
    shapeType = event.target.value;
});

svgCanvas.addEventListener('mousedown', (event) => {
    if (event.target === svgCanvas) {
        isDrawing = true;
        const rect = svgCanvas.getBoundingClientRect();
        startX = event.clientX - rect.left;
        startY = event.clientY - rect.top;

        if (shapeType === 'line') {
            currentElement = createElement('line', {
                x1: startX,
                y1: startY,
                x2: startX,
                y2: startY,
                class: 'selected'
            });
        } else if (shapeType === 'polygon') {
            currentElement = createElement('polygon', {
                points: `${startX},${startY}`,
                class: 'selected'
            });
        } else if (shapeType === 'circle') {
            currentElement = createElement('circle', {
                cx: startX,
                cy: startY,
                r: 0,
                class: 'selected'
            });
        } else if (shapeType === 'ellipse') {
            currentElement = createElement('ellipse', {
                cx: startX,
                cy: startY,
                rx: 0,
                ry: 0,
                class: 'selected'
            });
        } else {
            currentElement = createElement(shapeType, {
                x: startX,
                y: startY,
                width: 0,
                height: 0,
                class: 'selected'
            });
        }

        const id = `${shapeType}_${Date.now()}`;
        currentElement.setAttribute('id', id);
        svgCanvas.appendChild(currentElement);
        addToUndoStack({ action: 'create', element: currentElement });
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
        } else if (shapeType === 'polygon') {
            const points = currentElement.getAttribute('points');
            const newPoints = `${points} ${x},${y}`;
            currentElement.setAttribute('points', newPoints);
        } else if (shapeType === 'circle') {
            const r = Math.sqrt(Math.pow(x - startX, 2) + Math.pow(y - startY, 2));
            currentElement.setAttribute('r', r);
        } else if (shapeType === 'ellipse') {
            const rx = Math.abs(x - startX);
            const ry = Math.abs(y - startY);
            currentElement.setAttribute('rx', rx);
            currentElement.setAttribute('ry', ry);
        } else {
            const width = Math.abs(x - startX);
            const height = Math.abs(y - startY);
            currentElement.setAttribute('width', width);
            currentElement.setAttribute('height', height);
            currentElement.setAttribute('x', Math.min(x, startX));
            currentElement.setAttribute('y', Math.min(y, startY));
        }
    }
});

svgCanvas.addEventListener('mouseup', () => {
    if (isDrawing) {
        isDrawing = false;
        if (shapeType === 'polygon') {
            const points = currentElement.getAttribute('points').trim().split(' ');
            if (points.length > 2) {
                points.push(points[0]); // Add the first point to close the polygon
                currentElement.setAttribute('points', points.join(' '));
            }
        }
    }
});

document.getElementById('undoSvg').addEventListener('click', () => {
    if (undoStack.length > 0) {
        const lastAction = undoStack.pop();
        if (lastAction.action === 'create') {
            svgCanvas.removeChild(lastAction.element);
            redoStack.push(lastAction);
        } else if (lastAction.action === 'remove') {
            svgCanvas.appendChild(lastAction.element);
            redoStack.push(lastAction);
        }
    }
});

document.getElementById('redoSvg').addEventListener('click', () => {
    if (redoStack.length > 0) {
        const lastAction = redoStack.pop();
        if (lastAction.action === 'create') {
            svgCanvas.appendChild(lastAction.element);
            undoStack.push(lastAction);
        } else if (lastAction.action === 'remove') {
            svgCanvas.removeChild(lastAction.element);
            undoStack.push(lastAction);
        }
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
    document.getElementById('svgData').value = svgData;
});

clearSvg.addEventListener('click', (event) => {
    event.preventDefault(); // Prevent form submission or page reload
    clearSvgCanvas();
});

document.getElementById('submitSvg').addEventListener('click', (event) => {
    event.preventDefault(); // Prevent default form submission
    const svgData = new XMLSerializer().serializeToString(svgCanvas);
    document.getElementById('svgData').value = svgData;
    document.getElementById('svgForm').submit(); // Submit the form
});

uploadImage.addEventListener('change', (event) => {
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

    </script>
</body>
</html>
