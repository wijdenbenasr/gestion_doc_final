<?php
$lines = file('resources/views/layouts/app.blade.php');
$line = $lines[545];  // Line 546 (0-indexed)
echo "Line 546: " . $line . "\n";
echo "Hex: " . bin2hex($line) . "\n";

// Find the position of "qualit"
$pos = strpos($line, 'qualit');
if ($pos !== false) {
    echo "Found 'qualit' at position: $pos\n";
    echo "Bytes after 'qualit': " . bin2hex(substr($line, $pos + 7, 10)) . "\n";
}
