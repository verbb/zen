<?php
namespace verbb\zen\models;

use craft\elements\db\ElementQuery;

use yii\db\Connection;

class TempQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    private array $_elements = [];


    // Public Methods
    // =========================================================================

    public function setElements(array $value): void
    {
        $this->_elements = $value;
    }

    public function count($q = '*', $db = null): bool|int|string|null
    {
        return count($this->_elements);
    }

    public function all($db = null): array
    {
        return $this->_elements;
    }

    public function one($db = null): mixed
    {
        return $this->_elements[0] ?? null;
    }

    public function ids(?Connection $db = null): array
    {
        return [];
    }
}
