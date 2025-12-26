<?php

/**
 * SECURITY: Remove all secrets from documentation files
 * Replaces actual secrets with placeholders
 */

$docsDir = __DIR__.'/../docs';
$secrets = [
    '8rfN60oN51awZj8LLqNp' => 'YOUR_DATABASE_PASSWORD',
    'BYeRt00Hn3CKLojaGVys' => 'YOUR_REDIS_PASSWORD',
    'qbkaewaad7gauyd4nldo' => 'YOUR_REVERB_APP_KEY',
    'ylln4okatw3eypmj' => 'YOUR_DATABASE_USERNAME',
];

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($docsDir),
    RecursiveIteratorIterator::SELF_FIRST
);

$replaced = 0;

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'md') {
        $content = file_get_contents($file->getPathname());
        $original = $content;

        foreach ($secrets as $secret => $placeholder) {
            $content = str_replace($secret, $placeholder, $content);
        }

        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            $replaced++;
            echo "Updated: {$file->getPathname()}\n";
        }
    }
}

echo "\nReplaced secrets in {$replaced} files.\n";
