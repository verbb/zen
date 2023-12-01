<?php
namespace verbb\zen\elements;

use verbb\zen\Zen;
use verbb\zen\base\Element as ZenElement;
use verbb\zen\helpers\Db;
use verbb\zen\models\ElementImportAction;
use verbb\zen\models\ElementImportDependency;
use verbb\zen\models\ImportFieldTab;
use verbb\zen\services\Import;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;

use verbb\events\Events;
use verbb\events\elements\TicketType;

class EventsTicketType extends ZenElement
{
    // Properties
    // =========================================================================

    private static array $_cachedElements = [];


    // Static Methods
    // =========================================================================

    public static function elementType(): string
    {
        return TicketType::class;
    }

    public static function exportKeyForElement(ElementInterface $element): array
    {
        return ['ticketUid' => $element->ticket->uid];
    }

    public static function getExportOptions(ElementQueryInterface $query): array|bool
    {
        return false;
    }

    public static function defineSerializedElement(ElementInterface $element, array $data): array
    {
        // Serialize any additional attributes. Be sure to switch out IDs for UIDs.
        $data['handle'] = $element->handle;
        $data['taxCategory'] = $element->getTaxCategory()->handle ?? null;
        $data['shippingCategory'] = $element->getShippingCategory()->handle ?? null;

        // UID for this should be from the `events_tickettypes` table, not the `elements` table
        $data['uid'] = Db::uidById('{{%events_tickettypes}}', $element->id);

        return $data;
    }

    public static function defineNormalizedElement(array $data): array
    {
        // Swap the handles of tax/shipping categories to IDs
        $data['taxCategoryId'] = self::idByHandle('{{%commerce_taxcategories}}', ArrayHelper::remove($data, 'taxCategory'));
        $data['shippingCategoryId'] = self::idByHandle('{{%commerce_shippingcategories}}', ArrayHelper::remove($data, 'shippingCategory'));

        return $data;
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
