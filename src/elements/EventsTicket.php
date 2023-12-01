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
use craft\db\Table;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;

use verbb\events\Events;
use verbb\events\elements\Ticket;

class EventsTicket extends ZenElement
{
    // Properties
    // =========================================================================

    private static array $_cachedElements = [];


    // Static Methods
    // =========================================================================

    public static function elementType(): string
    {
        return Ticket::class;
    }

    public static function exportKeyForElement(ElementInterface $element): array
    {
        return ['eventUid' => $element->event->uid];
    }

    public static function getExportOptions(ElementQueryInterface $query): array|bool
    {
        return false;
    }

    public static function defineSerializedElement(ElementInterface $element, array $data): array
    {
        // Serialize any additional attributes. Be sure to switch out IDs for UIDs.
        $data['availableFrom'] = Db::prepareDateForDb($element->availableFrom);
        $data['availableTo'] = Db::prepareDateForDb($element->availableTo);
        $data['sku'] = $element->sku;
        $data['quantity'] = $element->quantity;
        $data['price'] = $element->price;
        $data['sortOrder'] = $element->sortOrder;
        $data['deletedWithEvent'] = $element->deletedWithEvent;
        $data['eventUid'] = $element->event->uid;

        // Ticket types are faux elements, so we need to bundle them
        $data['typeData'] = EventsTicketType::getSerializedElement($element->type);

        return $data;
    }

    public static function defineNormalizedElement(array $data): array
    {
        // Discard it, we don't need it with a new or existing event this is attached to
        ArrayHelper::remove($data, 'eventUid');

        $typeData = ArrayHelper::remove($data, 'typeData');

        $data['type'] = EventsTicketType::getNormalizedElement($typeData, true);

        return $data;
    }
}
