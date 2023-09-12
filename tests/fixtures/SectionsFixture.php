<?php
namespace verbb\zentests\fixtures;

use Craft;
use craft\records\Section;
use craft\services\Sections;
use craft\test\ActiveFixture;

class SectionsFixture extends ActiveFixture
{
    public $dataFile = __DIR__ . '/data/sections.php';
    public $modelClass = Section::class;
    public $depends = [SectionSettingFixture::class];

    public function load(): void
    {
        parent::load();

        Craft::$app->set('sections', new Sections());
    }
}