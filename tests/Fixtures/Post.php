<?php

namespace OpenSoutheners\LaravelApiable\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use OpenSoutheners\LaravelApiable\Concerns\HasJsonApi;
use OpenSoutheners\LaravelApiable\Contracts\JsonApiable;
use OpenSoutheners\LaravelApiable\JsonApiableOptions;

class Post extends Model implements JsonApiable
{
    use HasJsonApi;

    /**
     * The attributes that should be visible in serialization.
     *
     * @var string[]
     */
    protected $visible = ['status', 'title', 'content', 'abstract', 'author_id'];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]
     */
    protected $guarded = [];

    /**
     * Set options for model to be serialize with JSON:API.
     *
     * @return \OpenSoutheners\LaravelApiable\JsonApiableOptions
     */
    public function jsonApiableOptions()
    {
        return JsonApiableOptions::withDefaults(self::class);
    }

    /**
     * Get its parent post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(self::class);
    }

    /**
     * Get its author user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get its tags.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Return whether the post is published.
     *
     * @return bool
     */
    public function getIsPublishedAttribute()
    {
        return true;
    }
}
