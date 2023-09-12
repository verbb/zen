<?php
namespace verbb\zentests\unit;

use verbb\zentests\fixtures\SectionsFixture;
use verbb\zentests\fixtures\EntryTypesFixture;
use verbb\zentests\fixtures\CategoryGroupsFixture;
use verbb\zentests\fixtures\TagGroupsFixture;

use verbb\zen\Zen;

use Craft;
use craft\elements\Entry;
use craft\helpers\Db;

use Codeception\Test\Unit;

use UnitTester;
use DateTime;

class EntryTest extends Unit
{
    protected UnitTester $tester;

    public function _fixtures(): array
    {
        return [
            'sections' => [
                'class' => SectionsFixture::class,
            ],
            'entry-types' => [
                'class' => EntryTypesFixture::class,
            ],
            'category-groups' => [
                'class' => CategoryGroupsFixture::class,
            ],
            'tag-groups' => [
                'class' => TagGroupsFixture::class,
            ],
        ];
    }

    public function testNormalization()
    {
        $entryData = [
            'type' => 'craft\\elements\\Entry',
            'title' => 'Test Entry',
            'slug' => 'test-entry',
            'uid' => 'test-entry-1000------------------uid',
            'enabled' => true,
            'dateCreated' => '2023-01-30 23:01:04',
            'siteUid' => Craft::$app->getSites()->getPrimarySite()->uid,
            'fields' => [],
            'postDate' => '2023-02-14 19:35:00',
            'sectionUid' => 'section-1000---------------------uid',
            'typeUid' => 'entry-type-1000------------------uid',
        ];

        $entry = \verbb\zen\elements\Entry::getNormalizedElement($entryData, false);

        // Validate the entry created
        self::assertNotNull($entry, 'Entry element is not null.');

        self::assertSame($entryData['title'], $entry->title, 'Entry title is valid.');
        self::assertSame($entryData['slug'], $entry->slug, 'Entry slug is valid.');
        self::assertSame($entryData['uid'], $entry->uid, 'Entry uid is valid.');
        self::assertSame($entryData['enabled'], $entry->enabled, 'Entry enabled is valid.');
        self::assertSame(1000, $entry->sectionId, 'Entry sectionId is valid.');
        self::assertSame(1000, $entry->typeId, 'Entry typeId is valid.');
        self::assertSame(1, $entry->siteId, 'Entry siteId is valid.');

        self::assertInstanceOf(DateTime::class, $entry->dateCreated, 'Entry dateCreated is DateTime.');
        self::assertSame($entryData['dateCreated'], Db::prepareDateForDb($entry->dateCreated), 'Entry dateCreated is valid.');

        self::assertInstanceOf(DateTime::class, $entry->postDate, 'Entry postDate is DateTime.');
        self::assertSame($entryData['postDate'], Db::prepareDateForDb($entry->postDate), 'Entry postDate is valid.');
    }

    public function testImportWithFields()
    {
        Craft::$app->setEdition(Craft::Pro);

        // Fields need to be indexed with their handle+uid.
        $fields = [];
        $uidMap = [];

        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            $uidMap[$field->handle] = $field->handle . ':' . $field->uid;
            $fields[$field->handle . ':' . $field->uid] = $field;
        }

        $matrixField = Craft::$app->getFields()->getFieldByHandle('testMatrixField1');
        $blockTypeUid = null;

        foreach ($matrixField->getBlockTypes() as $blockType) {
            $blockTypeUid = $blockType->uid;

            foreach ($blockType->getCustomFields() as $field) {
                $uidMap[$matrixField->handle . ':' . $field->handle] = $field->handle . ':' . $field->uid;
                $fields[$field->handle . ':' . $field->uid] = $field;
            }
        }

        $entryData = [
            'type' => 'craft\\elements\\Entry',
            'title' => 'Test Entry With Fields',
            'slug' => 'test-entry-with-fields',
            'uid' => 'test-entry-1001------------------uid',
            'enabled' => true,
            'dateCreated' => '2023-01-30 23:01:04',
            'siteUid' => Craft::$app->getSites()->getPrimarySite()->uid,
            'fields' => [
                ($uidMap['testCategoriesField1'] ?? null) => [
                    [
                        'type' => 'craft\\elements\\Category',
                        'title' => 'New Category',
                        'slug' => 'new-category',
                        'uid' => 'test-category-1001---------------uid',
                        'enabled' => true,
                        'dateCreated' => '2023-03-23 10:05:03',
                        'siteUid' => Craft::$app->getSites()->getPrimarySite()->uid,
                        'fields' => [
                            'testPlainTextField1' => 'Category Testing Plain Text',
                        ],
                        'groupUid' => 'category-group-1001--------------uid',
                    ],
                ],
                ($uidMap['testCheckboxesField1'] ?? null) => [
                    'option1',
                    'option2',
                ],
                ($uidMap['testColorField1'] ?? null) => '#530edd',
                ($uidMap['testDateField1'] ?? null) => '2023-04-04 07:00:00',
                ($uidMap['testDropdownField1'] ?? null) => 'option2',
                ($uidMap['testEmailField1'] ?? null) => 'test@test.com',
                ($uidMap['testEntriesField1'] ?? null) => [
                    [
                        'type' => 'craft\\elements\\Entry',
                        'title' => 'New Entry',
                        'slug' => 'new-entry',
                        'uid' => 'test-entry-1002------------------uid',
                        'enabled' => true,
                        'dateCreated' => '2023-03-11 22:29:28',
                        'siteUid' => Craft::$app->getSites()->getPrimarySite()->uid,
                        'fields' => [
                            'testPlainTextField1' => 'Entry Testing Plain Text',
                        ],
                        'postDate' => '2023-03-11 22:29:00',
                        'authorEmail' => 'test@test.com',
                        'sectionUid' => 'section-1001---------------------uid',
                        'typeUid' => 'entry-type-1001------------------uid',
                    ],
                ],
                ($uidMap['testLightswitchField1'] ?? null) => true,
                ($uidMap['testMatrixField1'] ?? null) => [
                    [
                        'type' => $blockTypeUid,
                        'enabled' => true,
                        'collapsed' => false,
                        'uid' => 'matrix-block-1001----------------uid',
                        'fields' => [
                            ($uidMap['testMatrixField1:testCategoriesField1'] ?? null) => [
                                [
                                    'type' => 'craft\\elements\\Category',
                                    'title' => 'New Category',
                                    'slug' => 'new-category',
                                    'uid' => 'test-category-1001---------------uid',
                                    'enabled' => true,
                                    'dateCreated' => '2023-03-23 10:05:03',
                                    'siteUid' => Craft::$app->getSites()->getPrimarySite()->uid,
                                    'fields' => [
                                        'testPlainTextField1' => 'Category Testing Plain Text',
                                    ],
                                    'groupUid' => 'category-group-1001--------------uid',
                                ],
                            ],
                            ($uidMap['testMatrixField1:testCheckboxesField1'] ?? null) => [
                                'option1',
                                'option2',
                            ],
                            ($uidMap['testMatrixField1:testColorField1'] ?? null) => '#530edd',
                            ($uidMap['testMatrixField1:testDateField1'] ?? null) => '2023-04-04 07:00:00',
                        ],
                    ],
                    [
                        'type' => $blockTypeUid,
                        'enabled' => true,
                        'collapsed' => false,
                        'uid' => 'matrix-block-1002----------------uid',
                        'fields' => [
                            ($uidMap['testMatrixField1:testDropdownField1'] ?? null) => 'option2',
                            ($uidMap['testMatrixField1:testEmailField1'] ?? null) => 'test@test.com',
                            ($uidMap['testMatrixField1:testEntriesField1'] ?? null) => [
                                [
                                    'type' => 'craft\\elements\\Entry',
                                    'title' => 'New Entry',
                                    'slug' => 'new-entry',
                                    'uid' => 'test-entry-1002------------------uid',
                                    'enabled' => true,
                                    'dateCreated' => '2023-03-11 22:29:28',
                                    'siteUid' => Craft::$app->getSites()->getPrimarySite()->uid,
                                    'fields' => [
                                        'testPlainTextField1' => 'Entry Testing Plain Text',
                                    ],
                                    'postDate' => '2023-03-11 22:29:00',
                                    'authorEmail' => 'test@test.com',
                                    'sectionUid' => 'section-1001---------------------uid',
                                    'typeUid' => 'entry-type-1001------------------uid',
                                ],
                            ],
                            ($uidMap['testMatrixField1:testLightswitchField1'] ?? null) => true,
                        ],
                    ],
                ],
                ($uidMap['testMoneyField1'] ?? null) => '12300',
                ($uidMap['testMultiSelectField1'] ?? null) => [
                    'option1',
                    'option2',
                ],
                ($uidMap['testNumberField1'] ?? null) => 234,
                ($uidMap['testPlainTextField1'] ?? null) => 'Testing Plain Text',
                ($uidMap['testRadioButtonsField1'] ?? null) => 'option2',
                ($uidMap['testTableField1'] ?? null) => [
                    [
                        'col1' => true,
                        'col2' => '#530edd',
                        'col3' => '2023-04-04 07:00:00',
                        'col4' => 'option2',
                        'col5' => 'test@test.com',
                        'col6' => true,
                        'col7' => 'Multi-line Text',
                        'col8' => 1234,
                        'col10' => 'Single-line Text',
                        'col11' => '2023-04-04 03:00:00',
                        'col12' => 'http://www.google.com',
                    ],
                ],
                ($uidMap['testTagsField1'] ?? null) => [
                    [
                        'type' => 'craft\\elements\\Tag',
                        'title' => 'New Tag',
                        'slug' => 'new-tag',
                        'uid' => 'test-tag-1001--------------------uid',
                        'enabled' => true,
                        'dateCreated' => '2023-03-23 10:05:03',
                        'siteUid' => Craft::$app->getSites()->getPrimarySite()->uid,
                        'fields' => [
                            'testPlainTextField1' => 'Category Testing Plain Text',
                        ],
                        'groupUid' => 'tag-group-1001-------------------uid',
                    ],
                ],
                ($uidMap['testTimeField1'] ?? null) => '03:00:00',
                ($uidMap['testUrlField1'] ?? null) => 'http://www.google.com',
                ($uidMap['testUsersField1'] ?? null) => [
                    [
                        'type' => 'craft\\elements\\User',
                        'uid' => 'test-user-1001-------------------uid',
                        'enabled' => true,
                        'dateCreated' => '2023-03-08 09:11:05',
                        'siteUid' => Craft::$app->getSites()->getPrimarySite()->uid,
                        'fields' => [
                            
                        ],
                        'active' => true,
                        'pending' => false,
                        'locked' => false,
                        'suspended' => false,
                        'admin' => true,
                        'username' => 'test@test.com',
                        'email' => 'test@test.com',
                        'hasDashboard' => true,
                        'name' => 'test@test.com',
                        'friendlyName' => 'test@test.com',
                        'groupUids' => [
                            
                        ],
                        'permissions' => [
                            
                        ]
                    ],
                ],
            ],
            'postDate' => '2023-02-14 19:35:00',
            'sectionUid' => 'section-1001---------------------uid',
            'typeUid' => 'entry-type-1001------------------uid',
        ];

        // codecept_debug($entryData);

        $payload = [
            'verbb\\zen\\elements\\Entry' => [
                'modified' => [
                    $entryData
                ],
            ],
        ];

        $elementData = Zen::$plugin->getImport()->getImportConfiguration($payload, true);

        foreach ($elementData as $data) {
            foreach ($data['rows'] as $elementIndex => $elementImportAction) {
                $success = Zen::$plugin->getImport()->runElementAction($elementImportAction);

                self::assertTrue($success, 'Entry element was imported successfully.');
            }
        }

        // Find the imported element
        $entry = Entry::find()->uid('test-entry-1001------------------uid')->one();

        // codecept_debug($entry);

        self::assertNotNull($entry, 'Entry was imported (not null).');

        self::assertSame($entryData['title'], $entry->title, 'Entry title is valid.');
        self::assertSame($entryData['slug'], $entry->slug, 'Entry slug is valid.');
        self::assertSame($entryData['uid'], $entry->uid, 'Entry uid is valid.');
        self::assertSame($entryData['enabled'], $entry->enabled, 'Entry enabled is valid.');
        self::assertSame(1001, $entry->sectionId, 'Entry sectionId is valid.');
        self::assertSame(1001, $entry->typeId, 'Entry typeId is valid.');
        self::assertSame(1, $entry->siteId, 'Entry siteId is valid.');

        self::assertInstanceOf(DateTime::class, $entry->dateCreated, 'Entry dateCreated is DateTime.');
        self::assertSame($entryData['dateCreated'], Db::prepareDateForDb($entry->dateCreated), 'Entry dateCreated is valid.');

        self::assertInstanceOf(DateTime::class, $entry->postDate, 'Entry postDate is DateTime.');
        self::assertSame($entryData['postDate'], Db::prepareDateForDb($entry->postDate), 'Entry postDate is valid.');

        // Check all fields
        foreach ($entryData['fields'] as $fieldHandleUid => $value) {
            $field = $fields[$fieldHandleUid] ?? null;

            if ($field) {
                $fieldValue = $field->serializeValue($entry->getFieldValue($field->handle), $entry);

                // Special handling for relation fields, which won't be the same as their serialized version
                if ($field->handle === 'testCategoriesField1') {
                    $value = ['2'];
                } else if ($field->handle === 'testEntriesField1') {
                    $value = ['3'];
                } else if ($field->handle === 'testTagsField1') {
                    $value = ['5'];
                } else if ($field->handle === 'testUsersField1') {
                    $value = ['6'];
                }

                if ($field->handle === 'testMatrixField1') {
                    $value = [
                        8 => [
                            'type' => 'testBlock',
                            'enabled' => true,
                            'collapsed' => false,
                            'fields' => [
                                'testAssetsField1' => [],
                                'testCategoriesField1' => [],
                                'testCheckboxesField1' => ['option1', 'option2'],
                                'testColorField1' => '#530edd',
                                'testDateField1' => '2023-04-04 07:00:00',
                                'testDropdownField1' => null,
                                'testEmailField1' => null,
                                'testEntriesField1' => [],
                                'testLightswitchField1' => false,
                                'testMoneyField1' => null,
                                'testMultiSelectField1' => [],
                                'testNumberField1' => null,
                                'testPlainTextField1' => null,
                                'testRadioButtonsField1' => null,
                                'testTableField1' => [
                                    [
                                        'col1' => null,
                                        'col2' => null,
                                        'col3' => null,
                                        'col4' => null,
                                        'col5' => null,
                                        'col6' => null,
                                        'col7' => null,
                                        'col8' => null,
                                        'col10' => null,
                                        'col11' => null,
                                        'col12' => null,
                                    ],
                                ],
                                'testTagsField1' => [],
                                'testTimeField1' => null,
                                'testUrlField1' => null,
                                'testUsersField1' => [],
                            ],
                        ],
                        9 => [
                            'type' => 'testBlock',
                            'enabled' => true,
                            'collapsed' => false,
                            'fields' => [
                                'testAssetsField1' => [],
                                'testCategoriesField1' => [],
                                'testCheckboxesField1' => [],
                                'testColorField1' => null,
                                'testDateField1' => null,
                                'testDropdownField1' => 'option2',
                                'testEmailField1' => 'test@test.com',
                                'testEntriesField1' => [],
                                'testLightswitchField1' => true,
                                'testMoneyField1' => null,
                                'testMultiSelectField1' => [],
                                'testNumberField1' => null,
                                'testPlainTextField1' => null,
                                'testRadioButtonsField1' => null,
                                'testTableField1' => [
                                    [
                                        'col1' => null,
                                        'col2' => null,
                                        'col3' => null,
                                        'col4' => null,
                                        'col5' => null,
                                        'col6' => null,
                                        'col7' => null,
                                        'col8' => null,
                                        'col10' => null,
                                        'col11' => null,
                                        'col12' => null,
                                    ],
                                ],
                                'testTagsField1' => [],
                                'testTimeField1' => null,
                                'testUrlField1' => null,
                                'testUsersField1' => [],
                            ],
                        ],
                    ];
                }

                // codecept_debug([$field->handle, $fieldValue]);

                self::assertSame($value, $fieldValue);
            }
        }
    }
}