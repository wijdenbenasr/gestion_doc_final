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
        
        $search = array(
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xA9),
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xA8),
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xAA),
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xA0),
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xBB),
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xAE),
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xA7),
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xB4)
        );
        
        $replace = array(
            chr(0xC3).chr(0xA9),
            chr(0xC3).chr(0xA8),
            chr(0xC3).chr(0xAA),
            chr(0xC3).chr(0xA0),
            chr(0xC3).chr(0xBB),
            chr(0xC3).chr(0xAE),
            chr(0xC3).chr(0xA7),
            chr(0xC3).chr(0xB4)
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
