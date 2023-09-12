<?php
namespace verbb\zentests\fixtures;

use craft\records\CategoryGroup_SiteSettings;
use craft\test\ActiveFixture;

class CategoryGroupSettingFixture extends ActiveFixture
{
    public $dataFile = __DIR__ . '/data/category-group-settings.php';
    public $modelClass = CategoryGroup_SiteSettings::class;
}