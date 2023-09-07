<?php
namespace verbb\zen\fields;

use verbb\zen\Zen;
use verbb\zen\base\Field as ZenField;
use verbb\zen\helpers\ArrayHelper;
use verbb\zen\models\ElementImportAction;
use verbb\zen\models\TempQuery;

use craft\base\ElementInterface;
use craft\base\FieldInterface;

class RelationField extends ZenField
{
    // Static Methods
    // =========================================================================

    private static array $_cachedElements = [];


    // Static Methods
    // =========================================================================

    public static function serializeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        // Swap IDs to serialized elements for export
        $elements = [];

        foreach ($value->all() as $el) {
            if ($registeredElement = Zen::$plugin->getElements()->getElementByType(get_class($el))) {
                $elements[] = $registeredElement::getSerializedElement($el);
            }
        }

        return $elements;
    }

    public static function normalizeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        // This will be serialized element info. We want to fetch any existing elements, or handle creating them
        $elements = [];

        if (is_array($value)) {
            foreach ($value as $el) {
                $elementType = $el['type'] ?? null;

                if ($registeredElement = Zen::$plugin->getElements()->getElementByType($elementType)) {
                    $elementIdentifier = $registeredElement::elementUniqueIdentifier();
                    $elementUid = $el[$elementIdentifier] ?? null;

                    if ($elementUid) {
                        $foundElement = $registeredElement::elementType()::find()->$elementIdentifier($elementUid)->status(null)->one();

                        if ($foundElement) {
                            $elements[] = $foundElement->id;

                            // Store all found and new elements in a cache for this field. This allows us to create the new ones
                            // at later stages, but we record existing ones, so we retain the order of elements in the field.
                            self::$_cachedElements[$element->uid][$field->handle][$elementUid] = $foundElement;
                        } else {
                            self::$_cachedElements[$element->uid][$field->handle][$elementUid] = $el;
                        }
                    }
                }
            }
        }

        return $elements;
    }

    public static function getFieldForPreview(FieldInterface $field, ElementInterface $element, string $type): void
    {
        $elements = [];
        $elementType = $field::elementType();

        // Check our cached elements for any new ones we need to create
        if ($registeredElement = Zen::$plugin->getElements()->getElementByType($elementType)) {
            $tempElements = self::$_cachedElements[$element->uid][$field->handle] ?? [];

            // If this is the old element, ensure we don't use the cache, which will reflect what the field _will_ be.
            if ($type === 'old') {
                $tempElements = $element->getFieldValue($field->handle)->all();
            }

            foreach ($tempElements as $tempElement) {
                // If this is an existing element, easy
                if ($tempElement instanceof ElementInterface) {
                    $elements[] = $tempElement;
                } else {
                    $elementType = $tempElement['type'] ?? null;
                    $elementUid = $tempElement['uid'] ?? null;

                    if ($elementType && $elementUid) {
                        $elements[] = $registeredElement::getNormalizedElement($tempElement);
                    }
                }
            }
        }

        // Create a custom element query class that instead of querying the database (for elements that don't exist)
        // it just returns the static ones we've already prepared
        if ($elements) {
            $tempQuery = new TempQuery($elementType);
            $tempQuery->setElements($elements);

            $element->setFieldValue($field->handle, $tempQuery);
        }
    }

    public static function handleValueForDiff(FieldInterface $field, mixed &$oldValue, mixed &$newValue): ?array
    {
        // Remove custom fields from the element used in element fields. They're just noise.
        $callback = function(&$item) {
            ArrayHelper::remove($item, 'fields');
        };

        if (is_array($newValue)) {
            array_walk($newValue, $callback);
        }

        if (is_array($oldValue)) {
            array_walk($oldValue, $callback);
        }

        return null;
    }

    public static function beforeElementImport(FieldInterface $field, ElementInterface $element): bool
    {
        // Whenever we're importing a relations field, we might have elements that don't exist, so we need to create them first.
        // Then, assign the newly-created element back to the element's value to save.
        $elements = [];
        $elementType = $field::elementType();

        // Find any new element data (created in `normalizeValue()`) as we need to create that first before import
        if ($registeredElement = Zen::$plugin->getElements()->getElementByType($elementType)) {
            $tempElements = self::$_cachedElements[$element->uid][$field->handle] ?? [];

            foreach ($tempElements as $tempElement) {
                // If this is an already-saved element, skip it, but maintain the position in the field.
                // We could have a mix of existing and new elements.
                if ($tempElement instanceof ElementInterface) {
                    $elements[] = $tempElement->id;
                } else {
                    $fieldElement = $registeredElement::getNormalizedElement($tempElement);

                    // Trigger the import for this element
                    $success = Zen::$plugin->getImport()->runElementAction(new ElementImportAction([
                        'elementType' => $registeredElement,
                        'action' => ElementImportAction::ACTION_SAVE,
                        'data' => $tempElement,
                        'element' => $fieldElement,
                    ]));

                    if ($success && $fieldElement->id) {
                        $elements[] = $fieldElement->id;
                    }
                }
            }
        }

        if ($elements) {
            // Ensure that we're re-populating the field in the same order.
            $element->setFieldValue($field->handle, $elements);
        }

        return true;
    }

}
