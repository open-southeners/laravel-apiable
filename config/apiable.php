<?php

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
    'filters' => [
        'default_operator' => 'like',
    ],

    'sorts' => [
        'default_direction' => '*',
    ],

    'requests' => [
        'filters' => [
            'enforce_scoped_names' => false,
        ],
    ],

    /**
     * Default options for responses like: normalize relations names, include allowed filters and sorts, etc.
     *
     * @see https://docs.opensoutheners.com/laravel-apiable/guide/responses.html
     */
    'normalize_relations' => false,

    'responses' => [
        'include_allowed' => false,
    ],

    'pagination' => [
        'default_size' => 50,
    ],

];
