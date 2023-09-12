<?php
namespace verbb\zentests\fixtures;

use craft\records\EntryType;
use craft\test\ActiveFixture;

class EntryTypesFixture extends ActiveFixture
{
    public $dataFile = __DIR__ . '/data/entry-types.php';
    public $modelClass = EntryType::class;
    public $depends = [FieldLayoutsFixture::class, SectionsFixture::class];
}