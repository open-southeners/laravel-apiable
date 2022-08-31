<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

trait AllowsSearch
{
    /**
     * @var bool
     */
    protected $allowedSearch = false;

    /**
     * Get user search query from request.
     *
     * @return string
     */
    public function searchQuery()
    {
        return $this->request->get('q', $this->request->get('search', ''));
    }

    /**
     * Allow fulltext search to be performed.
     *
     * @param  bool  $value
     * @return $this
     */
    public function allowSearch(bool $value = true)
    {
        $this->allowedSearch = $value;

        return $this;
    }

    /**
     * Check if fulltext search is allowed.
     *
     * @return bool
     */
    public function isSearchAllowed()
    {
        return $this->allowedSearch;
    }
}
