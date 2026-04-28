<?php
echo "Starting encoding fix...\n";

$count = 0;
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('resources/views')
);

foreach ($files as $file) {
    if ($file->getExtension() === 'blade.php') {
        $path = $file->getPathname();
        $content = file_get_contents($path);
        
        // Fix double-encoded UTF-8 characters
        // The pattern in the file is: c383 c2a9 (double-encoded é)
        $search = array(
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xA9), // Ã© -> é
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xA8), // Ã¨ -> è
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xAA), // Ãª -> ê
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xA0), // Ã  -> à
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xBB), // Ã» -> û
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xAE), // Ã® -> î
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xA7), // Ã§ -> ç
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xB4), // Ã´ -> ô
        );
        
        $replace = array(
            chr(0xC3).chr(0xA9), // é
            chr(0xC3).chr(0xA8), // è
            chr(0xC3).chr(0xAA), // ê
            chr(0xC3).chr(0xA0), // à
            chr(0xC3).chr(0xBB), // û
            chr(0xC3).chr(0xAE), // î
            chr(0xC3).chr(0xA7), // ç
            chr(0xC3).chr(0xB4), // ô
        );
        
        $newContent = str_replace($search, $replace, $content);
        
        if ($newContent !== $content) {
            file_put_contents($path, $newContent);
            $count++;
            echo "Fixed: $path\n";
        }
    }
}

echo "\nFixed $count files.\n";
