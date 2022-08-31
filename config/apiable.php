<?php

return [

    'normalize_relations' => false,

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

    /**
     * Pagination options.
     *
     * @see https://docs.opensoutheners.com/laravel-apiable/guide/responses.html
     */
    'pagination' => [
        'default_size' => 50,
    ],

];
