<?php
// Fix double-encoded UTF-8 characters in blade files
$count = 0;
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('resources/views')
);

foreach ($files as $file) {
    if ($file->getExtension() === 'blade.php') {
        $path = $file->getPathname();
        $content = file_get_contents($path);

        // Fix double-encoded UTF-8 characters
        // When UTF-8 is encoded twice, é (c3a9) becomes c383 c2a9
        $replacements = [
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xA9) => chr(0xC3).chr(0xA9), // é
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xA8) => chr(0xC3).chr(0xA8), // è
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xAA) => chr(0xC3).chr(0xAA), // ê
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xA0) => chr(0xC3).chr(0xA0), // à
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xBB) => chr(0xC3).chr(0xBB), // û
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xAE) => chr(0xC3).chr(0xAE), // î
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xA7) => chr(0xC3).chr(0xA7), // ç
            chr(0xC3).chr(0x83).chr(0xC2).chr(0xB4) => chr(0xC3).chr(0xB4), // ô
        ];

        $newContent = str_replace(array_keys($replacements), array_values($replacements), $content);

        if ($newContent !== $content) {
            file_put_contents($path, $newContent);
            $count++;
            echo "Fixed: $path\n";
        }
    }
}

echo "\nFixed $count files.\n";
