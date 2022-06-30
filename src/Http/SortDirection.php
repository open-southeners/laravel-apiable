<?php

namespace OpenSoutheners\LaravelApiable\Http;

/**
 * TODO: Manual maintenance until PHP 8.1+ is mainly focused in Laravel/Symfony
 */
enum SortDirection: string
{
    case BOTH = '*';
    case ASCENDANT = 'asc';
    case DESCENDANT = 'desc';
}
