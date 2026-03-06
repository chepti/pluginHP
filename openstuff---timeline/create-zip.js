/**
 * Create distribution ZIP - cross-platform format for WordPress
 * PowerShell Compress-Archive uses backslashes and causes "Incompatible Archive"
 * Run: node create-zip.js
 */
const fs = require('fs');
const path = require('path');
const archiver = require('archiver');

const root = __dirname;
const items = ['openstuff-timeline.php', 'includes', 'build', 'README.md'];
const parentDir = path.join(root, '..');
const zipPath = path.join(parentDir, 'openstuff-timeline.zip');
const tempDir = path.join(parentDir, 'openstuff-timeline-zip-temp');
const pluginDir = path.join(tempDir, 'openstuff-timeline');

// Clean
if (fs.existsSync(zipPath)) fs.unlinkSync(zipPath);
if (fs.existsSync(tempDir)) fs.rmSync(tempDir, { recursive: true });

// Copy
fs.mkdirSync(pluginDir, { recursive: true });
for (const item of items) {
  const src = path.join(root, item);
  if (fs.existsSync(src)) {
    const dest = path.join(pluginDir, item);
    if (fs.statSync(src).isDirectory()) {
      fs.cpSync(src, dest, { recursive: true });
    } else {
      fs.copyFileSync(src, dest);
    }
  }
}

// Create ZIP with archiver (forward slashes = WordPress compatible)
const output = fs.createWriteStream(zipPath);
const archive = archiver('zip', { zlib: { level: 9 } });

archive.pipe(output);
archive.directory(pluginDir, 'openstuff-timeline');
archive.finalize();

output.on('close', () => {
  fs.rmSync(tempDir, { recursive: true });
  console.log('\nCreated:', zipPath);
  console.log('Size:', (fs.statSync(zipPath).size / 1024).toFixed(1), 'KB');
  console.log('(Uses forward slashes - compatible with WordPress)');
});

archive.on('error', (err) => {
  throw err;
});
