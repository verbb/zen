/** Seed real Craft entries and package representative changes in a Zen archive. */

use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fields\PlainText;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use verbb\zen\elements\Entry as ZenEntry;

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$elements = Craft::$app->getElements();
$site = Craft::$app->getSites()->getPrimarySite();
$fieldHandle = 'zenScreenshotSummary';
$sectionHandle = 'zenScreenshotPages';

$field = $fields->getFieldByHandle($fieldHandle);

if (!$field instanceof PlainText) {
    $field = new PlainText([
        'name' => 'Summary',
        'handle' => $fieldHandle,
        'multiline' => true,
        'initialRows' => 3,
    ]);

    if (!$fields->saveField($field)) {
        throw new RuntimeException('Unable to save Zen summary field: ' . Json::encode($field->getErrors()));
    }
}

$section = $entries->getSectionByHandle($sectionHandle);

if (!$section) {
    $entryType = new EntryType([
        'name' => 'Pages',
        'handle' => $sectionHandle . 'Type',
    ]);
    $layout = new FieldLayout(['type' => Entry::class]);
    $tab = new FieldLayoutTab(['name' => Craft::t('app', 'Content'), 'layout' => $layout]);
    $tab->setElements([new EntryTitleField(), new CustomField($field)]);
    $layout->setTabs([$tab]);
    $entryType->setFieldLayout($layout);

    if (!$entries->saveEntryType($entryType)) {
        throw new RuntimeException('Unable to save Zen entry type: ' . Json::encode($entryType->getErrors()));
    }

    $section = new Section([
        'name' => 'Pages',
        'handle' => $sectionHandle,
        'type' => Section::TYPE_CHANNEL,
    ]);
    $section->setEntryTypes([$entryType]);
    $section->setSiteSettings([new Section_SiteSettings([
        'siteId' => $site->id,
        'enabledByDefault' => true,
        'hasUrls' => false,
    ])]);

    if (!$entries->saveSection($section)) {
        throw new RuntimeException('Unable to save Zen section: ' . Json::encode($section->getErrors()));
    }
}

$entryType = $entries->getEntryTypesBySectionId($section->id)[0] ?? null;

if (!$entryType) {
    throw new RuntimeException('Zen screenshot section has no entry type.');
}

foreach (Entry::find()->sectionId($section->id)->siteId($site->id)->status(null)->trashed(null)->all() as $existingEntry) {
    if (!$elements->deleteElement($existingEntry, true)) {
        throw new RuntimeException('Unable to reset a Zen screenshot entry.');
    }
}

$createEntry = static function(string $title, string $slug, string $summary) use ($elements, $entryType, $fieldHandle, $section, $site): Entry {
    $entry = new Entry([
        'sectionId' => $section->id,
        'typeId' => $entryType->id,
        'siteId' => $site->id,
        'slug' => $slug,
        'enabled' => true,
    ]);
    $entry->title = $title;
    $entry->setFieldValue($fieldHandle, $summary);

    if (!$elements->saveElement($entry)) {
        throw new RuntimeException('Unable to save Zen screenshot entry: ' . Json::encode($entry->getErrors()));
    }

    return $entry;
};

$changedEntry = $createEntry(
    'About our studio',
    'about-our-studio',
    'A small independent team building thoughtful tools for Craft CMS.',
);
$changedPayload = ZenEntry::getSerializedElement($changedEntry);
$changedPayload['title'] = 'About the Verbb studio';
$changedPayload['slug'] = 'about-the-verbb-studio';
$changedFieldKey = array_key_first($changedPayload['fields']);
$changedPayload['fields'][$changedFieldKey] = 'Meet the independent team designing dependable Craft CMS plugins for content teams and developers.';

$addedEntry = $createEntry(
    'Spring campaign brief',
    'spring-campaign-brief',
    'The launch plan, campaign milestones and approved publishing notes.',
);
$addedPayload = ZenEntry::getSerializedElement($addedEntry);

if (!$elements->deleteElement($addedEntry, true)) {
    throw new RuntimeException('Unable to remove the source-only Zen entry.');
}

$deletedEntry = $createEntry(
    'Retired seasonal landing page',
    'retired-seasonal-landing-page',
    'A seasonal campaign that has now finished.',
);
$deletedPayload = ZenEntry::getSerializedElement($deletedEntry);

$restoredEntry = $createEntry(
    'Editorial style guide',
    'editorial-style-guide',
    'Voice, terminology and publishing guidance for the content team.',
);
$restoredPayload = ZenEntry::getSerializedElement($restoredEntry);

if (!$elements->deleteElement($restoredEntry)) {
    throw new RuntimeException('Unable to trash the Zen entry prepared for restoration.');
}

$payload = [
    ZenEntry::class => [
        'modified' => [$changedPayload, $addedPayload],
        'deleted' => [$deletedPayload],
        'restored' => [$restoredPayload],
    ],
];

$filename = 'zen-screenshot-feature-tour.zip';
$tempPath = Craft::$app->getPath()->getTempPath();
$zipPath = $tempPath . DIRECTORY_SEPARATOR . $filename;
$extractedPath = $tempPath . DIRECTORY_SEPARATOR . basename($filename, '.zip');

if (is_file($zipPath)) {
    unlink($zipPath);
}

if (is_dir($extractedPath)) {
    FileHelper::removeDirectory($extractedPath);
}

$zip = new ZipArchive();

if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Unable to create the Zen screenshot archive.');
}

$zip->addFromString('content.json', Json::encode($payload));
$zip->close();

echo Json::encode([
    'startRoute' => '/admin/zen',
    'configureRoute' => '/admin/zen/import/configure/' . $filename,
], JSON_THROW_ON_ERROR);
