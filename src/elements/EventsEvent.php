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
use verbb\events\elements\Event;
use verbb\events\elements\Ticket;

class EventsEvent extends ZenElement
{
    // Properties
    // =========================================================================

    private static array $_cachedElements = [];


    // Static Methods
    // =========================================================================

    public static function elementType(): string
    {
        return Event::class;
    }

    public static function displayName(): string
    {
        return 'Events ' . parent::displayName();
    }

    public static function lowerDisplayName(): string
    {
        return 'Events ' . parent::lowerDisplayName();
    }

    public static function pluralDisplayName(): string
    {
        return 'Events ' . parent::pluralDisplayName();
    }

    public static function pluralLowerDisplayName(): string
    {
        return 'Events ' . parent::pluralLowerDisplayName();
    }

    public static function exportKeyForElement(ElementInterface $element): array
    {
        return ['type' => $element->type->handle];
    }

    public static function getExportOptions(ElementQueryInterface $query): array|bool
    {
        $options = [];

        foreach (Events::$plugin->getEventTypes()->getAllEventTypes() as $type) {
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
        $data['startDate'] = Db::prepareDateForDb($element->startDate);
        $data['endDate'] = Db::prepareDateForDb($element->endDate);
        $data['postDate'] = Db::prepareDateForDb($element->postDate);
        $data['expiryDate'] = Db::prepareDateForDb($element->expiryDate);
        $data['allDay'] = $element->allDay;
        $data['capacity'] = $element->capacity;

        $data['typeUid'] = Db::uidById('{{%events_eventtypes}}', $element->typeId);

        foreach ($element->getTickets() as $ticket) {
            $data['tickets'][] = EventsTicket::getSerializedElement($ticket);
        }

        return $data;
    }

    public static function defineNormalizedElement(array $data): array
    {
        $data['typeId'] = Db::idByUid('{{%events_eventtypes}}', ArrayHelper::remove($data, 'typeUid'));

        foreach (ArrayHelper::remove($data, 'tickets', []) as $ticket) {
            // Ensure we set the parent field layout from the event type, so that custom fields work correctly
            $eventType = Events::$plugin->getEventTypes()->getEventTypeById($data['typeId']);

            if ($eventType) {
                $ticket['fieldLayoutId'] = $eventType->fieldLayout->id ?? null;
            }

            $data['tickets'][] = EventsTicket::getNormalizedElement($ticket, true);
        }

        return $data;
    }

    public static function defineImportTableAttributes(): array
    {
        return [
            'type' => Craft::t('zen', 'Event Type'),
        ];
    }

    public static function defineImportTableValues(?ElementInterface $newElement, ?ElementInterface $currentElement, ?string $state): array
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
                'fields' => array_merge([
                    'uid' => Cp::textFieldHtml([
                        'label' => Craft::t('app', 'UID'),
                        'id' => 'uid',
                        'value' => $element->uid,
                        'disabled' => true,
                    ]),
                    'enabled' => Cp::lightswitchFieldHtml([
                        'label' => Craft::t('app', 'Enabled'),
                        'id' => 'enabled',
                        'on' => $element->enabled,
                        'disabled' => true,
                    ]),
                    'startDate' => Cp::dateTimeFieldHtml([
                        'label' => Craft::t('app', 'Start Date'),
                        'id' => 'startDate',
                        'value' => $element->startDate,
                        'disabled' => true,
                    ]),
                    'endDate' => Cp::dateTimeFieldHtml([
                        'label' => Craft::t('app', 'End Date'),
                        'id' => 'endDate',
                        'value' => $element->endDate,
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
                static::getRawDataHtml($element),
                ),
            ]),
        ];
    }

    public static function beforeImport(ElementImportAction $importAction): bool
    {
        if (in_array($importAction->action, [ElementImportAction::ACTION_SAVE, ElementImportAction::ACTION_RESTORE])) {
            foreach ($importAction->element->getTickets() as $ticket) {
                // Ticket Types needs to exist before importing events and their tickets
                if (!$ticket->typeId && $ticket->type) {
                    // See if there's already an existing type
                    $existingType = Events::$plugin->getTicketTypes()->getTicketTypeByUid($ticket->type->uid);

                    if ($existingType) {
                        $ticket->type->id = $existingType->id;
                    }

                    Craft::$app->getElements()->saveElement($ticket->type);

                    $ticket->typeId = $ticket->type->id;
                }

                // Extra check here to find an existing ticket for the UID
                $existingTicket = Ticket::find()->uid($ticket->uid)->siteId($ticket->siteId)->one();

                if ($existingTicket) {
                    $ticket->id = $existingTicket->id;
                }
            }
        }

        return parent::beforeImport($importAction);
    }


    // Protected Methods
    // =========================================================================

    protected static function defineExistingImportedElement(ElementInterface $newElement, ElementInterface $currentElement): void
    {
        // We need to do a little extra handling here for repeated imports, or multi-site imports.
        // Zen will check for the event ID for an already-imported event, but needs to do the same
        // ticket-id check to ensure that tickets aren't imported as duplicates.
        $tickets = $newElement->tickets;

        foreach ($currentElement->tickets as $key => $value) {
            $tickets[$key]->id = $value->id;
        }

        $newElement->tickets = $tickets;
    }

}
