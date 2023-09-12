<?php
namespace verbb\zentests\fixtures;

use Craft;
use craft\records\Site;
use craft\services\Sites;
use craft\test\ActiveFixture;

class SitesFixture extends ActiveFixture
{
    public $modelClass = Site::class;
    public $dataFile = __DIR__ . '/data/sites.php';

    public function load(): void
    {
        parent::load();

        // Because the Sites() class memoizes on initialization we need to set() a new sites class
        // with the updated fixture data
        Craft::$app->set('sites', new Sites());
    }
}