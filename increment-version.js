// Increments version numbers in dev and main

const fs = require('fs');
const path = require('path');

// Define the paths to the files
const packageJsonPath = path.resolve(__dirname, 'package.json');
const readmePath = path.resolve(__dirname, 'README.md');
const pluginFilePath = path.resolve(__dirname, 'rrze-shorturl.php');

// Function to increment the version based on the type
function incrementVersion(version, type) {
    const parts = version.split('.');
    if (type === 'minor') {
        parts[1] = parseInt(parts[1], 10) + 1; // Increment the minor version
        parts[2] = 0; // Reset the patch version to 0
    } else if (type === 'patch') {
        parts[2] = parseInt(parts[2], 10) + 1; // Increment the patch version
    } else {
        console.error(`Unknown version increment type: ${type}`);
        process.exit(1);
    }
    return parts.join('.');
}

// Function to update the version in a file
function updateVersionInFile(filePath, oldVersion, newVersion) {
    try {
        let content = fs.readFileSync(filePath, 'utf8');
        const regex = new RegExp(oldVersion.replace(/\./g, '\\.'), 'g');
        content = content.replace(regex, newVersion);
        fs.writeFileSync(filePath, content, 'utf8');
        console.log(`Updated version in ${filePath}`);
    } catch (error) {
        console.error(`Failed to update version in ${filePath}: ${error.message}`);
        process.exit(1);
    }
}

// Get the version increment type from the command line arguments
const incrementType = process.argv[2];
if (!incrementType) {
    console.error("No increment type specified. Use 'minor' or 'patch'.");
    process.exit(1);
}

// Read and update package.json
let oldVersion;
let newVersion;
try {
    const packageJson = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
    oldVersion = packageJson.version;
    newVersion = incrementVersion(oldVersion, incrementType);
    packageJson.version = newVersion;
    fs.writeFileSync(packageJsonPath, JSON.stringify(packageJson, null, 2), 'utf8');
    console.log(`Version updated from ${oldVersion} to ${newVersion} in package.json`);
} catch (error) {
    console.error(`Failed to read or update package.json: ${error.message}`);
    process.exit(1);
}

try {
    // Update Stable tag in README.md
    if (fs.existsSync(readmePath)) {
        updateVersionInFile(readmePath, `Stable tag: ${oldVersion}`, `Stable tag: ${newVersion}`);
    } else {
        console.warn(`File ${readmePath} not found.`);
    }
} catch (error) {
    console.error(`Error updating version in file ${readmePath}:`, error);
}

try {
    // Update version in PLUGIN-NAME.php
    if (fs.existsSync(pluginFilePath)) {
        updateVersionInFile(pluginFilePath, `Version: ${oldVersion}`, `Version: ${newVersion}`);
    } else {
        console.warn(`File ${pluginFilePath} not found.`);
    }
} catch (error) {
    console.error(`Error updating version in file ${pluginFilePath}:`, error);
}

console.log(`Version successfully updated to ${newVersion}`);
