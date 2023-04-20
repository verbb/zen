<?php
namespace verbb\zen\elements;

use verbb\zen\base\Element as ZenElement;
use verbb\zen\models\ElementImportAction;
use verbb\zen\models\ImportFieldTab;

use Craft;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Db;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Product as ProductElement;

class Product extends ZenElement
{
    // Static Methods
    // =========================================================================

    public static function elementType(): string
    {
        return ProductElement::class;
    }

    public static function exportKeyForElement(ElementInterface $element): array
    {
        return ['type' => $element->type->handle];
    }

    public static function getExportOptions(ElementQueryInterface $query): array|bool
    {
        $options = [];

        foreach (Commerce::getInstance()->getProductTypes()->getAllProductTypes() as $type) {
            $options[] = [
                'label' => $type->name,
                'criteria' => ['type' => $type->handle],
                'count' => $query->type($type)->count(),
            ];
        }

        return $options;
    }

    public static function defineSerializedElement(ElementInterface $element, array $data): array
    {
        // Serialize any additional attributes. Be sure to switch out IDs for UIDs.
        $data['postDate'] = Db::prepareDateForDb($element->postDate);
        $data['expiryDate'] = Db::prepareDateForDb($element->expiryDate);
        $data['promotable'] = $element->promotable;
        $data['freeShipping'] = $element->freeShipping;
        $data['availableForPurchase'] = $element->availableForPurchase;
        $data['defaultSku'] = $element->defaultSku;
        $data['defaultPrice'] = $element->defaultPrice;
        $data['defaultHeight'] = $element->defaultHeight;
        $data['defaultLength'] = $element->defaultLength;
        $data['defaultWidth'] = $element->defaultWidth;
        $data['defaultWeight'] = $element->defaultWeight;

        $data['typeUid'] = $element->type->uid;
        $data['taxCategory'] = $element->getTaxCategory()->handle ?? null;
        $data['shippingCategory'] = $element->getShippingCategory()->handle ?? null;
        $data['defaultVariantUid'] = $element->defaultVariant->uid;

        foreach ($element->getVariants() as $variant) {
            $data['variants'][] = Variant::getSerializedElement($variant);
        }

        return $data;
    }

    public static function defineNormalizedElement(array $data): array
    {
        $data['typeId'] = Db::idByUid('{{%commerce_producttypes}}', ArrayHelper::remove($data, 'typeUid'));
        $data['defaultVariantId'] = Db::idByUid('{{%commerce_variants}}',  ArrayHelper::remove($data, 'defaultVariantUid'));

        // Swap the handles of tax/shipping categories to IDs
        $data['taxCategoryId'] = self::idByHandle('{{%commerce_taxcategories}}', ArrayHelper::remove($data, 'taxCategory'));
        $data['shippingCategoryId'] = self::idByHandle('{{%commerce_shippingcategories}}', ArrayHelper::remove($data, 'shippingCategory'));

        foreach (ArrayHelper::remove($data, 'variants', []) as $variant) {
            // Ensure we set the parent field layout from the product type, so that custom fields work correctly
            $productType = Commerce::getInstance()->getProductTypes()->getProductTypeById($data['typeId']);

            if ($productType) {
                $variant['fieldLayoutId'] = $productType->fieldLayout->id ?? null;
            }

            $data['variants'][] = Variant::getNormalizedElement($variant);
        }

        return $data;
    }

    public static function defineImportTableAttributes(): array
    {
        return [
            'type' => Craft::t('zen', 'Product Type'),
        ];
    }

    public static function defineImportTableValues(array $diffs, ?ElementInterface $newElement, ?ElementInterface $currentElement, ?string $state): array
    {
        // Use either the new or current element to get data for, at this generic stage.
        $element = $newElement ?? $currentElement ?? null;

        if (!$element) {
            return [];
        }

        return [
            'type' => $element->type->name,
        ];
    }

    public static function defineImportFieldTabs(ElementInterface $element, string $type): array
    {
        return [
            new ImportFieldTab([
                'name' => Craft::t('zen', 'Meta'),
                'fields' => [
                    'slug' => Cp::textFieldHtml([
                        'label' => Craft::t('app', 'Slug'),
                        'id' => 'slug',
                        'value' => $element->slug,
                        'disabled' => true,
                    ]),
                    'enabled' => Cp::lightswitchFieldHtml([
                        'label' => Craft::t('app', 'Enabled'),
                        'id' => 'enabled',
                        'on' => $element->enabled,
                        'disabled' => true,
                    ]),
                    'promotable' => Cp::lightswitchFieldHtml([
                        'label' => Craft::t('app', 'Promotable'),
                        'id' => 'promotable',
                        'on' => $element->promotable,
                        'disabled' => true,
                    ]),
                    'freeShipping' => Cp::lightswitchFieldHtml([
                        'label' => Craft::t('app', 'Free Shipping'),
                        'id' => 'freeShipping',
                        'on' => $element->freeShipping,
                        'disabled' => true,
                    ]),
                    'availableForPurchase' => Cp::lightswitchFieldHtml([
                        'label' => Craft::t('app', 'Available for Purchase'),
                        'id' => 'availableForPurchase',
                        'on' => $element->availableForPurchase,
                        'disabled' => true,
                    ]),
                    'postDate' => Cp::dateTimeFieldHtml([
                        'label' => Craft::t('app', 'Post Date'),
                        'id' => 'postDate',
                        'value' => $element->postDate,
                        'disabled' => true,
                    ]),
                    'expiryDate' => Cp::dateTimeFieldHtml([
                        'label' => Craft::t('app', 'Expiry Date'),
                        'id' => 'expiryDate',
                        'value' => $element->expiryDate,
                        'disabled' => true,
                    ]),
                    'dateCreated' => Cp::dateTimeFieldHtml([
                        'label' => Craft::t('app', 'Date Created'),
                        'id' => 'dateCreated',
                        'value' => $element->dateCreated,
                        'disabled' => true,
                    ]),
                ],
            ]),
        ];
    }


    // Protected Methods
    // =========================================================================

    protected static function defineExistingImportedElement(ElementInterface $newElement, ElementInterface $currentElement): void
    {
        // We need to do a little extra handling here for repeated imports, or multi-site imports.
        // Zen will check for the product ID for an already-imported product, but needs to do the same
        // variant-id check to ensure that variants aren't imported as duplicates.
        $variants = $newElement->variants;

        foreach ($currentElement->variants as $key => $value) {
            $variants[$key]->id = $value->id;
        }

        $newElement->variants = $variants;
    }


    // Private Methods
    // =========================================================================

    private static function idByHandle(string $table, string $handle): ?int
    {
        $id = (new Query())
            ->select(['id'])
            ->from([$table])
            ->where(['handle' => $handle])
            ->scalar();

        return (int)$id ?: null;
    }
}
