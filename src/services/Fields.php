<?php
namespace verbb\zen\services;

use verbb\zen\Zen;
use verbb\zen\base\Field as BaseField;
use verbb\zen\fields as fieldTypes;
use verbb\zen\helpers\ArrayHelper;
use verbb\zen\helpers\Plugin;
use verbb\zen\models\ZenField;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\db\Query;
use craft\events\RegisterComponentTypesEvent;
use craft\fieldlayoutelements\CustomField;
use craft\fields\BaseRelationField;
use craft\fields\Matrix;
use craft\helpers\Json;
use craft\models\FieldLayout;

use verbb\supertable\fields\SuperTableField;
use benf\neo\Field as NeoField;

class Fields extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_FIELD_TYPES = 'registerFieldTypes';


    // Properties
    // =========================================================================

    private array $_fieldTypeMap = [];
    private array $_fieldHashes = [];
    private array $_fieldsByHandle = [];


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        // Preload our Craft field vs Zen field map
        foreach ($this->getAllFieldTypes() as $registeredFieldType) {
            $this->_fieldTypeMap[$registeredFieldType::fieldType()] = $registeredFieldType;
        }

        parent::init();
    }

    public function getAllFieldTypes(): array
    {
        $fieldTypes = [
            fieldTypes\Matrix::class,
        ];

        if (Plugin::isPluginInstalledAndEnabled('super-table')) {
            $fieldTypes[] = fieldTypes\SuperTable::class;
        }

        if (Plugin::isPluginInstalledAndEnabled('neo')) {
            $fieldTypes[] = fieldTypes\Neo::class;
        }

        if (Plugin::isPluginInstalledAndEnabled('seomatic')) {
            $fieldTypes[] = fieldTypes\Seomatic::class;
        }

        if (Plugin::isPluginInstalledAndEnabled('image-optimize')) {
            $fieldTypes[] = fieldTypes\ImageOptimize::class;
        }

        if (Plugin::isPluginInstalledAndEnabled('preparse-field')) {
            $fieldTypes[] = fieldTypes\Preparse::class;
        }

        $event = new RegisterComponentTypesEvent([
            'types' => $fieldTypes,
        ]);
        $this->trigger(self::EVENT_REGISTER_FIELD_TYPES, $event);

        return $event->types;
    }

    public function getFieldType(FieldInterface $field): string
    {
        $fallback = BaseField::class;

        // Rather than trying to support every possible relation field, catch them all.
        if ($field instanceof BaseRelationField) {
            $fallback = fieldTypes\RelationField::class;
        }

        // Return either a specific class to handle the field, or a generic one.
        return $this->_fieldTypeMap[get_class($field)] ?? $fallback;
    }

    public function getFieldFromHash(string $hash): ?ZenField
    {
        if ($this->_fieldHashes) {
            return $this->_fieldHashes[$hash] ?? null;
        }

        $fields = [];

        // For every field (for every context), store it indexed by it's handle+UID to easily identify it. We also store a reference
        // to the field, and the Zen class that supports it (if any).
        foreach (Craft::$app->getFields()->getAllFields(false) as $field) {
            $fields[$field->handle . ':' . $field->uid] = new ZenField([
                'field' => $field,
                'fieldType' => $this->getFieldType($field),
            ]);
        }

        $fields = $this->_fieldHashes = array_filter($fields);

        return $fields[$hash] ?? null;
    }

    public function getFieldLayout(?FieldLayout $fieldLayout): ?FieldLayout
    {
        // Filter out any unsupported fields in the field layout
        if ($fieldLayout) {
            $fieldLayout = clone $fieldLayout;

            $tabs = $fieldLayout->getTabs();

            foreach ($tabs as $tabKey => $tab) {
                $fieldElements = $tab->getElements();

                foreach ($fieldElements as $fieldElementKey => $fieldElement) {
                    if ($fieldElement instanceof CustomField) {
                        $field = $fieldElement->getField();

                        $fieldType = $this->getFieldType($field);

                        if (!$fieldType::isSupported()) {
                            unset($fieldElements[$fieldElementKey]);
                        }
                    }
                }

                $tab->setElements($fieldElements);
            }

            $fieldLayout->setTabs($tabs);

            return $fieldLayout;
        }

        return null;
    }

    public function getElementFieldLayout(mixed $element): ?FieldLayout
    {
        return $this->getFieldLayout($element->getFieldLayout());
    }

    public function getCustomFields(mixed $element): array
    {
        $fields = [];

        if ($fieldLayout = $this->getElementFieldLayout($element)) {
            $fields = $fieldLayout->getCustomFields();
        }

        return $fields;
    }

    public function getCustomFieldElements(mixed $element): array
    {
        $fields = [];

        if ($fieldLayout = $this->getElementFieldLayout($element)) {
            $fields = $fieldLayout->getCustomFieldElements();
        }

        return $fields;
    }

    public function serializeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        $fieldType = $this->getFieldType($field);

        return $fieldType::serializeValue($field, $element, $value);
    }

    public function normalizeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        $fieldType = $this->getFieldType($field);
        
        return $fieldType::normalizeValue($field, $element, $value);
    }

    public function getFieldForPreview(FieldInterface $field, ElementInterface $element, string $type): void
    {
        $fieldType = $this->getFieldType($field);
        
        $fieldType::getFieldForPreview($field, $element, $type);
    }

    public function beforeElementImport(ElementInterface $element): bool
    {
        if ($fieldLayout = $element->getFieldLayout()) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                $fieldType = $this->getFieldType($field);

                if (!$fieldType::beforeElementImport($field, $element)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function afterElementImport(ElementInterface $element): void
    {
        if ($fieldLayout = $element->getFieldLayout()) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                $fieldType = $this->getFieldType($field);

                $fieldType::afterElementImport($field, $element);
            }
        }
    }

    public function handleValueForDiff(string $fieldKey, mixed &$oldValue, mixed &$newValue): ?array
    {
        // Determine the field from the handle+UID, so we don't get tripped up just looking up via handle
        if ($fieldInfo = $this->getFieldFromHash($fieldKey)) {
            return $fieldInfo->fieldType::handleValueForDiff($fieldInfo->field, $oldValue, $newValue);
        }

        return null;
    }

    public function getEagerLoadingMap(): array
    {
        $mapKey = [];

        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            if ($keys = $this->_getEagerLoadingMapForField($field)) {
                $mapKey = array_merge($mapKey, $keys);
            }
        }

        return $mapKey;
    }


    // Private Methods
    // =========================================================================

    private function _getEagerLoadingMapForField(FieldInterface $field, ?string $prefix = null): array
    {
        $keys = [];

        if ($field instanceof Matrix) {
            $keys[] = $prefix . $field->handle;

            foreach ($field->getBlockTypes() as $blocktype) {
                foreach ($blocktype->getCustomFields() as $subField) {
                    $nestedKeys = $this->_getEagerLoadingMapForField($subField, $prefix . $field->handle . '.' . $blocktype->handle . ':');

                    if ($nestedKeys) {
                        $keys = array_merge($keys, $nestedKeys);
                    }
                }
            }
        }

        if (Plugin::isPluginInstalledAndEnabled('super-table')) {
            if ($field instanceof SuperTableField) {
                $keys[] = $prefix . $field->handle;
                
                foreach ($field->getBlockTypes() as $blocktype) {
                    foreach ($blocktype->getCustomFields() as $subField) {
                        $nestedKeys = $this->_getEagerLoadingMapForField($subField, $prefix . $field->handle . '.');

                        if ($nestedKeys) {
                            $keys = array_merge($keys, $nestedKeys);
                        }
                    }
                }
            }
        }

        if (Plugin::isPluginInstalledAndEnabled('neo')) {
            if ($field instanceof NeoField) {
                $keys[] = $prefix . $field->handle;
                
                foreach ($field->getBlockTypes() as $blocktype) {
                    foreach ($blocktype->getCustomFields() as $subField) {
                        $nestedKeys = $this->_getEagerLoadingMapForField($subField, $prefix . $field->handle . '.');

                        if ($nestedKeys) {
                            $keys = array_merge($keys, $nestedKeys);
                        }
                    }
                }
            }
        }

        if ($field instanceof BaseRelationField) {
            $keys[] = $prefix . $field->handle;
        }

        return $keys;
    }

}
