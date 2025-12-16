<?php

/**
 * Migration Dependency Mapper
 * 
 * Maps all foreign key dependencies to ensure migrations run in correct order
 */

$migrations = glob('database/migrations/*.php');
$dependencies = [];
$tableCreation = [];

foreach ($migrations as $file) {
    $basename = basename($file);
    $timestamp = substr($basename, 0, 19); // YYYY_MM_DD_HHMMSS
    
    $content = file_get_contents($file);
    
    // Find table creation
    if (preg_match("/Schema::create\(['\"]([^'\"]+)['\"]/", $content, $matches)) {
        $tableCreation[$matches[1]] = $timestamp;
    }
    
    // Find foreign key references
    preg_match_all("/->constrained\(['\"]?([^'\"]+)['\"]?\)/", $content, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $refTable) {
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

// Check for missing dependencies
$issues = [];
foreach ($dependencies as $timestamp => $refs) {
    foreach ($refs as $refTable) {
        if (!isset($tableCreation[$refTable])) {
            $issues[] = "Migration $timestamp references table '$refTable' which is never created";
        } else {
            $refTimestamp = $tableCreation[$refTable];
            if ($refTimestamp > $timestamp) {
                $issues[] = "Migration $timestamp references '$refTable' (created at $refTimestamp) - ORDER ISSUE";
            }
        }
    }
}

if (empty($issues)) {
    echo "✓ All foreign key dependencies are correctly ordered\n";
} else {
    echo "✗ Found " . count($issues) . " dependency issues:\n";
    foreach ($issues as $issue) {
        echo "  - $issue\n";
    }
}

echo "\nTable creation order:\n";
asort($tableCreation);
foreach ($tableCreation as $table => $timestamp) {
    echo "  $timestamp: $table\n";
}
