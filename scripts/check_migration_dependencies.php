<?php

/**
 * Migration Dependency Checker
 * 
 * Verifies all foreign key dependencies are correctly ordered
 */

$migrations = glob(__DIR__ . '/../database/migrations/*.php');
$tableCreation = [];
$dependencies = [];

foreach ($migrations as $file) {
    $basename = basename($file);
    $timestamp = substr($basename, 0, 19);
    $content = file_get_contents($file);
    
    // Find table creation
    if (preg_match("/Schema::create\(['\"]([^'\"]+)['\"]/", $content, $matches)) {
        $tableCreation[$matches[1]] = $timestamp;
    }
    
    // Find foreign key references (exclude method calls like ->cascadeOnDelete())
    preg_match_all("/->constrained\(['\"]([^'\"]+)['\"]\)/", $content, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $refTable) {
            // Skip if it looks like a method call
            if (strpos($refTable, '->') !== false || strpos($refTable, '(') !== false) {
                continue;
            }
            if (!isset($dependencies[$timestamp])) {
                $dependencies[$timestamp] = [];
            }
            $dependencies[$timestamp][] = $refTable;
        }
    }
    
    // Find foreign() references
    preg_match_all("/->references\(['\"]id['\"]\)->on\(['\"]([^'\"]+)['\"]\)/", $content, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $refTable) {
            if (!isset($dependencies[$timestamp])) {
                $dependencies[$timestamp] = [];
            }
            $dependencies[$timestamp][] = $refTable;
        }
    }
}

// Check for issues
$issues = [];
foreach ($dependencies as $timestamp => $refs) {
    // Get content for this timestamp
    $migrationContent = $migrationContents[$timestamp] ?? '';
    
    foreach (array_unique($refs) as $refTable) {
        // Check if table is created in the same migration file
        $createdInSameFile = preg_match("/Schema::create\(['\"]" . preg_quote($refTable, '/') . "['\"]/", $migrationContent);
        
        if ($createdInSameFile) {
            continue; // Table is created in same file, no issue
        }
        
        if (!isset($tableCreation[$refTable])) {
            $issues[] = "⚠️  $timestamp references '$refTable' which is never created";
        } else {
            $refTimestamp = $tableCreation[$refTable];
            if ($refTimestamp > $timestamp) {
                $issues[] = "❌ $timestamp references '$refTable' (created at $refTimestamp) - ORDER ISSUE";
            }
        }
    }
}

if (empty($issues)) {
    echo "✅ All foreign key dependencies are correctly ordered\n";
} else {
    echo "Found " . count($issues) . " dependency issues:\n\n";
    foreach ($issues as $issue) {
        echo "$issue\n";
    }
}

echo "\n📊 Summary:\n";
echo "  - Tables created: " . count($tableCreation) . "\n";
echo "  - Migrations with dependencies: " . count($dependencies) . "\n";
echo "  - Issues found: " . count($issues) . "\n";
