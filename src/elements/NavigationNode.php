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

use verbb\navigation\Navigation;
use verbb\navigation\elements\Node;

class NavigationNode extends ZenElement
{
    // Properties
    // =========================================================================

    private static array $_cachedElements = [];


    // Static Methods
    // =========================================================================

    public static function elementType(): string
    {
        return Node::class;
    }

    public static function displayName(): string
    {
        return 'Navigation ' . parent::displayName();
    }

    public static function lowerDisplayName(): string
    {
        return 'Navigation ' . parent::lowerDisplayName();
    }

    public static function pluralDisplayName(): string
    {
        return 'Navigation ' . parent::pluralDisplayName();
    }

    public static function pluralLowerDisplayName(): string
    {
        return 'Navigation ' . parent::pluralLowerDisplayName();
    }

    public static function exportKeyForElement(ElementInterface $element): array
    {
        return ['nav' => $element->nav->handle];
    }

    public static function getExportOptions(ElementQueryInterface $query): array|bool
    {
        $options = [];

        foreach (Navigation::$plugin->getNavs()->getAllNavs() as $nav) {
            $options[] = [
                'label' => $nav->name,
                'criteria' => ['navId' => $nav->id],
                'count' => $query->navId($nav->id)->count(),
            ];
        }

        return $options;
    }

    public static function defineSerializedElement(ElementInterface $element, array $data): array
    {
        // Serialize any additional attributes. Be sure to switch out IDs for UIDs.
        $data['navUid'] = Db::uidById('{{%navigation_navs}}', $element->navId);
        $data['url'] = $element->getRawUrl();
        $data['nodeType'] = $element->type;
        $data['classes'] = $element->classes;
        $data['urlSuffix'] = $element->urlSuffix;
        $data['customAttributes'] = $element->customAttributes;
        $data['data'] = $element->data;
        $data['newWindow'] = $element->newWindow;

        // Check for the linked element for element nodes
        if ($linkedElement = $element->getElement()) {
            if ($registeredElement = Zen::$plugin->getElements()->getElementByType(get_class($linkedElement))) {
                $data['linkedElement'] = $registeredElement::getSerializedElement($linkedElement);
            }
        }

        return $data;
    }

    public static function defineNormalizedElement(array $data): array
    {
        $data['navId'] = Db::idByUid('{{%navigation_navs}}', ArrayHelper::remove($data, 'navUid'));
        $data['type'] = ArrayHelper::remove($data, 'nodeType');

        // Create the linked-to element, if an element node
        if ($linkedElement = ArrayHelper::remove($data, 'linkedElement')) {
            // Save it to the cache so we can use it in the preview
            self::$_cachedElements['linkedElement'] = $linkedElement;

            Import::createDependency($linkedElement, $data, function(ElementInterface $element, ElementImportDependency $dependency) {
                // Assign the link element now that it's been imported
                $element->elementId = $dependency->elementImportAction->element->id;
            });
        }

        return $data;
    }

    public static function getElementForPreview(ElementInterface $element, string $type): void
    {
        $linkedElement = self::$_cachedElements['linkedElement'] ?? [];

        if ($linkedElement) {
            $elementType = $linkedElement['type'] ?? null;

            if ($elementType) {
                // Check our cached elements for any new ones we need to create
                if ($registeredElement = Zen::$plugin->getElements()->getElementByType($elementType)) {
                    $element->setElement($registeredElement::getNormalizedElement($linkedElement));
                }
            }
        }
    }

    public static function defineImportTableAttributes(): array
    {
        return [
            'type' => Craft::t('zen', 'Type'),
            'nav' => Craft::t('zen', 'Navigation'),
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
            'type' => $element->getTypeLabel(),
            'nav' => $element->nav->name,
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
}
