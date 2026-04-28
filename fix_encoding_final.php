<?php
echo "Starting encoding fix...\n";

// Fix double-encoded UTF-8 characters
// When UTF-8 bytes are incorrectly interpreted as Windows-1252 and then saved as UTF-8 again,
// characters like 'é' (UTF-8: C3A9) become 'Ã©' (UTF-8: C383 C2A9)

$count = 0;
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('resources/views')
);

foreach ($files as $file) {
    if ($file->getExtension() === 'blade.php') {
        $path = $file->getPathname();
        $content = file_get_contents($path);

        // Fix double-encoded characters
        // Pattern: C383 C2A9 should become C3A9 (é)
        $search = [
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xA9), // é
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xA8), // è
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xAA), // ê
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xA0), // à
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xBB), // û
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xAE), // î
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xA7), // ç
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xB4), // ô
        ];

        $replace = [
            chr(0xC3).chr(0xA9), // é
            chr(0xC3).chr(0xA8), // è
            chr(0xC3).chr(0xAA), // ê
            chr(0xC3).chr(0xA0), // à
            chr(0xC3).chr(0xBB), // û
            chr(0xC3).chr(0xAE), // î
            chr(0xC3).chr(0xA7), // ç
            chr(0xC3).chr(0xB4), // ô
        ];

        $newContent = str_replace($search, $replace, $content);

        if ($newContent !== $content) {
            file_put_contents($path, $newContent);
            $count++;
            echo "Fixed: $path\n";
        }
    }
}

echo "\nFixed $count files.\n";
