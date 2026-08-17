<?php

namespace OpenSoutheners\LaravelApiable\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use OpenSoutheners\LaravelApiable\Documentation\EndpointSchemaGenerator;
use OpenSoutheners\LaravelApiable\Documentation\Exporters\TypeScriptSchemaExporter;

class ApiableTypesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apiable:types
        {--only=* : Only include routes matching these patterns}
        {--exclude=* : Exclude routes matching these patterns}
        {--path= : Override the output file (defaults to resources/js/api-schema.ts)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a TypeScript module describing every documented endpoint\'s allowed filters, sorts, includes, fields and appends';

    public function handle(Router $router, Filesystem $files): int
    {
        $outputPath = $this->option('path') ?? base_path('resources/js/api-schema.ts');

        /** @var array<string> $only */
        $only = (array) $this->option('only');
        /** @var array<string> $exclude */
        $exclude = (array) $this->option('exclude');

        $this->components->info('Generating TypeScript API schema…');

        $generator = new EndpointSchemaGenerator($router);
        $schemas = $generator->generate($only, $exclude);

        if (empty($schemas)) {
            $this->components->warn('No documented resources found. Annotate controllers with #[DocumentedResource].');

            return self::SUCCESS;
        }

        $exporter = new TypeScriptSchemaExporter;
        $contents = $exporter->export($schemas);

        $files->ensureDirectoryExists(dirname((string) $outputPath));
        $files->put((string) $outputPath, $contents);

        $this->newLine();
        $this->components->twoColumnDetail('<fg=green>Resource</>', '<fg=green>Path</>');

        foreach ($schemas as $slug => $schema) {
            $this->components->twoColumnDetail($slug, $schema['path']);
        }

        $this->newLine();
        $this->components->info("Wrote {$outputPath}.");

        return self::SUCCESS;
    }
}
