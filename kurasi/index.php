<?php
/**
 * SimpleAkunting Kurasi BUMDesa Landing Point
 * Melayani index.html secara langsung untuk kompatibilitas web server & Apache DirectoryIndex
 */
if (file_exists(__DIR__ . '/index.html')) {
    include __DIR__ . '/index.html';
} else {
    header('Location: index.html', true, 302);
}
exit;
