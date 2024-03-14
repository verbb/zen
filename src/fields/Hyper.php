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
                $linkSiteId = $link['linkSiteId'] ?? null;
                $linkValue = $link['linkValue'] ?? null;

                $value[$key]['linkSiteId'] = $linkSiteId ? Db::uidById(Table::SITES, $linkSiteId) : null;
                $value[$key]['linkValue'] = $linkValue ? Db::uidById(Table::ELEMENTS, $linkValue) : null;
            }
        }

        return $value;
    }

    public static function normalizeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        foreach ($value as $key => $link) {
            if (is_subclass_of($link['type'], ElementLink::class)) {
                // Swap UIDs for IDs
                $linkSiteId = $link['linkSiteId'] ?? null;
                $linkValue = $link['linkValue'] ?? null;

                $value[$key]['linkSiteId'] = $linkSiteId ? Db::idByUid(Table::SITES, $linkSiteId) : null;
                $value[$key]['linkValue'] = $linkValue ? Db::idByUid(Table::ELEMENTS, $linkValue) : null;
            }
        }

        return $value;
    }

}
