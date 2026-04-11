<?php

namespace OpenSoutheners\LaravelApiable\Tests\Documentation;

use OpenSoutheners\LaravelApiable\Documentation\DocblockExtractor;
use PHPUnit\Framework\TestCase;

class DocblockExtractorTest extends TestCase
{
    public function test_empty_docblock_returns_empty_string(): void
    {
        $this->assertSame('', DocblockExtractor::extractSummary(''));
    }

    public function test_single_line_summary(): void
    {
        $docblock = '/** Get a list of posts. */';
        $this->assertSame('Get a list of posts.', DocblockExtractor::extractSummary($docblock));
    }

    public function test_multi_line_summary(): void
    {
        $docblock = <<<'DOC'
        /**
         * Get a paginated list of posts.
         *
         * Returns all published posts, ordered by creation date.
         *
         * @param  int  $page
         * @return void
         */
        DOC;

        $summary = DocblockExtractor::extractSummary($docblock);
        $this->assertSame('Get a paginated list of posts.', $summary);
    }

    public function test_stops_before_at_tags(): void
    {
        $docblock = <<<'DOC'
        /**
         * Short summary here.
         * @param string $foo
         * @return void
         */
        DOC;

        $summary = DocblockExtractor::extractSummary($docblock);
        $this->assertSame('Short summary here.', $summary);
    }

    public function test_no_docblock_from_reflection_returns_empty_string(): void
    {
        $reflection = new \ReflectionFunction(static function () {});
        $this->assertSame('', DocblockExtractor::fromReflection($reflection));
    }

    public function test_from_reflection_extracts_summary(): void
    {
        $controller = new class {
            /**
             * List all resources.
             *
             * @return void
             */
            public function index(): void {}
        };

        $reflection = new \ReflectionMethod($controller, 'index');
        $this->assertSame('List all resources.', DocblockExtractor::fromReflection($reflection));
    }
}
