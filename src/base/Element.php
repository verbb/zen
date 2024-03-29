<?php
namespace verbb\zen\base;

use verbb\zen\Zen;
use verbb\zen\base\ElementInterface as ZenElementInterface;
use verbb\zen\events\ElementImportEvent;
use verbb\zen\events\ModifyElementImportFieldTabsEvent;
use verbb\zen\events\ModifyElementImportTableAttributesEvent;
use verbb\zen\events\ModifyElementImportTableValuesEvent;
use verbb\zen\events\ModifyElementNormalizedDataEvent;
use verbb\zen\events\ModifyElementSerializedDataEvent;
use verbb\zen\helpers\ArrayHelper;
use verbb\zen\helpers\Db;
use verbb\zen\models\ElementImportAction;
use verbb\zen\models\ElementImportDependency;
use verbb\zen\services\Import;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\db\Table;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutForm;
use craft\models\FieldLayoutFormTab;
use craft\models\FieldLayoutTab;
use craft\web\View;

use Throwable;

use yii\base\Event;

use Wa72\HtmlPageDom\HtmlPageCrawler;

abstract class Element implements ZenElementInterface
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_IMPORT_FIELD_TABS = 'modifyImportFieldTabs';
    public const EVENT_MODIFY_IMPORT_TABLE_ATTRIBUTES = 'modifyImportTableAttributes';
    public const EVENT_MODIFY_IMPORT_TABLE_VALUES = 'modifyImportTableValues';
    public const EVENT_MODIFY_NORMALIZED_DATA = 'modifyNormalizedData';
    public const EVENT_MODIFY_SERIALIZED_DATA = 'modifySerializedData';
    public const EVENT_BEFORE_IMPORT = 'beforeImport';
    public const EVENT_AFTER_IMPORT = 'afterImport';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return static::elementType()::displayName();
    }

    public static function lowerDisplayName(): string
    {
        return static::elementType()::lowerDisplayName();
    }

    public static function pluralDisplayName(): string
    {
        return static::elementType()::pluralDisplayName();
    }

    public static function pluralLowerDisplayName(): string
    {
        return static::elementType()::pluralLowerDisplayName();
    }

    public static function elementUniqueIdentifier(): string
    {
        return 'uid';
    }

    public static function find(): ElementQueryInterface
    {
        return static::elementType()::find();
    }

    public static function getElementHtml(ElementInterface $element): string
    {
        return $element->id ? Cp::elementHtml(element: $element, single: true, context: 'index') : (string)$element;
    }

    public static function exportKey(): string
    {
        return StringHelper::toCamelCase(static::pluralDisplayName());
    }

    public static function exportKeyForElement(ElementInterface $element): array
    {
        return [];
    }

    public static function getExportData(ElementQueryInterface $query, array $params): array
    {
        Craft::configure($query, $params);

        return $query->all();
    }

    public static function getImportTableAttributes(): array
    {
        $prefixColumns = [
            'element' => static::displayName(),
            'site' => Craft::t('app', 'Site'),
        ];

        $elementColumns = static::defineImportTableAttributes();

        $suffixColumns = [
            'state' => Craft::t('zen', 'State'),
            'summary' => Craft::t('zen', 'Summary'),
        ];

        // Give plugins a chance to modify them
        $event = new ModifyElementImportTableAttributesEvent([
            'elementType' => static::class,
            'attributes' => array_merge($prefixColumns, $elementColumns, $suffixColumns),
        ]);
        Event::trigger(static::class, self::EVENT_MODIFY_IMPORT_TABLE_ATTRIBUTES, $event);

        return $event->attributes;
    }

    public static function defineImportTableAttributes(): array
    {
        return [];
    }

    public static function getImportTableValues(array $diffCounts, ?ElementInterface $newElement, ?ElementInterface $currentElement, ?string $state): array
    {
        $element = $newElement ?? $currentElement ?? null;
        $parent = $element ? $element->getParent() : null;
        $elementHtml = $element ? static::getElementHtml($element) : '';
        $elementColumns = [];

        // Create a unique ID for this row, so we can lookup the preview later
        $elementIdentifier = static::elementUniqueIdentifier();
        $elementId = ($element) ? $element->$elementIdentifier . ':' . $element->site->uid : null;

        $prefixColumns = [
            'id' => $elementId,
            'element' => $elementHtml,
            'parents' => static::getParentSummary($parent),
            'site' => $element->site->name ?? '',
        ];

        try {
            // Important to catch any errors related to the element (invalid field handles, invalid PC updates)
            $elementColumns = static::defineImportTableValues($newElement, $currentElement, $state);
        } catch (Throwable $e) {
            return [
                'error' => true,
                'errorMessage' => $e->getMessage(),
                'errorDetail' => nl2br($e->getTraceAsString()),
            ];
        }
        
        $suffixColumns = array_filter([
            'state' => $state,
            'summary' => $diffCounts,
        ]);

        // Give plugins a chance to modify them
        $event = new ModifyElementImportTableValuesEvent([
            'elementType' => static::class,
            'values' => array_merge($prefixColumns, $elementColumns, $suffixColumns),
        ]);
        Event::trigger(static::class, self::EVENT_MODIFY_IMPORT_TABLE_VALUES, $event);

        return array_filter([
            'data' => $event->values,
        ]);
    }

    public static function defineImportTableValues(?ElementInterface $newElement, ?ElementInterface $currentElement, ?string $state): array
    {
        return [];
    }

    /**
     * When generating an element for export, we serialize it to have just what we need (and in the format we need)
     * so we can import it on another install. We're pretty restrictive about what content we do save, as we don't need
     * everything for an element. Importantly, any references to IDs should be swapped to UIDs or handles. This is because
     * on the destination install, the ID likely won't be the same (think `authorId`).
     * 
     * This is also called when comparing on the destination install, to ensure there's consistency.
     * 
     * Element classes should use [[defineSerializedElement()]] to define their own data.
     */
    public static function getSerializedElement(ElementInterface $element): array
    {
        // Check if this element has already been serialized. Helpful for parent-resolution
        // which can happen multiple times for the same element.
        $cacheKey = $element->uid . ':' . $element->getSite()->uid;

        if ($cachedSerializedElement = Zen::$plugin->getElements()->getCachedSerializedElement($cacheKey)) {
            return $cachedSerializedElement;
        }

        $data = [
            'type' => $element::class,
            'title' => $element->title,
            'slug' => $element->slug,
            'uid' => $element->uid,
            'enabled' => $element->enabled,
            'dateCreated' => Db::prepareDateForDb($element->dateCreated),
        ];

        // Check for `parentId` first for performance
        if ($element->parentId) {
            if ($parent = $element->getParent()) {
                $data['level'] = $element->level;
                $data['parent'] = static::getSerializedElement($parent);
            }
        }

        // For any elements in a structure, reference the siblings to ensure theyre placed correctly and not just appended
        if ($element->structureId) {
            if ($prevSibling = $element->getPrevSibling()) {
                $data['prevSibling'] = $prevSibling->uid;
            }

            if ($nextSibling = $element->getNextSibling()) {
                $data['nextSibling'] = $nextSibling->uid;
            }
        }

        // Swap some IDs to their UIDs
        $data['siteUid'] = $element->getSite()->uid;
        $data['fields'] = static::getSerializedElementFields($element);

        // Allow element type classes to modify the data
        $data = static::defineSerializedElement($element, $data);

        // Allow plugins to modify the data
        $event = new ModifyElementSerializedDataEvent([
            'elementType' => static::class,
            'element' => $element,
            'values' => $data,
        ]);
        Event::trigger(static::class, self::EVENT_MODIFY_SERIALIZED_DATA, $event);

        // Convert to any extra objects to an array easily (without calling `toArray()`)
        // We also want to filter out any empty values, so we can get the right "remove" diff rather than change
        $data = ArrayHelper::recursiveFilter(Json::decode(Json::encode($event->values)));

        // Cache it in case we call the same element
        Zen::$plugin->getElements()->setCachedSerializedElement($cacheKey, $data);

        return $data;
    }

    /**
     * Use this function to specify how to serialize an element when it's generated for export.
     */
    public static function defineSerializedElement(ElementInterface $element, array $data): array
    {
        return [];
    }

    /**
     * Define how element fields are serialized for export. It's important to swap IDs for some other form of identification
     * that exists on the destination install (think relation fields).
     */
    public static function getSerializedElementFields(ElementInterface $element): array
    {
        $values = [];

        $fieldsService = Zen::$plugin->getFields();

        foreach ($fieldsService->getCustomFields($element) as $field) {
            // Use the field UID to maintain uniqueness, as handles can be the same in Matrix/etc fields. This helps with diffing resolution.
            $fieldKey = $field->handle . ':' . $field->uid;

            // Only handly fields that are "supported"
            $value = $element->getFieldValue($field->handle);

            // Allow registered fields with Zen to handle the serialization
            $values[$fieldKey] = $fieldsService->serializeValue($field, $element, $value);
        }

        return $values;
    }

    public static function getNormalizedElement(array $data, bool $includeFields = false): ElementInterface
    {
        // Check if this element has already been normalized. Helpful for parent-resolution
        // which can happen multiple times for the same element.
        $cacheKey = ($data['uid'] ?? '') . ':' . ($data['siteUid'] ?? '');

        if ($cachedNormalizedElement = Zen::$plugin->getElements()->getCachedNormalizedElement($cacheKey)) {
            return $cachedNormalizedElement;
        }

        ArrayHelper::rename($data, 'type', 'class');
        $fields = ArrayHelper::remove($data, 'fields', []);

        // Normalize UIDs back to IDs
        $data['siteId'] = Db::idByUid(Table::SITES, ArrayHelper::remove($data, 'siteUid'));

        // Allow element type classes to modify the data before being turned into an element
        $data = static::defineNormalizedElement($data);

        // Handle parent items (after classes can handle them)
        if ($parent = ArrayHelper::remove($data, 'parent')) {
            Import::createDependency($parent, $data, function(ElementInterface $element, ElementImportDependency $dependency) {
                $element->parentId = $dependency->elementImportAction->element->id;
            });

            // Despite not really needing this yet, we prep the parent so it's ready for use in previews, not for the actual import
            $data['parent'] = static::getNormalizedElement($parent, false);
        }

        // For any elements in a structure, reference the siblings to ensure theyre placed correctly and not just appended
        $prevSibling = ArrayHelper::remove($data, 'prevSibling');
        $nextSibling = ArrayHelper::remove($data, 'nextSibling');

        if ($prevSibling || $nextSibling) {
            // Add a post-import (after _all_ items are done) task to move the elements into their right place
            Zen::$plugin->getElements()->setStructureItem(($data['uid'] ?? ''), [
                'elementType' => $data['class'] ?? null,
                'siteId' => $data['siteId'] ?? null,
                'prevSibling' => $prevSibling,
                'nextSibling' => $nextSibling,
            ]);
        }

        // Allow plugins to modify the data
        $event = new ModifyElementNormalizedDataEvent([
            'elementType' => static::class,
            'fields' => $fields,
            'values' => $data,
        ]);
        Event::trigger(static::class, self::EVENT_MODIFY_NORMALIZED_DATA, $event);

        // Create the element and assign custom fields
        $element = Craft::createObject($event->values);

        if ($includeFields) {
            $fieldValues = static::getNormalizedElementFields($element, $event->fields);
            $element->setFieldValues($fieldValues);
        }

        // Cache it in case we call the same element
        Zen::$plugin->getElements()->setCachedNormalizedElement($cacheKey, $element);

        return $element;
    }

    /**
     * Use this function to specify how to normalize an element when it's imported.
     */
    public static function defineNormalizedElement(array $data): array
    {
        return $data;
    }

    /**
     * Define how element fields are normalized for import.
     */
    public static function getNormalizedElementFields(ElementInterface $element, array $fieldData): array
    {
        // Field handles contain their handle and UID to ensure uniqueness, but that's now no longer needed. Convert back to handles.
        $fieldData = self::_normalizeFieldHandles($fieldData);

        $values = [];
        $fieldsByHandle = [];

        $fieldsService = Zen::$plugin->getFields();

        $fieldsByHandle = ArrayHelper::index($fieldsService->getCustomFields($element), 'handle');

        foreach ($fieldData as $fieldHandle => $fieldValue) {
            if ($field = ArrayHelper::getValue($fieldsByHandle, $fieldHandle)) {
                // Allow registered fields with Zen to handle the serialization
                $values[$field->handle] = $fieldsService->normalizeValue($field, $element, $fieldValue);
            }
        }

        return $values;
    }

    public static function getImportFieldTabs(ElementInterface $element, string $type): array
    {
        $tabs = [];

        // Convert our `ImportFieldTab` items to `FieldLayoutFormTab/FieldLayoutTab/FieldLayoutElement`
        foreach (static::defineImportFieldTabs($element, $type) as $tab) {
            $fieldElements = [];

            // Convert to field layout tab elements
            foreach ($tab->fields as $field) {
                $fieldElements[] = [null, false, $field];
            }

            $tabs[] = new FieldLayoutFormTab([
                'layoutTab' => new FieldLayoutTab([
                    'name' => $tab->name,
                ]),
                'elements' => $fieldElements,
            ]);
        }

        // Give plugins a chance to modify them
        $event = new ModifyElementImportFieldTabsEvent([
            'elementType' => static::class,
            'tabs' => $tabs,
        ]);
        Event::trigger(static::class, self::EVENT_MODIFY_IMPORT_FIELD_TABS, $event);

        return $event->tabs;
    }

    public static function defineImportFieldTabs(ElementInterface $element, string $type): array
    {
        return [];
    }

    public static function getElementForPreview(ElementInterface $element, string $type): void
    {
    }

    public static function beforeImport(ElementImportAction $importAction): bool
    {
        // Tell the fields about it
        if (!Zen::$plugin->getFields()->beforeElementImport($importAction->element)) {
            return false;
        }

        // Trigger a 'beforeImport' event
        $event = new ElementImportEvent([
            'importAction' => $importAction,
        ]);
        Event::trigger(static::class, self::EVENT_BEFORE_IMPORT, $event);

        return $event->isValid;
    }

    public static function afterImport(ElementImportAction $importAction): void
    {
        // Tell the fields about it
        Zen::$plugin->getFields()->afterElementImport($importAction->element);

        // Trigger an 'afterImport' event
        Event::trigger(static::class, self::EVENT_AFTER_IMPORT, new ElementImportEvent([
            'importAction' => $importAction,
        ]));
    }

    public static function checkExistingImportedElement(ElementImportAction $importAction): void
    {
        // Before importing, check if the element we're about to import already has, and add the ID so we don't get duplicates.
        // There are a few scenarios where this might happen:
        // 1. Multi-site imports, where the "Site A" element has already been imported, but the "Site B" element needs to import
        // (but use the same imported element, just site-specific).
        // 2. Elements containing element fields. Importing "Entry 1" with a categories field with "Category 1", which is also
        // being imported at the same time as an element.
        // 3. Parent elements, for the same reasons above, as the parent will have been imported first, but the child won't
        // know about it.
        //
        // So, to address everything here, we query for an existing element, and apply the ID if found.
        static::populateExistingImportedElement($importAction->element);

        // Do the same for any parent all the way up the tree
        $parent = $importAction->element->parent;

        while ($parent) {
            static::populateExistingImportedElement($parent);

            $parent = $parent->parent;
        }
    }

    public static function getEagerLoadingMap(): array
    {
        $attributes = ['parent', 'ancestors'];

        return array_merge($attributes, static::defineEagerLoadingMap());
    }

    public static function defineEagerLoadingMap(): array
    {
        return [];
    }

    public static function generateCompareHtml(?ElementInterface $element, array $diffSummary, string $type): array
    {
        $html = '';
        $js = '';
        $view = Craft::$app->getView();

        // Required when testing outside of the CP (using the URLs directly)
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);

        $fieldsService = Zen::$plugin->getFields();

        if ($element) {
            if ($fieldLayout = $fieldsService->getElementFieldLayout($element)) {
                static::getElementForPreview($element, $type);

                // Allow any registered fields to modify their values for preview
                foreach ($fieldLayout->getCustomFields() as $field) {
                    $fieldsService->getFieldForPreview($field, $element, $type);
                }

                // Render the form, despite us not needing the HTML, we need to fetch any JS used to append it
                Craft::$app->getView()->startJsBuffer();
                $form = $fieldLayout->createForm($element, true);
                $js = Craft::$app->getView()->clearJsBuffer();

                // Get any custom field tabs for the element, and any extra defined class
                $tabHtml = '';
                $tabs = array_merge($form->tabs, static::getImportFieldTabs($element, $type));

                foreach ($tabs as $key => $tab) {
                    $tabHtml .= Html::tag('button', $tab->getName(), [
                        'type' => 'button',
                        'data-zui-tab-target' => $tab->getName() . '-' . $key,
                        'class' => ['tab', ($key === 0 ? 'sel' : '')],
                    ]);
                }

                $html .= Html::tag('nav', $tabHtml, [
                    'class' => 'zui-import-detail-tabs',
                ]);

                foreach ($tabs as $key => $tab) {
                    $html .= Html::tag('div', $tab->getContent(), [
                        'data-zui-tab-pane' => $tab->getName() . '-' . $key,
                        'class' => [($key === 0 ? '' : 'hidden')],
                    ]);
                }
            }
        } else {
            $html .= Html::tag('div', Craft::t('zen', 'Element does not exist.'), [
                'class' => 'detail-empty',
            ]);
        }

        // For some reason, CSS buffers don't seem to catch loaded CSS from third-party fields.
        // This is a very rough approach, but we go through each asset bundle and load them manually.
        foreach ($view->assetBundles as $key => $bundle) {
            if (!str_starts_with($key, 'craft\\web\\') && !str_starts_with($key, 'yii\\web\\')) {
                foreach ($bundle->css as $cssFile) {
                    if (is_array($cssFile)) {
                        $cssFile = $cssFile[array_keys($cssFile)[0]];
                    }

                    $url = $view->getAssetManager()->getActualAssetUrl($bundle, $cssFile);
                    $html .= '<link href="' . $url . '" rel="stylesheet">';
                }

                foreach ($bundle->js as $jsFile) {
                    $type = 'script';

                    if (is_array($jsFile)) {
                        $type = $jsFile['type'] ?? $type;

                        $jsFile = $jsFile[array_keys($jsFile)[0]];
                    }

                    $url = $view->getAssetManager()->getActualAssetUrl($bundle, $jsFile);
                    $js = '<script type="' . $type . '" src="' . $url . '"></script>' . $js;
                }
            }
        }

        // Do some extra work to prepare the HTML just the way we need it.
        $crawler = HtmlPageCrawler::create($html);

        $addStatusIndicator = function($crawler, $selector, $diffInfo) {
            $diffType = $diffInfo['type'];
            $diffHtml = $diffInfo['diffHtml'] ?? null;

            // From the diff, build the selector to fetch the right field.
            $field = self::_buildFieldSelector($crawler, $selector);

            $text = '';

            if ($diffType === 'add') {
                $text = Craft::t('zen', 'This content has been added.');
            } else if ($diffType === 'change') {
                $text = Craft::t('zen', 'This content has been changed.');
            } else if ($diffType === 'remove') {
                $text = Craft::t('zen', 'This content has been removed.');
            }

            if ($field->count()) {
                $field->prepend(Html::tag('div', Html::tag('span', $text, ['class' => 'visually-hidden']), [
                    'id' => $selector . '-status',
                    'class' => ['status-badge', $diffType],
                    'title' => $text,
                ]));

                // Also add an indicator to the parent tab
                $pane = $field->closest('[data-zui-tab-pane]');

                if ($pane->count()) {
                    $tabName = $pane->attr('data-zui-tab-pane');

                    $crawler->filter('[data-zui-tab-target="' . $tabName . '"]')->addClass('has-change');
                }

                // Modify inputs or text to show diff as text/html
                if ($diffHtml) {
                    $inputs = $field->filter('textarea.nicetext, .text.readable')->each(function($node, $i) use ($diffHtml) {
                        $classes = $node->attr('class');

                        $node->replaceWith('<div class="' . $classes .'">' . $diffHtml . '</div>');
                    });
                }
            }
        };

        // Add our change status indicator to each field for the "new" element
        if ($type === 'new') {
            foreach ($diffSummary as $selector => $diffInfo) {
                $addStatusIndicator($crawler, $selector, $diffInfo);
            }
        }

        // Fix some fields like title adding `disabled` class, which make it faded out. They still have `disabled` attributes
        $crawler->filter('input')->removeClass('disabled');

        // Fix Date/time fields still showing the "X" despite being read-only. More a Craft bug.
        $crawler->filter('.clear-btn')->remove();

        // Remove links on relation fields, because they might point to elements that don't exist yet.
        $crawler->filter('.element .label a')->removeAttribute('href');

        // Fix checkboxes can still be toggled
        $crawler->filter('input[type="checkbox"]')->setAttribute('disabled', true);

        // Fix dropdown fields (selectize) being hidden
        $crawler->filter('.selectize.select select')->removeAttribute('style');

        return ['html' => $crawler->saveHTML(), 'js' => $js];
    }


    // Abstract Methods
    // =========================================================================

    /**
     * Return the actual Element Type class used by Craft.
     * 
     * @return class-string<ElementInterface> The Element class name
     */
    abstract public static function elementType(): string;

    /**
     * Return a collection of options for what groups of elements the user can pick to export.
     * The `value` of each item should reflect the corresponding ElementQueryInterface param for that element.
     * For example, providing `section:mySectionHandle` will be transformed into `['section': ['mySectionHandle']]`
     * which can then be used later in [[getExportData()]] to apply the query param.
     * 
     * This should follow the format:
     * [
     *     'label' => 'My Section',
     *     'criteria' => ['section' => 'mySectionHandle'],
     *     'count' => 123,
     *     'children' => [
     *         // optional, as required
     *     ],
     * ]
     */
    abstract public static function getExportOptions(ElementQueryInterface $query): array|bool;


    // Protected Methods
    // =========================================================================

    protected static function populateExistingImportedElement(?ElementInterface $element): void
    {
        $elementIdentifier = static::elementUniqueIdentifier();

        // We only care if the element doesn't have an ID, and there's data to match with the identifier
        if ($element && !$element->id && $element->$elementIdentifier) {
            if ($importedElement = static::getExistingImportedElement($element)) {
                $element->id = $importedElement->id;

                // Allow some elements to handle populating the new element from the existing one (Products)
                static::defineExistingImportedElement($element, $importedElement);
            }
        }
    }

    protected static function defineExistingImportedElement(ElementInterface $newElement, ElementInterface $currentElement): void
    {
        return;
    }

    protected static function getExistingImportedElement(ElementInterface $element): ?ElementInterface
    {
        $elementIdentifier = static::elementUniqueIdentifier();

        return static::find()
            ->$elementIdentifier($element->$elementIdentifier)
            ->siteId($element->siteId)
            ->status(null)
            ->trashed(null)
            ->one();
    }

    protected static function getParentSummary(?ElementInterface $element): string
    {
        $items = [];

        if ($element) {
            if ($parent = $element->getParent()) {
                $items[] = static::getParentSummary($parent);
            }

            $items[] = $element->getUiLabel();
        }

        return implode(' / ', $items);
    }

    protected static function getRawDataHtml(ElementInterface $element): array
    {
        $id = StringHelper::randomString(10);

        return [
            'dataToggle' => Html::tag('a', Craft::t('zen', 'Raw Data'), [
                'class' => 'fieldtoggle',
                'data-target' => 'raw-data-' . $id,
            ]),
            'rawData' => Html::tag('div', Html::tag('pre', Json::encode(static::getSerializedElement($element), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)), [
                'class' => 'zui-raw-data hidden',
                'id' => 'raw-data-' . $id,
            ]),
        ];
    }


    // Private Methods
    // =========================================================================

    private static function _normalizeFieldHandles($array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (str_contains($key, ':')) {
                $key = explode(':', $key)[0];
            }

            $result[$key] = is_array($value) ? self::_normalizeFieldHandles($value) : $value;
        }

        return $result;
    }

    private static function _buildFieldSelector($crawler, string $selector)
    {
        // From the diff, build the selector to fetch the right field. This will convert diff selectors
        // into DOM selectors. For example:
        // `title` = `[data-attribute="title"]`
        // `fields.icon` = `[data-attribute="icon"]`
        // `fields.pluginPricing.0.fields.features` = `[data-attribute="pluginPricing"] [data-attribute="features"]:eq(0)`
        $selector = str_replace(['fields.', '.fields.'], ['', '.'], $selector);
        $result = $crawler;
        $equalCommand = null;

        $commands = [];

        foreach (explode('.', $selector) as $item) {
            if (is_numeric($item)) {
                $equalCommand = 'eq:' . $item;
                continue;
            }

            $commands[] = 'filter:[data-attribute="' . $item . '"]';

            // Tricky check here, as we need to have `filter(...)->eq(..)` and not `eq->(...)filter(...)`
            if ($equalCommand) {
                $commands[] = $equalCommand;
                $equalCommand = null;
            }
        }

        foreach ($commands as $command) {
            if (str_starts_with($command, 'filter:')) {
                $result = $result->filter(str_replace('filter:', '', $command));
            }

            if (str_starts_with($command, 'eq:')) {
                $result = $result->eq(str_replace('eq:', '', $command));
            }
        }

        return $result;
    }
}
