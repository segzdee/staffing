<?php

/**
 * Script to fix external links by adding rel="noopener noreferrer"
 * 
 * Usage: php scripts/fix-external-links.php
 */

$basePath = __DIR__.'/..';
$viewPath = $basePath.'/resources/views';

function fixExternalLinksInFile(string $filePath): int
{
    $content = file_get_contents($filePath);
    $originalContent = $content;
    $fixCount = 0;

    // Pattern to match target="_blank" without rel="noopener noreferrer"
    // Matches: target="_blank" or target='_blank' followed by optional whitespace and closing tag
    // Excludes if rel="noopener" or rel='noopener' already exists
    
    // Pattern 1: target="_blank" without rel attribute
    $pattern1 = '/(<a[^>]*target=["\']_blank["\'][^>]*)(?!.*rel=["\']noopener)([^>]*>)/i';
    $replacement1 = '$1 rel="noopener noreferrer"$2';
    
    // Pattern 2: target="_blank" with rel but missing noopener
    $pattern2 = '/(<a[^>]*target=["\']_blank["\'][^>]*rel=["\'])(?!.*noopener)([^"\']*)(["\'][^>]*>)/i';
    $replacement2 = '$1noopener noreferrer$3';
    
    // Apply fixes
    $content = preg_replace($pattern1, $replacement1, $content, -1, $count1);
    $fixCount += $count1;
    
    $content = preg_replace($pattern2, $replacement2, $content, -1, $count2);
    $fixCount += $count2;

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "Fixed {$fixCount} links in: {$filePath}\n";
    }

    return $fixCount;
}

function findBladeFiles(string $directory): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

echo "Fixing external links in Blade templates...\n\n";

$files = findBladeFiles($viewPath);
$totalFixed = 0;
$filesModified = 0;

foreach ($files as $file) {
    $fixed = fixExternalLinksInFile($file);
    if ($fixed > 0) {
        $totalFixed += $fixed;
        $filesModified++;
    }
}

echo "\n";
echo "Summary:\n";
echo "  Files scanned: ".count($files)."\n";
echo "  Files modified: {$filesModified}\n";
echo "  Total links fixed: {$totalFixed}\n";
