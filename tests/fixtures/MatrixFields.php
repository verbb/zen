<?php
namespace verbb\zentests\fixtures;

use Craft;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use RuntimeException;

/** Builds the fixture's nested content with Craft 5 entry types. */
class MatrixFields
{
    private static array $entryTypes = [];
    private static array $fields = [];

    public static function configure(array $config): array
    {
        unset($config['groupId']);
        if (($config['fieldType'] ?? $config['type'] ?? null) === \craft\fields\Color::class) {
            $config['allowCustomColors'] = true;
        }
        if (!isset($config['blockTypes'])) {
            return $config;
        }
        $types = [];
        foreach ($config['blockTypes'] as $block) {
            $layout = new FieldLayout(['type' => Entry::class]);
            $elements = [];
            foreach ($block['fields'] as $fieldConfig) {
                $handle = $fieldConfig['handle'];
                $fieldConfig['handle'] = $config['handle'] . '_' . $handle;
                $settings = $fieldConfig['typesettings'] ?? [];
                unset($fieldConfig['typesettings']);
                $field = Craft::$app->getFields()->createField($fieldConfig + $settings);
                if ($field instanceof \craft\fields\Color) {
                    $field->allowCustomColors = true;
                }
                if ($field instanceof \craft\fields\Dropdown) {
                    $field->options = [['label' => '', 'value' => ''], ...$field->options];
                }
                if (!Craft::$app->getFields()->saveField($field)) {
                    throw new RuntimeException(json_encode($field->getErrors()));
                }
                self::$fields[] = $field;
                $elements[] = new CustomField($field, ['handle' => $handle]);
            }
            $tab = new FieldLayoutTab(['name' => 'Content', 'layout' => $layout]);
            $tab->setElements($elements);
            $layout->setTabs([$tab]);
            $type = new EntryType(['name' => $block['name'], 'handle' => $block['handle'], 'hasTitleField' => false, 'titleFormat' => '{id}']);
            $type->setFieldLayout($layout);
            if (!Craft::$app->getEntries()->saveEntryType($type)) {
                throw new RuntimeException(json_encode($type->getErrors()));
            }
            self::$entryTypes[] = $type;
            $types[] = $type;
        }
        unset($config['blockTypes']);
        $config['entryTypes'] = $types;
        return $config;
    }

    public static function unload(): void
    {
        foreach (self::$entryTypes as $type) {
            Craft::$app->getEntries()->deleteEntryType($type);
        }
        foreach (self::$fields as $field) {
            Craft::$app->getFields()->deleteField($field);
        }
        self::$entryTypes = self::$fields = [];
    }
}
