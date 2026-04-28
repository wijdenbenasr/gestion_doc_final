<?php
echo "Finding blade files with double-encoded UTF-8 characters...\n\n";

$files = glob('resources/views/**/*.blade.php', GLOB_BRACE);
$count = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Check for double-encoded UTF-8 (c383c2a9 = Ã©)
    if (strpos($content, chr(0xc3).chr(0x83).chr(0xc2).chr(0xa9)) !== false) {
        echo "Has issue: $file\n";
        $count++;
    }
}

echo "\nFound $count files with double-encoded characters.\n";
