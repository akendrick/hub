const fs = require('fs');
const path = require('path');

const directoryPath = './'; // Current folder
const templatePath = './index.html';

// 1. Read files in the current directory
fs.readdir(directoryPath, (err, files) => {
    if (err) return console.log('Unable to scan directory: ' + err);

    // 2. Filter out the script itself and the index file
    const filteredFiles = files.filter(file => 
        file !== 'scanner.js' && 
        file !== 'index.html' && 
        !file.startsWith('.') // Ignore hidden files
    );

    // 3. Create HTML button strings
    const buttonsHtml = filteredFiles.map(file => {
        return `<a href="${file}" class="file-button">${file}</a>`;
    }).join('\n        ');

    // 4. Read the HTML file and inject the buttons
    fs.readFile(templatePath, 'utf8', (err, data) => {
        if (err) return console.log(err);

        // This regex finds the div with id="file-list" and puts buttons inside
        const result = data.replace(
            /(<div id="file-list" class="grid">)([\s\S]*?)(<\/div>)/,
            `$1\n        ${buttonsHtml}\n    $3`
        );

        fs.writeFile(templatePath, result, 'utf8', (err) => {
            if (err) return console.log(err);
            console.log('Successfully scanned and updated index.html!');
        });
    });
});