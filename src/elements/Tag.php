<?php
namespace verbb\zen\elements;

use verbb\zen\base\Element as ZenElement;
use verbb\zen\models\ImportFieldTab;

use Craft;
use craft\base\ElementInterface;
use craft\db\Table;
use craft\elements\Tag as TagElement;
use craft\elements\User;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Db;

class Tag extends ZenElement
{
    // Static Methods
    // =========================================================================

    public static function elementType(): string
    {
        return TagElement::class;
    }

    public static function exportKeyForElement(ElementInterface $element): array
    {
        return ['group' => $element->group->handle];
    }

    public static function getExportOptions(ElementQueryInterface $query): array|bool
    {
        $options = [];

        foreach (Craft::$app->getTags()->getAllTagGroups() as $group) {
            $options[] = [
                'label' => $group->name,
                'criteria' => ['group' => $group->handle],
                'count' => $query->group($group)->count(),
            ];
        }

        return $options;
    }

    public static function defineSerializedElement(ElementInterface $element, array $data): array
    {
        // Serialize any additional attributes. Be sure to switch out IDs for UIDs.
        $data['groupUid'] = $element->getGroup()->uid;

        return $data;
    }

    public static function defineNormalizedElement(array $data): array
    {
        $data['groupId'] = Db::idByUid(Table::TAGGROUPS, ArrayHelper::remove($data, 'groupUid'));

        return $data;
    }

    public static function defineImportTableAttributes(): array
    {
        return [
            'group' => Craft::t('zen', 'Group'),
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
            'group' => $element->group->name,
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
}
