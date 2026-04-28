<?php
// Fix encoding for all blade files
$count = 0;
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('resources/views')
);

foreach ($files as $file) {
    if ($file->getExtension() === 'blade.php') {
        $path = $file->getPathname();
        $content = file_get_contents($path);

        // Fix specific corrupted characters
        $search = [
            'qualit' . chr(0xC3) . chr(0xA9),  // qualité corrupted
            'Cr' . chr(0xC3) . chr(0xA9) . 'er',  // Créer corrupted
            'Connex' . chr(0xC3) . chr(0xA9) . 'on',  // Connexion corrupted
            's' . chr(0xC3) . chr(0xA9) . 'curit' . chr(0xC3) . chr(0xA9),  // sécurité corrupted
            'acc' . chr(0xC3) . chr(0xA9) . 'der',  // accéder corrupted
            chr(0xC3) . chr(0xA9),  // é
            chr(0xC3) . chr(0xA8),  // è
            chr(0xC3) . chr(0xAA),  // ê
            chr(0xC3) . chr(0xA0),  // à
            chr(0xC3) . chr(0xBB),  // û
            chr(0xC3) . chr(0xAE),  // î
            chr(0xC3) . chr(0xA7),  // ç
            chr(0xC3) . chr(0xB4),  // ô
        ];

        $replace = [
            'qualité',
            'Créer',
            'Connexion',
            'sécurité',
            'accéder',
            'é',
            'è',
            'ê',
            'à',
            'û',
            'î',
            'ç',
            'ô',
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
