<?php
namespace verbb\zen\elements;

use verbb\zen\base\Element as ZenElement;
use verbb\zen\helpers\Db;
use verbb\zen\models\ImportFieldTab;

use Craft;
use craft\base\ElementInterface;
use craft\db\Table;
use craft\elements\Entry as EntryElement;
use craft\elements\User;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;

class Entry extends ZenElement
{
    // Static Methods
    // =========================================================================

    public static function elementType(): string
    {
        return EntryElement::class;
    }

    public static function exportKeyForElement(ElementInterface $element): array
    {
        return ['section' => $element->section->handle, 'type' => $element->type->handle];
    }

    public static function getExportOptions(ElementQueryInterface $query): array|bool
    {
        $options = [];

        foreach (Craft::$app->getSections()->getAllSections() as $section) {
            $entryTypes = [];

            foreach ($section->getEntryTypes() as $entryType) {
                $entryTypes[] = [
                    'label' => $entryType->name,
                    'criteria' => ['section' => $section->handle, 'type' => $entryType->handle],
                    'count' => $query->type($entryType)->count(),
                ];
            }

            $options[] = [
                'label' => $section->name,
                'criteria' => ['section' => $section->handle],
                'count' => array_sum(ArrayHelper::getColumn($entryTypes, 'count')),
                'children' => $entryTypes,
            ];
        }

        return $options;
    }

    public static function defineEagerLoadingMap(): array
    {
        // Eager-load the author to speed up serialization for export
        return ['author'];
    }

    public static function defineSerializedElement(ElementInterface $element, array $data): array
    {
        // Serialize any additional attributes. Be sure to switch out IDs for UIDs.
        $data['postDate'] = Db::prepareDateForDb($element->postDate);
        $data['expiryDate'] = Db::prepareDateForDb($element->expiryDate);
        $data['sectionUid'] = Db::uidById(Table::SECTIONS, $element->sectionId);
        $data['typeUid'] = Db::uidById(Table::ENTRYTYPES, $element->typeId);

        if ($element->authorId) {
            $data['authorEmail'] = Db::emailById($element->authorId);
        }

        return $data;
    }

    public static function defineNormalizedElement(array $data): array
    {
        $data['sectionId'] = Db::idByUid(Table::SECTIONS, ArrayHelper::remove($data, 'sectionUid'));
        $data['typeId'] = Db::idByUid(Table::ENTRYTYPES, ArrayHelper::remove($data, 'typeUid'));

        if ($authorEmail = ArrayHelper::remove($data, 'authorEmail')) {
            $data['authorId'] = Db::idByEmail($authorEmail);
        }

        return $data;
    }

    public static function defineImportTableAttributes(): array
    {
        return [
            'section' => Craft::t('zen', 'Section / Type'),
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
            'section' => $element->section->name . ' / ' . $element->type->name,
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
                    'authorEmail' => Cp::elementSelectFieldHtml([
                        'label' => Craft::t('app', 'Author'),
                        'id' => 'authorEmail',
                        'elementType' => User::class,
                        'elements' => [$element->author],
                        'disabled' => true,
                        'single' => true,
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
}
