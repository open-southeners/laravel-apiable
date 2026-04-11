<?php

namespace OpenSoutheners\LaravelApiable\Documentation\Exporters;

interface ExporterInterface
{
    /**
     * Export the given resources and return a map of output path → file contents.
     *
     * @param  \OpenSoutheners\LaravelApiable\Documentation\Resource[]  $resources
     * @return array<string, string>
     */
    public function export(array $resources): array;
}
