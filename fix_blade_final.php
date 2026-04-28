<?php
echo "Fixing double-encoded UTF-8 characters in blade files...\n";

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('resources/views')
);

$count = 0;
foreach ($files as $file) {
    if ($file->getExtension() === 'blade.php') {
        $path = $file->getPathname();
        $content = file_get_contents($path);
        
        // Fix double-encoded UTF-8 by using hex replacement
        $search = array(
            '\xc3\x83\xc2\xa9', // Ã© -> é
            '\xc3\x83\xc2\xa8', // Ã¨ -> è
            '\xc3\x83\xc2\xaa', // Ãª -> ê
            '\xc3\x83\xc2\xa0', // Ã  -> à
            '\xc3\x83\xc2\xbb', // Ã» -> û
            '\xc3\x83\xc2\xae', // Ã® -> î
            '\xc3\x83\xc2\xa7', // Ã§ -> ç
            '\xc3\x83\xc2\xb4', // Ã´ -> ô
        );
        
        $replace = array(
            '\xc3\xa9', // é
            '\xc3\xa8', // è
            '\xc3\xaa', // ê
            '\xc3\xa0', // à
            '\xc3\xbb', // û
            '\xc3\xae', // î
            '\xc3\xa7', // ç
            '\xc3\xb4', // ô
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
