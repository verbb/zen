<?php
namespace verbb\zen\fields;

use verbb\zen\base\Field as ZenField;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\db\Table;
use craft\helpers\Db;

use verbb\hyper\fields\HyperField;
use verbb\hyper\base\ElementLink;

class Hyper extends ZenField
{
    // Static Methods
    // =========================================================================

    public static function fieldType(): string
    {
        return HyperField::class;
    }

    public static function serializeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        $value = $field->serializeValue($value, $element);

        foreach ($value as $key => $link) {
            if (is_subclass_of($link['type'], ElementLink::class)) {
                // Swap IDs for UIDs
                $value[$key]['linkSiteId'] = Db::uidById(Table::SITES, $link['linkSiteId']);
                $value[$key]['linkValue'] = Db::uidById(Table::ELEMENTS, $link['linkValue']);
            }
        }

        return $value;
    }

    public static function normalizeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        foreach ($value as $key => $link) {
            if (is_subclass_of($link['type'], ElementLink::class)) {
                // Swap UIDs for IDs
                $value[$key]['linkSiteId'] = Db::idByUd(Table::SITES, $link['linkSiteId']);
                $value[$key]['linkValue'] = Db::idByUid(Table::ELEMENTS, $link['linkValue']);
            }
        }

        return $value;
    }

}
