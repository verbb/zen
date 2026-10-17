<?php
namespace verbb\zentests\fixtures;

use craft\test\fixtures\FieldLayoutFixture as BaseFieldLayoutFixture;

class FieldLayoutsFixture extends BaseFieldLayoutFixture
{
    public $dataFile = __DIR__ . '/data/field-layouts.php';

    private ?array $configuredData = null;

    protected function getData(): array
    {
        if ($this->configuredData !== null) {
            return $this->configuredData;
        }
        $data = parent::getData();
        foreach ($data as &$layout) {
            foreach ($layout['tabs'] as &$tab) {
                foreach ($tab['fields'] as &$field) {
                    $field = MatrixFields::configure($field);
                    if (($field['fieldType'] ?? null) === \craft\fields\Dropdown::class) {
                        array_unshift($field['options'], ['label' => '', 'value' => '']);
                    }
                }
            }
        }
        return $this->configuredData = $data;
    }

    public function unload(): void
    {
        parent::unload();
        MatrixFields::unload();
        $this->configuredData = null;
    }
}