<?php
namespace verbb\zen\services;

use verbb\zen\Zen;
use verbb\zen\elements as elementTypes;
use verbb\zen\helpers\Plugin;
use verbb\zen\records\ElementAction;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\db\ActiveRecord;
use craft\db\Query;
use craft\events\DraftEvent;
use craft\events\ElementEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Json;

use Throwable;

class Elements extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_ELEMENT_TYPES = 'registerElementTypes';


    // Properties
    // =========================================================================

    private ?string $_excludedElement = null;


    // Public Methods
    // =========================================================================

    public function getAllElementTypes(): array
    {
        $elementTypes = [
            elementTypes\Asset::class,
            elementTypes\Category::class,
            elementTypes\Entry::class,
            elementTypes\GlobalSet::class,
            elementTypes\Tag::class,
            elementTypes\User::class,
        ];

        if (Plugin::isPluginInstalledAndEnabled('commerce')) {
            $elementTypes[] = elementTypes\Product::class;
            $elementTypes[] = elementTypes\Variant::class;
        }

        $event = new RegisterComponentTypesEvent([
            'types' => $elementTypes,
        ]);
        $this->trigger(self::EVENT_REGISTER_ELEMENT_TYPES, $event);

        return $event->types;
    }

    public function getElementByType(string $elementType): ?string
    {
        foreach ($this->getAllElementTypes() as $registeredElementType) {
            if ($registeredElementType::elementType() === $elementType) {
                return $registeredElementType;
            }
        }

        return null;
    }

    public function onApplyDraft(DraftEvent $event): void
    {
        $this->_excludedElement = $event->draft->uid;
    }

    public function onDeleteElement(ElementEvent $event): void
    {
        // Record the deleted element so we have a record
        $record = new ElementAction();
        $record->type = 'delete';

        // Check if we're preventing deletion, mostly for when applying a draft. This is because when applying a draft
        // the draft technically raises a deletion event, but an applied draft isn't something we need to track.
        if ($this->_excludedElement === $event->element->uid) {
            return;
        }

        // We also don't care if discarding a provisional draft
        if ($event->element->isProvisionalDraft) {
            return;
        }

        $this->_onElementAction($record, $event->element);
    }

    public function onRestoreElement(ElementEvent $event): void
    {
        // Record the restored element so we have a record
        $record = new ElementAction();
        $record->type = 'restore';

        $this->_onElementAction($record, $event->element);
    }

    public function getDeletedElementsForExport(string $elementType, array $dateRange, array $criteria): array
    {
        // In order to get the correct deleted elements that haven't since been restored, we need to do extra work
        return $this->_getConsolidatedActions($elementType, $dateRange, $criteria, 'delete');
    }

    public function getRestoredElementsForExport(string $elementType, array $dateRange, array $criteria): array
    {
        // In order to get the correct restored elements that haven't since been deleted (again), we need to do extra work
        return $this->_getConsolidatedActions($elementType, $dateRange, $criteria, 'restore');
    }


    // Private Methods
    // =========================================================================

    private function _onElementAction(ActiveRecord $record, ElementInterface $element): void
    {
        try {
            $record->elementId = $element->id;
            $record->elementSiteId = $element->siteId;
            $record->elementType = get_class($element);
            $record->draftId = $element->draftId;
            $record->canonicalId = $element->canonicalId;

            // If there's a matching Zen-ready element type, serialize the data
            $registeredElement = $this->getElementByType(get_class($element));

            // This is an element not configured to work with Zen, so bail
            if (!$registeredElement) {
                return;
            }

            $record->exportKey =  Json::encode([$registeredElement::exportKey() => $registeredElement::exportKeyForElement($element)]);
            $record->data = $registeredElement::getSerializedElement($element);

            $record->save(false);
        } catch (Throwable $e) {
            Zen::error(Craft::t('zen', 'Unable to record element action: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }
    }

    private function _getConsolidatedActions(string $elementType, array $dateRange, array $criteria, string $type): array
    {
        $actions = (new Query())
            ->select(['*'])
            ->from(ElementAction::tableName())
            ->where(['elementType' => $elementType, 'exportKey' => Json::encode($criteria)])
            ->andWhere(['between', 'dateCreated', $dateRange[0], $dateRange[1]])
            ->all();

        $processed = [];
        $data = [];

        // To ensure that we check if one action is cancelling out a previous one (deleted then restored, etc.)
        // we process each item, only caring about the latest action.
        foreach ($actions as $action) {
            $previousAction = $processed[$action['elementId']] ?? [];

            // Are we restoring a deleted element within the same payload? If so, that's pointless, they cancel it out.
            if ($previousAction && $previousAction['type'] === 'delete' && $action['type'] === 'restore') {
                unset($processed[$action['elementId']]);

                continue;
            }

            $processed[$action['elementId']] = $action;
        }

        foreach ($processed as $action) {
            if ($action['type'] === $type) {
                $data[] = Json::decode($action['data']);
            }
        }

        return $data;
    }
}
