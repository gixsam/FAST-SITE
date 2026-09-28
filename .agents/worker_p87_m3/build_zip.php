<?php
$zipFile = __DIR__ . '/../../fastsite_phase87.zip';
if (file_exists($zipFile)) {
    unlink($zipFile);
}

$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("ERROR: Cannot create zip file at $zipFile\n");
}

$filesToArchive = [
    'includes/user_sidebar.php',
    'user/dashboard.php',
    'assets/css/user.css',
    'partner/nav.php',
    'partner/dashboard.php',
    'partner/index.php',
    'partner/logout.php',
    'partner/product_add.php',
    'partner/product_edit.php',
    'partner/product_delete.php',
    'partner/profile.php',
    'partner/api_docs.php',
    'DEPLOYMENT_GUIDE.txt'
];

$rootDir = realpath(__DIR__ . '/../../');

foreach ($filesToArchive as $relPath) {
    $fullPath = $rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
    if (!file_exists($fullPath)) {
        die("ERROR: File not found: $fullPath\n");
    }
    // Force Unix forward slash path inside zip
    $zipEntryName = str_replace('\\', '/', $relPath);
    $zip->addFile($fullPath, $zipEntryName);
    echo "Added: $zipEntryName (from $fullPath)\n";
}

$zip->close();
echo "\nZip created successfully: " . realpath($zipFile) . " (" . filesize($zipFile) . " bytes)\n";

// Verification: Read back entries to ensure Unix paths and integrity
echo "\n--- VERIFYING ARCHIVE ENTRIES ---\n";
$verifyZip = new ZipArchive();
if ($verifyZip->open($zipFile) === true) {
    $count = $verifyZip->numFiles;
    echo "Total files in archive: $count\n";
    $hasBackslash = false;
    for ($i = 0; $i < $count; $i++) {
        $stat = $verifyZip->statIndex($i);
        $name = $stat['name'];
        if (strpos($name, '\\') !== false) {
            $hasBackslash = true;
            echo "ERROR: Entry has backslash: $name\n";
        } else {
            echo sprintf("  [%2d] %-30s | Size: %6d bytes | CRC: %08X\n", $i + 1, $name, $stat['size'], $stat['crc']);
        }
    }
    $verifyZip->close();
    if (!$hasBackslash && $count === count($filesToArchive)) {
        echo "ARCHIVE INTEGRITY CONFIRMED: All entries use normalized forward slashes ('/').\n";
    } else {
        echo "ARCHIVE VERIFICATION FAILED!\n";
    }
} else {
    echo "ERROR: Cannot open archive for verification!\n";
}
