<?php

namespace OpenSoutheners\LaravelApiable\Documentation\Exporters;

interface ExporterInterface
{
    /**
     * Export the given resources and return a map of output path → file contents.
     *
     * @param  OpenSouthenersLaravelApiableDocumentationResource[]  $resources
     * @return array<string, string>
     */
    public function export(array $resources): array;
}
