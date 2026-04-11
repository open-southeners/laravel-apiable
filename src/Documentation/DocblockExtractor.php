<?php

namespace OpenSoutheners\LaravelApiable\Documentation;

use ReflectionFunctionAbstract;

/**
 * Regex-based PHPDoc summary extractor. No runtime phpstan/phpdoc-parser dependency.
 */
class DocblockExtractor
{
    /**
     * Extract the summary (first paragraph before any @tag) from a docblock string.
     */
    public static function extractSummary(string $docblock): string
    {
        // Strip /** and */
        $stripped = preg_replace('/^\s*\/\*\*\s*/', '', $docblock) ?? '';
        $stripped = preg_replace('/\s*\*\/\s*$/', '', $stripped) ?? '';

        // Remove leading " * " from each line
        $lines = explode("\n", $stripped);
        $cleaned = array_map(static function (string $line): string {
            return preg_replace('/^\s*\*\s?/', '', $line) ?? '';
        }, $lines);

        // Join and split into paragraphs at blank lines
        $text = implode("\n", $cleaned);

        // Stop at the first @tag line
        $text = preg_replace('/@\w+.*$/ms', '', $text) ?? $text;

        // Trim and collapse internal whitespace
        $text = trim($text);

        // Return first paragraph only
        $paragraphs = preg_split('/\n\s*\n/', $text, 2);

        return trim($paragraphs[0] ?? '');
    }

    /**
     * Extract the PHPDoc summary from a reflected function or method.
     */
    public static function fromReflection(ReflectionFunctionAbstract $reflector): string
    {
        $docblock = $reflector->getDocComment();

        if ($docblock === false || $docblock === '') {
            return '';
        }

        return static::extractSummary($docblock);
    }
}
