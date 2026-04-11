<?php

namespace OpenSoutheners\LaravelApiable\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use OpenSoutheners\LaravelApiable\Documentation\Exporters\MarkdownExporter;
use OpenSoutheners\LaravelApiable\Documentation\Exporters\OpenApiExporter;
use OpenSoutheners\LaravelApiable\Documentation\Exporters\PostmanExporter;
use OpenSoutheners\LaravelApiable\Documentation\Generator;

use function Laravel\Prompts\select;

class ApiableDocsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apiable:docs
        {--format=* : Output format(s): markdown, postman, openapi (repeatable)}
        {--stub=protocol : Markdown stub name (protocol|plain)}
        {--only=* : Only include routes matching these patterns}
        {--exclude=* : Exclude routes matching these patterns}
        {--path= : Override the output directory}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate API documentation from route attributes (Postman, Markdown/MDX, OpenAPI 3.1)';

    public function handle(Router $router, Filesystem $files): int
    {
        $formats = $this->resolveFormats();

        if (empty($formats)) {
            $this->components->error('No output format selected.');

            return self::FAILURE;
        }

        $outputPath = $this->option('path')
            ?? config('apiable.documentation.output_path')
            ?? storage_path('exports/apiable');

        $stub = (string) ($this->option('stub')
            ?? config('apiable.documentation.default_stub', 'protocol'));

        /** @var array<string> $only */
        $only = (array) $this->option('only');
        /** @var array<string> $exclude */
        $exclude = (array) $this->option('exclude');

        $this->components->info('Generating API documentation…');

        $generator = new Generator($router);
        $resources = $generator->generate($only, $exclude);

        if (empty($resources)) {
            $this->components->warn('No documented resources found. Annotate controllers with #[DocumentedResource].');

            return self::SUCCESS;
        }

        $generated = [];

        foreach ($formats as $format) {
            $exporter = $this->makeExporter($format, $stub, (string) $outputPath);
            $outputs = $exporter->export($resources);

            foreach ($outputs as $filePath => $contents) {
                $fullPath = str_starts_with($filePath, '/') ? $filePath : $outputPath.'/'.$filePath;
                $files->ensureDirectoryExists(dirname($fullPath));
                $files->put($fullPath, $contents);
                $generated[] = [$format, $fullPath];
            }
        }

        $this->newLine();
        $this->components->twoColumnDetail('<fg=green>Format</>', '<fg=green>Output file</>');

        foreach ($generated as [$format, $filePath]) {
            $this->components->twoColumnDetail($format, $filePath);
        }

        $this->newLine();
        $this->components->info('Done.');

        return self::SUCCESS;
    }

    /**
     * Resolve the list of output formats, prompting interactively when none specified.
     *
     * @return list<string>
     */
    private function resolveFormats(): array
    {
        /** @var array<string> $fromOption */
        $fromOption = (array) $this->option('format');
        $fromOption = array_filter($fromOption);

        if (! empty($fromOption)) {
            return array_values($fromOption);
        }

        $configDefault = config('apiable.documentation.default_format');

        if (is_string($configDefault) && $configDefault !== '') {
            return [$configDefault];
        }

        // Interactive prompt
        $chosen = select(
            label: 'Select output format',
            options: [
                'markdown' => 'Markdown / MDX',
                'postman' => 'Postman v2.1 Collection',
                'openapi' => 'OpenAPI 3.1 YAML',
            ]
        );

        return [$chosen];
    }

    private function makeExporter(string $format, string $stub, string $outputPath): \OpenSoutheners\LaravelApiable\Documentation\Exporters\ExporterInterface
    {
        return match ($format) {
            'postman' => new PostmanExporter(
                collectionName: config('app.name', 'API').' Documentation',
                outputPath: $outputPath.'/postman_collection.json',
            ),
            'openapi' => new OpenApiExporter(
                title: config('app.name', 'API').' Documentation',
                outputPath: $outputPath.'/openapi.yaml',
            ),
            default => new MarkdownExporter(
                stub: $stub,
                outputPath: $outputPath,
            ),
        };
    }
}
