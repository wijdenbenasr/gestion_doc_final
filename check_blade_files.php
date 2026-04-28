<?php
echo "Checking blade files for correct UTF-8 characters...\n\n";

$files = array(
    'resources/views/auth/register.blade.php',
    'resources/views/auth/forgot-password.blade.php',
    'resources/views/auth/reset-password.blade.php',
    'resources/views/auth/verify-email.blade.php',
    'resources/views/auth/change-password.blade.php',
    'resources/views/layouts/app.blade.php',
    'resources/views/dashboard.blade.php',
);

$allOk = true;
foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $hasIssue = false;
        
        // Check for double-encoded characters
        if (strpos($content, chr(0xC3).chr(0x83).chr(0xC2).chr(0xA9)) !== false) {
            echo "$file: ISSUE (double-encoded é)\n";
            $hasIssue = true;
        }
        if (strpos($content, 'qualit'.chr(0xC3).chr(0xA9)) !== false) {
            echo "$file: OK (has qualité)\n";
        } else if (!$hasIssue) {
            echo "$file: ISSUE (missing qualité)\n";
            $allOk = false;
        }
    }
}

echo "\nDone.\n";
