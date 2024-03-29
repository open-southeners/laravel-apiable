<?php

use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;

return [

    /**
     * Resource type model map.
     *
     * @see https://docs.opensoutheners.com/laravel-apiable/guide/#getting-started
     */
    'resource_type_map' => [],

    /**
     * Default options for request query filters, sorts, etc.
     *
     * @see https://docs.opensoutheners.com/laravel-apiable/guide/requests.html
     */
    'requests' => [
        'validate_params' => false,

        'filters' => [
            'default_operator' => AllowedFilter::SIMILAR,
            'enforce_scoped_names' => false,
        ],

        'sorts' => [
            'default_direction' => AllowedSort::BOTH,
        ],
    ],

    /**
     * Default options for responses like: normalize relations names, include allowed filters and sorts, etc.
     *
     * @see https://docs.opensoutheners.com/laravel-apiable/guide/responses.html
     */
    'responses' => [
        'formatting' => [
            'type' => 'application/vnd.api+json',
            'force' => false,
        ],

        'normalize_relations' => false,

        'include_allowed' => false,

        'pagination' => [
            'default_size' => 50,
        ],

        'viewable' => true,

        'include_ids_on_attributes' => false,
    ],

];
