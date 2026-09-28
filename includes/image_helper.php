<?php
// =========================================================================
// includes/image_helper.php — Dynamic WebP Auto-Compression Engine
// =========================================================================

/**
 * Automatically compresses JPG/PNG image and converts to optimized WebP format
 * Falls back safely to original if WebP isn't supported by server GD extension
 */
function processAndOptimizeImage($sourceFilePath, $targetWebpPath = null, $quality = 82) {
    if (!file_exists($sourceFilePath)) {
        return false;
    }

    if (!$targetWebpPath) {
        $info = pathinfo($sourceFilePath);
        $targetWebpPath = $info['dirname'] . '/' . $info['filename'] . '.webp';
    }

    $imageInfo = @getimagesize($sourceFilePath);
    if (!$imageInfo) {
        return $sourceFilePath;
    }

    $mime = $imageInfo['mime'];
    $image = null;

    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            if (function_exists('imagecreatefromjpeg')) {
                $image = @imagecreatefromjpeg($sourceFilePath);
            }
            break;
        case 'image/png':
            if (function_exists('imagecreatefrompng')) {
                $image = @imagecreatefrompng($sourceFilePath);
                if ($image) {
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                }
            }
            break;
        case 'image/webp':
            return $sourceFilePath; // Already webp
    }

    if ($image && function_exists('imagewebp')) {
        // Save optimized WebP image
        $success = @imagewebp($image, $targetWebpPath, $quality);
        imagedestroy($image);
        if ($success) {
            return $targetWebpPath;
        }
    }

    return $sourceFilePath;
}

// =========================================================================
// Universal Media & Artwork Resolvers (Dynamic Root-Relative Paths)
// =========================================================================
if (!function_exists('resolveMediaUrl')) {
    function resolveMediaUrl($path, $fallback = '') {
        if (empty($path)) {
            return $fallback;
        }
        $raw = trim($path);
        $raw = str_replace('\\', '/', $raw);
        $raw = preg_replace('#^https?://(localhost|127\.0\.0\.1)(:\d+)?#i', '', $raw);
        if (preg_match('#^https?://#i', $raw) || preg_match('#^data:image/#i', $raw)) {
            return $raw;
        }
        $clean = preg_replace('#^(\.\./)+#', '', $raw);
        $clean = preg_replace('#^(\./)+#', '', $clean);
        $clean = preg_replace('#/{2,}#', '/', $clean);
        return '/' . ltrim($clean, '/');
    }
}
