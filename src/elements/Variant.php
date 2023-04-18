<?php
namespace verbb\zen\elements;

use verbb\zen\base\Element as ZenElement;

use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;

use craft\commerce\elements\Variant as VariantElement;

class Variant extends ZenElement
{
    // Static Methods
    // =========================================================================

    public static function elementType(): string
    {
        return VariantElement::class;
    }

    public static function exportKeyForElement(ElementInterface $element): array
    {
        return ['productUid' => $element->product->uid];
    }

    public static function getExportOptions(ElementQueryInterface $query): array|bool
    {
        return false;
    }

    public static function defineSerializedElement(ElementInterface $element, array $data): array
    {
        // Serialize any additional attributes. Be sure to switch out IDs for UIDs.
        $data['sku'] = $element->sku;
        $data['isDefault'] = $element->isDefault;
        $data['price'] = $element->price;
        $data['sortOrder'] = $element->sortOrder;
        $data['width'] = $element->width;
        $data['height'] = $element->height;
        $data['length'] = $element->length;
        $data['weight'] = $element->weight;
        $data['stock'] = $element->stock;
        $data['hasUnlimitedStock'] = $element->hasUnlimitedStock;
        $data['minQty'] = $element->minQty;
        $data['maxQty'] = $element->maxQty;
        $data['deletedWithProduct'] = $element->deletedWithProduct;
        $data['productUid'] = $element->product->uid;

        return $data;
    }

    public static function defineNormalizedElement(array $data): array
    {
        $data['productId'] = Db::idByUid('{{%commerce_products}}',  ArrayHelper::remove($data, 'productUid'));

        return $data;
    }
}
