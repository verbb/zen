<?php
namespace verbb\zentests\fixtures;

use Craft;
use craft\records\TagGroup;
use craft\test\ActiveFixture;

class TagGroupsFixture extends ActiveFixture
{
    public $dataFile = __DIR__ . '/data/tag-groups.php';
    public $modelClass = TagGroup::class;
}