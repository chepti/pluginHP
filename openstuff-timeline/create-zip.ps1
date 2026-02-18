# Create distribution ZIP for OpenStuff Timeline plugin
# Excludes node_modules, src, and dev files - only what WP needs to run

$ErrorActionPreference = "Stop"
$sourceDir = $PSScriptRoot
$parentDir = Split-Path $sourceDir -Parent
# Use unique temp name - NOT "openstuff-timeline" (that's the source folder!)
$tempDir = Join-Path $parentDir "openstuff-timeline-zip-temp"
$pluginDir = Join-Path $tempDir "openstuff-timeline"
$zipPath = Join-Path $parentDir "openstuff-timeline.zip"

# Clean previous
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
if (Test-Path $tempDir) { Remove-Item $tempDir -Recurse -Force }

# Create temp structure: temp/openstuff-timeline/ (zip root = openstuff-timeline)
New-Item -ItemType Directory -Force -Path $pluginDir | Out-Null

# Copy only distribution files
$items = @(
    "openstuff-timeline.php",
    "includes",
    "build",
    "README.md"
)

foreach ($item in $items) {
    $src = Join-Path $sourceDir $item
    if (Test-Path $src) {
        $dest = Join-Path $pluginDir $item
        if (Test-Path $src -PathType Container) {
            Copy-Item $src -Destination $dest -Recurse -Force
        } else {
            Copy-Item $src -Destination $dest -Force
        }
    }
}

# Create ZIP - root must be openstuff-timeline/ for WP to find the plugin
Compress-Archive -Path $pluginDir -DestinationPath $zipPath -Force

# Cleanup temp
Remove-Item $tempDir -Recurse -Force

Write-Host "Created: $zipPath" -ForegroundColor Green
Write-Host "Contents: openstuff-timeline.php, includes/, build/, README.md" -ForegroundColor Cyan
