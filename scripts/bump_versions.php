<?php
// Simple version bumper for WP plugins in this workspace.
// - Bumps patch version (x.y.z -> x.y.(z+1))
// - Updates both the plugin file header "Version:" and the VERSION constant define inside the file

function bump_version_string(string $v): string {
    if (!preg_match('/^(\d+)\.(\d+)\.(\d+)$/', trim($v), $m)) {
        // fallback for x.y
        if (preg_match('/^(\d+)\.(\d+)$/', trim($v), $m2)) {
            return $m2[1] . '.' . $m2[2] . '.1';
        }
        // if unparsable, default to 0.1.0
        return '0.1.0';
    }
    $major = (int)$m[1];
    $minor = (int)$m[2];
    $patch = (int)$m[3] + 1;
    return $major . '.' . $minor . '.' . $patch;
}

function bump_plugin_file(string $file): bool {
    $contents = file_get_contents($file);
    if ($contents === false) return false;

    $updated = false;

    // 1) Header Version: line
    if (preg_match('/^\s*\*\s*Version:\s*(.+)$/mi', $contents, $m)) {
        $old = trim($m[1]);
        $new = bump_version_string($old);
        if ($new !== $old) {
            $contents = preg_replace('/(^\s*\*\s*Version:\s*)' . preg_quote($old, '/') . '(\s*)$/mi', '$1' . $new . '$2', $contents, 1);
            $updated = true;
        }
    }

    // 2) define('..._VERSION', 'x.y.z')
    if (preg_match_all('/define\s*\(\s*\' . "'" . '([A-Z0-9_]+_VERSION)' . "'" . '\s*,\s*\' . "'" . '([0-9\.]+)' . "'" . '\s*\)\s*;/', $contents, $defs, PREG_SET_ORDER)) {
        foreach ($defs as $def) {
            $const = $def[1];
            $old = $def[2];
            $new = bump_version_string($old);
            if ($new !== $old) {
                $contents = preg_replace('/(define\s*\(\s*\' . "'" . preg_quote($const, '/') . "'" . '\s*,\s*\' . "'" . ')' . preg_quote($old, '/') . '(\' . "'" . '\s*\)\s*;)/', '$1' . $new . '$2', $contents, 1);
                $updated = true;
            }
        }
    }

    if ($updated) {
        file_put_contents($file, $contents);
    }
    return $updated;
}

// Discover plugin main files (these contain the header block and VERSION define)
$candidates = [
    'homer-patuach-grid/homer-patuach-grid.php',
    'homer-patuach-collections/homer-patuach-collections.php',
    'homer-patuach-bp-tweaks/homer-patuach-bp-tweaks.php',
];

$any = false;
foreach ($candidates as $file) {
    if (file_exists($file)) {
        $changed = bump_plugin_file($file);
        if ($changed) {
            echo "Bumped: {$file}\n";
            $any = true;
        }
    }
}

if (!$any) {
    echo "No versions bumped.\n";
}
exit(0);
?>

