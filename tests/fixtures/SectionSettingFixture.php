<?php
namespace verbb\zentests\fixtures;

use craft\records\Section_SiteSettings;
use craft\test\ActiveFixture;

class SectionSettingFixture extends ActiveFixture
{
    public $dataFile = __DIR__ . '/data/section-settings.php';
    public $modelClass = Section_SiteSettings::class;
}