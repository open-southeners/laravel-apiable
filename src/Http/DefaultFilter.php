<?php

namespace OpenSoutheners\LaravelApiable\Http;

class DefaultFilter extends AllowedFilter
{
    /**
     * Make an instance of this class.
     *
     * @return void
     */
    public function __construct(string $attribute, ?int $operator = null, string|array $values = '*')
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
     * @return array<string, array<string>>
     */
    public function toArray(): array
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
