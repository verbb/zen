<?php
namespace verbb\zentests\fixtures;

use Craft;
use craft\records\CategoryGroup;
use craft\test\ActiveFixture;

class CategoryGroupsFixture extends ActiveFixture
{
    public $dataFile = __DIR__ . '/data/category-groups.php';
    public $modelClass = CategoryGroup::class;
    public $depends = [CategoryGroupSettingFixture::class, StructuresFixture::class];
}