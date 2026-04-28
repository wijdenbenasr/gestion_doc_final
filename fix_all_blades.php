<?php
echo "Fixing double-encoded UTF-8 characters in all blade files...\n";

$count = 0;
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('resources/views')
);

foreach ($files as $file) {
    if ($file->getExtension() === 'blade.php') {
        $path = $file->getPathname();
        $content = file_get_contents($path);
        
        // Fix double-encoded UTF-8: c383c2a9 -> c3a9 (é)
        $search = array(
            chr(0xc3).chr(0x83).chr(0xc2).chr(0xa9), // Ã© -> é
            chr(0xc3).chr(0x83).chr(0xc2).chr(0xa8), // Ã¨ -> è
            chr(0xc3).chr(0x83).chr(0xc2).chr(0xaa), // Ãª -> ê
            chr(0xc3).chr(0x83).chr(0xc2).chr(0xa0), // Ã  -> à
            chr(0xc3).chr(0x83).chr(0xc2).chr(0xbb), // Ã» -> û
            chr(0xc3).chr(0x83).chr(0xc2).chr(0xae), // Ã® -> î
            chr(0xc3).chr(0x83).chr(0xc2).chr(0xa7), // Ã§ -> ç
            chr(0xc3).chr(0x83).chr(0xc2).chr(0xb4), // Ã´ -> ô
        );
        
        $replace = array(
            chr(0xc3).chr(0xa9), // é
            chr(0xc3).chr(0xa8), // è
            chr(0xc3).chr(0xaa), // ê
            chr(0xc3).chr(0xa0), // à
            chr(0xc3).chr(0xbb), // û
            chr(0xc3).chr(0xae), // î
            chr(0xc3).chr(0xa7), // ç
            chr(0xc3).chr(0xb4), // ô
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
