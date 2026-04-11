<?php

namespace OpenSoutheners\LaravelApiable\Documentation\Exporters;

use Illuminate\Support\Facades\Blade;

class MarkdownExporter implements ExporterInterface
{
    public function __construct(
        private readonly string $stub = 'protocol',
        private readonly string $outputPath = '',
    ) {
        //
    }

    /**
     * @param  OpenSouthenersLaravelApiableDocumentationResource[]  $resources
     * @return array<string, string>
     */
    public function export(array $resources): array
    {
        $files = [];

        foreach ($resources as $resource) {
            $filename = $this->slugify($resource->name);
            $extension = $this->stub === 'plain' ? 'md' : 'mdx';
            $outPath = $this->outputPath
                ? rtrim($this->outputPath, '/').'/'.$filename.'.'.$extension
                : $filename.'.'.$extension;

            $stubContents = $this->loadStub();

            $rendered = Blade::render($stubContents, [
                'resource' => $resource->toArray(),
            ], deleteCachedView: true);

            $files[$outPath] = $rendered;
        }

        return $files;
    }

    /**
     * Load the stub file, preferring user-published stubs over package stubs.
     */
    private function loadStub(): string
    {
        $extension = $this->stub === 'plain' ? 'md' : 'mdx';
        $stubName = $this->stub.'.'.$extension;

        // User-published stubs take priority
        $userStubPath = base_path('stubs/apiable/docs/'.$stubName);

        if (file_exists($userStubPath)) {
            return (string) file_get_contents($userStubPath);
        }

        // Fall back to package stubs
        $packageStubPath = __DIR__.'/../../../stubs/docs/'.$stubName;

        if (file_exists($packageStubPath)) {
            return (string) file_get_contents($packageStubPath);
        }

        throw new \RuntimeException("Apiable documentation stub [{$stubName}] not found. Run `php artisan vendor:publish --tag=apiable-stubs` to publish stubs.");
    }

    private function slugify(string $name): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name) ?? $name);
    }
}
