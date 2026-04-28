<?php
echo "Starting encoding fix using mb_convert_encoding...\n";

$count = 0;
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('resources/views')
);

foreach ($files as $file) {
    if ($file->getExtension() === 'blade.php') {
        $path = $file->getPathname();
        $content = file_get_contents($path);

        // Try to fix double-encoded UTF-8
        // First, convert from UTF-8 to Windows-1252 (which interprets the double-encoded bytes correctly)
        // Then convert back to UTF-8
        $converted = mb_convert_encoding($content, 'Windows-1252', 'UTF-8');
        $fixed = mb_convert_encoding($converted, 'UTF-8', 'Windows-1252');

        // If that didn't work, try another approach
        if ($converted === false || $fixed === false) {
            // Try to decode HTML entities
            $fixed = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if ($fixed !== $content && $fixed !== false) {
            file_put_contents($path, $fixed);
            $count++;
            echo "Fixed: $path\n";
        }
    }
}

echo "\nFixed $count files.\n";
