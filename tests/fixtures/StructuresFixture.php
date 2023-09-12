<?php
namespace verbb\zentests\fixtures;

use Craft;
use craft\records\Structure;
use craft\test\ActiveFixture;

class StructuresFixture extends ActiveFixture
{
    public $dataFile = __DIR__ . '/data/structures.php';
    public $modelClass = Structure::class;
}