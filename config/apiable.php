<?php

return [

    'normalize_relations' => false,

    /**
     * Default options for request query filters, sorts, etc.
     *
     * @see https://docs.open-southeners.com/laravel-apiable/requests
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
     * @see https://docs.open-southeners.com/laravel-apiable/reponses
     */
    'pagination' => [
        'default_size' => 50,
    ],

];
