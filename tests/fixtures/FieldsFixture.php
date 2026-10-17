<?php
namespace verbb\zentests\fixtures;

use craft\test\fixtures\FieldFixture;

class FieldsFixture extends FieldFixture
{
    public $dataFile = __DIR__ . '/data/fields.php';

    private ?array $configuredData = null;

    protected function getData(): array
    {
        if ($this->configuredData !== null) {
            return $this->configuredData;
        }
        $data = parent::getData();
        $data = array_map([MatrixFields::class, 'configure'], $data);
        return $this->configuredData = $data;
    }

    public function unload(): void
    {
        parent::unload();
        MatrixFields::unload();
        $this->configuredData = null;
    }
}