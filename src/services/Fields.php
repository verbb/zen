<?php
namespace verbb\zen\services;

use verbb\zen\fields as fieldTypes;
use verbb\zen\helpers\Plugin;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\events\RegisterComponentTypesEvent;
use craft\fields\BaseRelationField;
use craft\fields\Matrix;

use verbb\supertable\fields\SuperTableField;

class Fields extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_FIELD_TYPES = 'registerFieldTypes';


    // Public Methods
    // =========================================================================

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

        $event = new RegisterComponentTypesEvent([
            'types' => $fieldTypes,
        ]);
        $this->trigger(self::EVENT_REGISTER_FIELD_TYPES, $event);

        return $event->types;
    }

    public function getFieldByType(string $fieldType): ?string
    {
        foreach ($this->getAllFieldTypes() as $registeredFieldType) {
            if ($registeredFieldType::fieldType() === $fieldType) {
                return $registeredFieldType;
            }
        }

        return null;
    }

    public function serializeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        // Cheek if any registered field types match this field
        if ($fieldType = $this->getFieldByType(get_class($field))) {
            if ($customValue = $fieldType::serializeValue($field, $element, $value)) {
                return $customValue;
            }
        } else if ($field instanceof BaseRelationField) {
            // Always use the value from the RelationField class to override
            return fieldTypes\RelationField::serializeValue($field, $element, $value);
        }

        return $field->serializeValue($value, $element);
    }

    public function normalizeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        // Cheek if any registered field types match this field
        if ($fieldType = $this->getFieldByType(get_class($field))) {
            if ($customValue = $fieldType::normalizeValue($field, $element, $value)) {
                return $customValue;
            }
        } else if ($field instanceof BaseRelationField) {
            // Always use the value from the RelationField class to override
            return fieldTypes\RelationField::normalizeValue($field, $element, $value);
        }

        // We don't need to normalize here, as the element will do that, when calling `setFieldValues()`
        return $value;
    }

    public function getFieldForPreview(FieldInterface $field, ElementInterface $element): void
    {
        // Cheek if any registered field types match this field
        if ($fieldType = $this->getFieldByType(get_class($field))) {
            $fieldType::getFieldForPreview($field, $element);
        } else if ($field instanceof BaseRelationField) {
            // Always use the value from the RelationField class to override
            fieldTypes\RelationField::getFieldForPreview($field, $element);
        }
    }

    public function beforeElementImport(ElementInterface $element): bool
    {
        if ($fieldLayout = $element->getFieldLayout()) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                // Some handlers are generic
                if ($field instanceof BaseRelationField) {
                    // Always use the value from the RelationField class to override
                    if (!fieldTypes\RelationField::beforeElementImport($field, $element)) {
                        return false;
                    }
                }

                // Cheek if any registered field types match this field
                if ($fieldType = $this->getFieldByType(get_class($field))) {
                    if (!$fieldType::beforeElementImport($field, $element)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function afterElementImport(ElementInterface $element): void
    {
        if ($fieldLayout = $element->getFieldLayout()) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                // Some handlers are generic
                if ($field instanceof BaseRelationField) {
                    // Always use the value from the RelationField class to override
                    fieldTypes\RelationField::afterElementImport($field, $element);
                }

                // Cheek if any registered field types match this field
                if ($fieldType = $this->getFieldByType(get_class($field))) {
                    $fieldType::afterElementImport($field, $element);
                }
            }
        }
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
