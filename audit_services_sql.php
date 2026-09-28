<?php
// =========================================================================
// audit_services_sql.php — Deduplicate Services Table & Fix Service Logos
// =========================================================================
require_once __DIR__ . '/config.php';

echo "=== DEEP AUDIT & DE-DUPLICATION OF SERVICES TABLE ===\n";

$stmt = $pdo->query("SELECT * FROM services ORDER BY name ASC, id ASC");
$all_services = $stmt->fetchAll();

$seen_names = [];
$duplicates_removed = 0;
$logos_cleaned = 0;

foreach ($all_services as $s) {
    $clean_name = trim(preg_replace('/\s+/', ' ', strtolower($s['name'])));
    
    // 1. Clean duplicate domain prefixes in logo_url if any
    $logo = $s['logo_url'];
    if ($logo && preg_match('/^https?:\/\/[^\/]+\/(https?:\/\/.*)$/i', $logo, $m)) {
        $logo = $m[1];
        $up = $pdo->prepare("UPDATE services SET logo_url = :l WHERE id = :id");
        $up->execute([':l' => $logo, ':id' => $s['id']]);
        $logos_cleaned++;
        echo "  [FIXED LOGO ID {$s['id']}] {$s['logo_url']} => {$logo}\n";
    }

    // 2. Check for duplicate name
    if (isset($seen_names[$clean_name])) {
        $existing_id = $seen_names[$clean_name]['id'];
        $existing_logo = $seen_names[$clean_name]['logo_url'];
        
        // If current service has a logo and existing doesn't, copy logo to existing
        if (!empty($logo) && empty($existing_logo)) {
            $pdo->prepare("UPDATE services SET logo_url = :l WHERE id = :id")
                ->execute([':l' => $logo, ':id' => $existing_id]);
            $seen_names[$clean_name]['logo_url'] = $logo;
            echo "  [MERGED LOGO] Transferred logo '{$logo}' from duplicate ID {$s['id']} to primary ID {$existing_id}\n";
        }

        // Disable or delete duplicate entry
        $pdo->prepare("DELETE FROM services WHERE id = :id")->execute([':id' => $s['id']]);
        $duplicates_removed++;
        echo "  [DELETED DUPLICATE SERVICE ID {$s['id']}] Name: '{$s['name']}' (Kept ID {$existing_id})\n";
    } else {
        $seen_names[$clean_name] = $s;
    }
}

echo "\nSummary of Service Audit:\n";
echo "  - Total Services Processed: " . count($all_services) . "\n";
echo "  - Duplicate Services Deleted: {$duplicates_removed}\n";
echo "  - Service Logo URLs Cleaned: {$logos_cleaned}\n";
echo "  - Unique Active Services Remaining: " . count($seen_names) . "\n";
