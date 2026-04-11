<?php

namespace OpenSoutheners\LaravelApiable\Documentation\Exporters;

use OpenSoutheners\LaravelApiable\Documentation\AuthScheme;
use OpenSoutheners\LaravelApiable\Documentation\QueryParam;

class OpenApiExporter implements ExporterInterface
{
    public function __construct(
        private readonly string $title = 'API Documentation',
        private readonly string $version = '1.0.0',
        private readonly string $outputPath = 'openapi.yaml',
    ) {
        //
    }

    /**
     * @param  OpenSouthenersLaravelApiableDocumentationResource[]  $resources
     * @return array<string, string>
     */
    public function export(array $resources): array
    {
        $paths = [];
        $securitySchemes = [];
        $globalSecurity = [];

        foreach ($resources as $resource) {
            foreach ($resource->endpoints as $endpoint) {
                $pathKey = '/'.$endpoint->uri;
                $method = strtolower($endpoint->method);

                $operation = [
                    'summary' => $endpoint->title,
                    'tags' => [$resource->name],
                    'parameters' => array_map(
                        static fn (QueryParam $p) => [
                            'name' => $p->key,
                            'in' => 'query',
                            'description' => $p->description,
                            'required' => $p->required,
                            'schema' => ['type' => 'string'],
                        ],
                        $endpoint->queryParams
                    ),
                ];

                if ($endpoint->description !== '') {
                    $operation['description'] = $endpoint->description;
                }

                if ($endpoint->auth !== null) {
                    $schemeName = $this->registerSecurityScheme($endpoint->auth, $securitySchemes);
                    $operation['security'] = [[$schemeName => []]];

                    if (! in_array([$schemeName => []], $globalSecurity, true)) {
                        $globalSecurity[] = [$schemeName => []];
                    }
                }

                if (! isset($paths[$pathKey])) {
                    $paths[$pathKey] = [];
                }

                $paths[$pathKey][$method] = $operation;
            }
        }

        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => $this->title,
                'version' => $this->version,
            ],
            'paths' => $paths,
        ];

        if (! empty($securitySchemes)) {
            $spec['components'] = ['securitySchemes' => $securitySchemes];
        }

        return [
            $this->outputPath => $this->toYaml($spec),
        ];
    }

    /**
     * Register a security scheme and return the scheme name.
     *
     * @param  array<string, array<string, mixed>>  $securitySchemes
     */
    private function registerSecurityScheme(AuthScheme $auth, array &$securitySchemes): string
    {
        if ($auth->type === 'basic') {
            $securitySchemes['basicAuth'] = [
                'type' => 'http',
                'scheme' => 'basic',
            ];

            return 'basicAuth';
        }

        $securitySchemes['bearerAuth'] = [
            'type' => 'http',
            'scheme' => 'bearer',
        ];

        return 'bearerAuth';
    }

    /**
     * Convert a nested PHP array to YAML string.
     * Uses symfony/yaml when available (it is in Laravel 12+), otherwise hand-rolls.
     *
     * @param  array<string, mixed>  $data
     */
    private function toYaml(array $data): string
    {
        if (class_exists(\Symfony\Component\Yaml\Yaml::class)) {
            return \Symfony\Component\Yaml\Yaml::dump($data, 10, 2, \Symfony\Component\Yaml\Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        }

        return $this->encodeYaml($data, 0);
    }

    /**
     * Simple recursive YAML encoder for the limited schema surface we need.
     *
     * @param  array<string, mixed>|mixed  $value
     */
    private function encodeYaml(mixed $value, int $indent): string
    {
        $pad = str_repeat('  ', $indent);

        if (is_array($value)) {
            if (empty($value)) {
                return "{}\n";
            }

            // Detect list vs map
            if (array_is_list($value)) {
                $out = '';
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $lines = explode("\n", rtrim($this->encodeYaml($item, $indent + 1)));
                        $out .= $pad.'- '.ltrim($lines[0])."\n";
                        foreach (array_slice($lines, 1) as $line) {
                            if ($line !== '') {
                                $out .= $line."\n";
                            }
                        }
                    } else {
                        $out .= $pad.'- '.$this->scalarYaml($item)."\n";
                    }
                }

                return $out;
            }

            $out = '';
            foreach ($value as $k => $v) {
                if (is_array($v)) {
                    if (empty($v)) {
                        $out .= $pad.$k.": {}\n";
                    } else {
                        $out .= $pad.$k.":\n".$this->encodeYaml($v, $indent + 1);
                    }
                } else {
                    $out .= $pad.$k.': '.$this->scalarYaml($v)."\n";
                }
            }

            return $out;
        }

        return $this->scalarYaml($value)."\n";
    }

    private function scalarYaml(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $str = (string) $value;

        // Quote strings that look like YAML keywords or contain special chars
        if (in_array(strtolower($str), ['true', 'false', 'null', 'yes', 'no', 'on', 'off'], true)
            || preg_match('/[:#\[\]{}|>&*!,?@`]/', $str)
            || str_contains($str, "\n")
        ) {
            return "'".str_replace("'", "''", $str)."'";
        }

        return $str;
    }
}
