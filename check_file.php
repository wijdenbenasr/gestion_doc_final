<?php
echo "Checking file encoding...\n";

$line = file('resources/views/layouts/app.blade.php')[545]; // Line 546 (0-indexed)
echo "Line 546: " . $line;

// Check if the line contains the correct UTF-8 é (c3a9)
if (strpos($line, chr(0xC3).chr(0xA9)) !== false) {
    echo "\nLine contains correct UTF-8 'é' (c3a9)\n";
} else if (strpos($line, chr(0xC3).chr(0x83).chr(0xC2).chr(0xA9)) !== false) {
    echo "\nLine contains double-encoded 'é' (c383c2a9) - needs fixing\n";
} else {
    echo "\nLine does not contain expected characters\n";
    echo "Hex: " . bin2hex($line) . "\n";
}
