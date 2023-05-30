<?php
namespace verbb\zen\elements;

use verbb\zen\Zen;
use verbb\zen\base\Element as ZenElement;
use verbb\zen\helpers\Db;
use verbb\zen\models\ElementImportAction;
use verbb\zen\models\ImportFieldTab;

use Craft;
use craft\base\ElementInterface;
use craft\db\Table;
use craft\elements\Asset as AssetElement;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\events\DefineAssetThumbUrlEvent;
use craft\fs\Local;
use craft\services\Assets;

use yii\base\Event;

class Asset extends ZenElement
{
    // Static Methods
    // =========================================================================

    public static function elementType(): string
    {
        return AssetElement::class;
    }

    public static function exportKeyForElement(ElementInterface $element): array
    {
        return ['volume' => $element->volume->handle];
    }

    public static function getExportOptions(ElementQueryInterface $query): array|bool
    {
        $options = [];

        foreach (Craft::$app->getVolumes()->getAllVolumes() as $volume) {
            $options[] = [
                'label' => $volume->name,
                'criteria' => ['volume' => $volume->handle],
                'count' => $query->volume($volume)->count(),
            ];
        }

        return $options;
    }

    public static function defineEagerLoadingMap(): array
    {
        // Eager-load the uploader to speed up serialization for export
        return ['uploader'];
    }

    public static function defineSerializedElement(ElementInterface $element, array $data): array
    {
        // Serialize any additional attributes. Be sure to switch out IDs for UIDs.
        $data['folderPath'] = $element->folderPath;
        $data['kind'] = $element->kind;
        $data['alt'] = $element->alt;
        $data['size'] = $element->size;
        $data['keptFile'] = $element->keptFile;
        $data['dateModified'] = $element->dateModified;
        $data['deletedWithVolume'] = $element->deletedWithVolume;
        $data['filename'] = $element->filename;
        $data['width'] = $element->width;
        $data['height'] = $element->height;
        $data['volumeUid'] = Db::uidById(Table::VOLUMES, $element->volumeId);

        if ($element->uploaderId) {
            if (Craft::$app->getEdition() === Craft::Pro) {
                $data['uploader'] = Db::emailById($element->uploaderId);
            }
        }

        // Only store focal point data if it's set. SVGs can throw errors when storing this against the asset
        // (maybe Craft should enforce some proper validation on the SVG asset to prevent focal point being set at all)
        if ($element->getHasFocalPoint()) {
            $data['focalPoint'] = $element->focalPoint;
        }

        $data['folder'] = [
            'volume' => $element->getFolder()->volumeId,
            'name' => $element->getFolder()->name,
            'path' => $element->getFolder()->path,
        ];

        // If serializing for an export, record any additional files (the actual asset) to be stored in the zip
        // But only for local storage. If a remote filesystem, no need to process
        if ($element->getVolume()->getFs() instanceof Local) {
            Zen::$plugin->getExport()->storeExportFile([
                'filename' => $element->filename,
                'content' => $element->getContents(),
            ]);
        }

        return $data;
    }

    public static function defineNormalizedElement(array $data): array
    {
        $data['volumeId'] = Db::idByUid(Table::VOLUMES, ArrayHelper::remove($data, 'volumeUid'));

        if ($uploaderEmail = ArrayHelper::remove($data, 'uploader')) {
            $data['uploaderId'] = Db::idByEmail($uploaderEmail);
        }

        // Not needed yet, but removed for validation as it won't populate the model
        ArrayHelper::remove($data, 'folder');

        // Assign any asset in our payload matching this filename
        foreach (Zen::$plugin->getImport()->getStoredImportFiles() as $tempAsset) {
            if ($tempAsset['filename'] === $data['filename']) {
                $data['tempFilePath'] = $tempAsset['path'];
            }
        }

        // For asset fields, we want to generate the thumbnail, but because it's not publicly available and still in temp
        // we need to do a little more work to get it working. Using this event, we can hijack things across the entire
        // import process, when turning into an Asset element.
        Event::on(Assets::class, Assets::EVENT_DEFINE_THUMB_URL, function(DefineAssetThumbUrlEvent $event) {
            $path = $event->asset->tempFilePath;

            if ($path) {
                $path = str_replace(Craft::getAlias('@storage/runtime/temp/'), '', $path);

                $event->url = UrlHelper::actionUrl('zen/plugin/temp-asset', [
                    'path' => $path,
                ]);
            }
        });

        return $data;
    }

    public static function defineImportTableAttributes(): array
    {
        return [
            'volume' => Craft::t('zen', 'Volume'),
            'filename' => Craft::t('zen', 'Filename'),
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
            'volume' => $element->volume->name,
            'filename' => $element->filename,
        ];
    }

    public static function defineImportFieldTabs(ElementInterface $element, string $type): array
    {
        return [
            new ImportFieldTab([
                'name' => Craft::t('zen', 'Preview'),
                'fields' => [
                    'preview' => Html::beginTag('div', [
                        'id' => 'thumb-container',
                        'class' => array_filter([
                            'preview-thumb-container',
                            'button-fade',
                            $element->getHasCheckeredThumb() ? 'checkered' : null,
                        ]),
                    ]) .
                    Html::tag('div', $element->getPreviewThumbImg(350, 190), [
                        'class' => 'preview-thumb',
                    ]) .
                    Html::endTag('div'),
                ],
            ]),
            new ImportFieldTab([
                'name' => Craft::t('zen', 'Meta'),
                'fields' => [
                    'filename' => Cp::textFieldHtml([
                        'label' => Craft::t('app', 'Filename'),
                        'id' => 'filename',
                        'value' => $element->filename,
                        'disabled' => true,
                    ]),
                    'width' => Cp::textFieldHtml([
                        'label' => Craft::t('app', 'Width'),
                        'id' => 'width',
                        'value' => $element->width,
                        'disabled' => true,
                    ]),
                    'height' => Cp::textFieldHtml([
                        'label' => Craft::t('app', 'Height'),
                        'id' => 'height',
                        'value' => $element->height,
                        'disabled' => true,
                    ]),
                    'size' => Cp::textFieldHtml([
                        'label' => Craft::t('app', 'Size'),
                        'id' => 'size',
                        'value' => $element->getFormattedSize(0),
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

    public static function beforeImport(ElementImportAction $importAction): bool
    {
        if (in_array($importAction->action, [ElementImportAction::ACTION_SAVE, ElementImportAction::ACTION_RESTORE])) {
            // Ensure the folder exists before importing into it. Volume folders aren't stored in project config.
            $folderInfo = ArrayHelper::remove($importAction->data, 'folder');
            $volume = $importAction->element->getVolume();
            $path = $folderInfo['path'] ?? null;

            $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume((string)$path, $volume);
            $importAction->element->folderId = $folder->id;

            // Also check if the temp file doesn't exist. It might've been already taken care of
            if ($importAction->element->tempFilePath && !file_exists($importAction->element->tempFilePath)) {
                $importAction->element->tempFilePath = null;
            }
        }

        return parent::beforeImport($importAction);
    }

    public static function afterImport(ElementImportAction $importAction): void
    {
        if (in_array($importAction->action, [ElementImportAction::ACTION_SAVE, ElementImportAction::ACTION_RESTORE])) {
            // In `Asset::_relocateFile()`, the `dateModified` value will be altered when the temp file has been moved. 
            // We don't want that, so reset it back.
            $currentDateModified = DateTimeHelper::toDateTime($importAction->element->dateModified);
            $originalDateModified = DateTimeHelper::toDateTime(($importAction->data['dateModified'] ?? null));

            if ($currentDateModified !== $originalDateModified) {
                $importAction->element->dateModified = $originalDateModified;

                Craft::$app->getElements()->saveElement($importAction->element, false);
            }
        }

        parent::afterImport($importAction);
    }

}
