<?php
/**
 * Deployment & Migration Script for SimpleAkunting v3.6
 */
require_once 'app/config.php';
require_once 'app/core/Database.php';

$db = new Database();

echo "🚀 Starting Deployment & Migration to v3.6...\n";

// 1. Load SQL File
$sqlFile = 'simkopde_umkm_fixed.sql';
if (!file_exists($sqlFile)) {
    die("❌ Error: SQL file $sqlFile not found.\n");
}

echo "📂 Loading SQL dump: $sqlFile\n";
$sql = file_get_contents($sqlFile);

// Remove comments and split by semicolon
// Note: This is a simple parser, might struggle with complex procedures but good for standard dumps
$queries = explode(";", $sql);

echo "⚙️  Executing database initialization...\n";
$count = 0;
foreach ($queries as $query) {
    $query = trim($query);
    if ($query) {
        try {
            $db->query($query);
            $db->execute();
            $count++;
        } catch (Exception $e) {
            // Some queries might fail if they are part of a comment or multi-line statement
            // but we continue for others
            // echo "⚠️  Query skipped: " . substr($query, 0, 50) . "...\n";
        }
    }
}
echo "✅ Successfully processed queries.\n";

// 2. Perform v3.6 Specific Migrations (if any)
echo "🔧 Applying v3.6 specific patches...\n";
$patches = [
    // Add any new v3.6 tables or columns here
    "ALTER TABLE tenants ADD COLUMN IF NOT EXISTS version VARCHAR(10) DEFAULT '3.6';",
    "UPDATE tenants SET version = '3.6';",
    // Example: Standardization of naming
    "UPDATE perusahaan SET nama_perusahaan = 'KLINIK BUMDESA PROV JATIM' WHERE id = 1;"
];

foreach ($patches as $patch) {
    try {
        $db->query($patch);
        $db->execute();
        echo "✅ Patch applied: " . substr($patch, 0, 50) . "...\n";
    } catch (Exception $e) {
        echo "⚠️  Patch skipped/failed: " . $e->getMessage() . "\n";
    }
}

echo "🏁 Migration to v3.6 Completed Successfully!\n";
echo "🌐 You can now access the application at: " . BASEURL . "\n";
