<?php

namespace OpenSoutheners\LaravelApiable\Http;

class DefaultFilter extends AllowedFilter
{
    /**
     * Make an instance of this class.
     *
     * @param  string  $attribute
     * @param  int|null  $operator
     * @param  string|array<string>  $values
     * @return void
     */
    public function __construct($attribute, $operator = null, $values = '*')
    {
        if (! is_null($operator) && ! $this->isValidOperator($operator)) {
            throw new \Exception(
                sprintf('Operator value "%s" for filtered attribute "%s" is not valid', $operator, $attribute)
            );
        }

        $this->attribute = $attribute;
        $this->operator = $operator ?? static::SIMILAR;
        $this->values = $values;
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, array<string, array<string>>>
     */
    public function toArray()
    {
        $defaultFilterArr = parent::toArray();

        $attribute = array_key_first($defaultFilterArr);

        return [
            $attribute => [
                $defaultFilterArr[$attribute]['operator'] => $defaultFilterArr[$attribute]['values'],
            ],
        ];
    }
}
