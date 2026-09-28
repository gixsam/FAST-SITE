<?php
$source = __DIR__;
$destination = __DIR__ . '/fastsite_phase62_v3.zip';

if (file_exists($destination)) {
    unlink($destination);
}

$zip = new ZipArchive();
if (!$zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
    die("Failed to create zip file");
}

$source = realpath($source);

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $item) {
    $realPath = $item->getRealPath();
    // Exclude unwanted directories
    if (strpos($realPath, DIRECTORY_SEPARATOR . '.git') !== false) continue;
    if (strpos($realPath, DIRECTORY_SEPARATOR . '.gemini') !== false) continue;
    if (strpos($realPath, DIRECTORY_SEPARATOR . 'FastSiteApp') !== false) continue;
    if (strpos($realPath, DIRECTORY_SEPARATOR . 'whatsapp-bot') !== false) continue;
    if (strpos($realPath, DIRECTORY_SEPARATOR . '.agents') !== false) continue;
    if (strpos($realPath, DIRECTORY_SEPARATOR . '_build_archives') !== false) continue;
    if (strpos($realPath, DIRECTORY_SEPARATOR . '_dev_tools') !== false) continue;
    if (strpos($realPath, DIRECTORY_SEPARATOR . 'APK FILE USER') !== false) continue;
    if (strpos($realPath, DIRECTORY_SEPARATOR . 'apk file ADMIN') !== false) continue;
    if (strpos($realPath, DIRECTORY_SEPARATOR . 'uploads') !== false) continue;
    if (strpos($realPath, DIRECTORY_SEPARATOR . 'AFFILIATE PARTNERS LOGO') !== false) continue;
    if (strpos($realPath, DIRECTORY_SEPARATOR . 'MEDIA PHOTO') !== false) continue;
    if (strpos($realPath, DIRECTORY_SEPARATOR . 'SQL FILE') !== false) continue;
    
    // Exclude unwanted files
    if (strpos($realPath, '.zip') !== false) continue;
    if (strpos($realPath, '.apk') !== false) continue;
    if (strpos($realPath, '.db') !== false) continue;
    if (strpos($realPath, 'create_project_zip.php') !== false) continue;
    
    $relativePath = str_replace($source . DIRECTORY_SEPARATOR, '', $realPath);
    $relativePath = str_replace('\\', '/', $relativePath); // Forward slashes for Hostinger

    if ($item->isDir()) {
        $zip->addEmptyDir($relativePath);
    } else {
        $zip->addFile($realPath, $relativePath);
    }
}

$zip->close();
echo "Successfully created $destination";
