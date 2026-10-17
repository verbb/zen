<?php
namespace verbb\zentests\fixtures;

use craft\records\EntryType;
use craft\test\ActiveFixture;

class EntryTypesFixture extends ActiveFixture
{
    public $dataFile = __DIR__ . '/data/entry-types.php';
    public $modelClass = EntryType::class;
    public $depends = [FieldLayoutsFixture::class, SectionsFixture::class];

    protected function getData(): array
    {
        return array_map(static function(array $row): array {
            unset($row['sectionId'], $row['sortOrder']);
            return $row;
        }, parent::getData());
    }

    public function load(): void
    {
        parent::load();
        foreach (require $this->dataFile as $row) {
            \Craft::$app->getDb()->createCommand()->insert('{{%sections_entrytypes}}', [
                'sectionId' => $row['sectionId'], 'typeId' => $row['id'], 'sortOrder' => 1,
            ])->execute();
        }
        \Craft::$app->set('entries', new \craft\services\Entries());
    }
}