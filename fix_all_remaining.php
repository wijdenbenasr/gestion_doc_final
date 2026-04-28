<?php
echo "Fixing all remaining blade files with double-encoded UTF-8 characters...\n\n";

$files = array(
    'resources/views/admin/dashboard.blade.php',
    'resources/views/admin/documents.blade.php',
    'resources/views/auth/login.blade.php',
    'resources/views/components/delete-modal.blade.php',
    'resources/views/documents/approver-index.blade.php',
    'resources/views/documents/validator-index.blade.php',
    'resources/views/layouts/app.blade.php',
);

$count = 0;
foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Fix double-encoded UTF-8 characters
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
            file_put_contents($file, $newContent);
            $count++;
            echo "Fixed: $file\n";
        }
    }
}

echo "\nFixed $count files.\n";
