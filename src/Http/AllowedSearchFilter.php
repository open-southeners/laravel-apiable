<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Contracts\Support\Arrayable;

class AllowedSearchFilter implements Arrayable
{
    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var string|array<string>
     */
    protected $values;

    /**
     * Make an instance of this class.
     *
     * @param  string  $attribute
     * @param  string|array<string>  $values
     * @return void
     */
    public function __construct($attribute, $values = '*')
    {
        $this->attribute = $attribute;
        $this->values = $values;
    }

    /**
     * Allow default filter by attribute
     *
     * @param  string  $attribute
     * @param  string|array<string>  $values
     */
    public static function make($attribute, $values = '*'): self
    {
        return new self($attribute, $values);
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, array<string, array<string>>>
     */
    public function toArray()
    {
        return [
            $this->attribute => [
                'values' => $this->values,
            ],
        ];
    }
}
