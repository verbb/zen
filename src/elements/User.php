<?php
namespace verbb\zen\elements;

use verbb\zen\base\Element as ZenElement;
use verbb\zen\helpers\Db;
use verbb\zen\models\ElementImportAction;
use verbb\zen\models\ImportFieldTab;

use Craft;
use craft\base\ElementInterface;
use craft\db\Table;
use craft\elements\Asset as AssetElement;
use craft\elements\User as UserElement;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;

class User extends ZenElement
{
    // Properties
    // =========================================================================

    private static array $_permissions = [];
    

    // Static Methods
    // =========================================================================

    public static function elementType(): string
    {
        return UserElement::class;
    }

    public static function elementUniqueIdentifier(): string
    {
        return 'email';
    }

    public static function exportKeyForElement(ElementInterface $element): array
    {
        return ['group' => $element->group->handle];
    }

    public static function getExportOptions(ElementQueryInterface $query): array|bool
    {
        // There's not a simple "ungrouped" query, so use IDs
        $tempQuery = clone $query;
        $groupHandles = ArrayHelper::getColumn(Craft::$app->getUserGroups()->getAllGroups(), 'handle');
        $ungroupedIds = $tempQuery->group(array_merge(['not', $groupHandles]))->ids();

        $options = [[
            'label' => Craft::t('zen', 'Ungrouped'),
            'criteria' => ['id' => $ungroupedIds],
            'count' => $tempQuery->id($ungroupedIds)->count(),
        ]];

        foreach (Craft::$app->getUserGroups()->getAllGroups() as $group) {
            $options[] = [
                'label' => $group->name,
                'criteria' => ['group' => $group->handle],
                'count' => $query->group($group)->count(),
            ];
        }

        return $options;
    }

    public static function defineSerializedElement(ElementInterface $element, array $data): array
    {
        // Serialize any additional attributes. Be sure to switch out IDs for UIDs.
        $data['active'] = $element->active;
        $data['pending'] = $element->pending;
        $data['locked'] = $element->locked;
        $data['suspended'] = $element->suspended;
        $data['admin'] = $element->admin;
        $data['username'] = $element->username;
        $data['email'] = $element->email;
        $data['password'] = $element->password;
        $data['hasDashboard'] = $element->hasDashboard;
        $data['name'] = $element->name;
        $data['fullName'] = $element->fullName;
        $data['friendlyName'] = $element->friendlyName;
        $data['groupUids'] = ArrayHelper::getColumn($element->getGroups(), 'uid');
        $data['permissions'] = Craft::$app->getUserPermissions()->getPermissionsByUserId($element->id);

        if ($photo = $element->getPhoto()) {
            $data['photo'] = Asset::getSerializedElement($photo);
        }

        return $data;
    }

    public static function defineNormalizedElement(array $data): array
    {
        $groupUids = ArrayHelper::remove($data, 'groupUids');

        foreach ($groupUids as $groupUid) {
            $data['groups'][] = Db::idByUid(Table::USERGROUPS, $groupUid);
        }

        // Have to store on this instance, because it's not a property on the element
        self::$_permissions[$data['email']] = ArrayHelper::remove($data, 'permissions');

        if ($photo = ArrayHelper::remove($data, 'photo')) {
            $data['photo'] = Asset::getNormalizedElement($photo);
        }

        return $data;
    }

    public static function defineImportTableAttributes(): array
    {
        return [
            'email' => Craft::t('zen', 'Email'),
            'username' => Craft::t('zen', 'Username'),
            'name' => Craft::t('zen', 'Full Name'),
        ];
    }

    public static function defineImportTableValues(array $diffs, ?ElementInterface $newElement, ?ElementInterface $currentElement, ?string $state): array
    {
        // Use either the new or current element to get data for, at this generic stage.
        $element = $newElement ?? $currentElement ?? null;

        if (!$element) {
            return [];
        }

        return [
            'email' => $element->email,
            'username' => $element->username,
            'name' => $element->name,
        ];
    }

    public static function defineImportFieldTabs(ElementInterface $element, string $type): array
    {
        $groupsOptions = [];
        $permissionsOptions = [];

        foreach (Craft::$app->getUserPermissions()->getAllPermissions() as $permission) {
            self::_getPermissionsOptions($permission['permissions'], $permissionsOptions);
        }

        foreach (Craft::$app->getUserGroups()->getAllGroups() as $group) {
            $groupsOptions[] = [
                'label' => $group->name,
                'value' => $group->id,
            ];
        }

        // Values will be different depending on existing or new content
        if ($type === 'new') {
            $groups = $element->groups;
            $permissions = self::$_permissions[$element->email] ?? [];
        } else {
            $groups = ArrayHelper::getColumn($element->groups, 'id', []);
            $permissions = Craft::$app->getUserPermissions()->getPermissionsByUserId($element->id);
        }

        return [
            new ImportFieldTab([
                'name' => Craft::t('zen', 'Meta'),
                'fields' => [
                    'email' => Cp::textFieldHtml([
                        'label' => Craft::t('app', 'Email'),
                        'id' => 'email',
                        'value' => $element->email,
                        'disabled' => true,
                    ]),
                    'username' => Cp::textFieldHtml([
                        'label' => Craft::t('app', 'Username'),
                        'id' => 'username',
                        'value' => $element->username,
                        'disabled' => true,
                    ]),
                    'name' => Cp::textFieldHtml([
                        'label' => Craft::t('app', 'Full Name'),
                        'id' => 'name',
                        'value' => $element->name,
                        'disabled' => true,
                    ]),
                    'photo' => Cp::elementSelectFieldHtml([
                        'label' => Craft::t('app', 'Photo'),
                        'id' => 'photo',
                        'elementType' => AssetElement::class,
                        'elements' => [$element->photo],
                        'disabled' => true,
                        'single' => true,
                    ]),
                    'admin' => Cp::lightswitchFieldHtml([
                        'label' => Craft::t('app', 'Admin'),
                        'id' => 'admin',
                        'on' => $element->admin,
                        'disabled' => true,
                    ]),
                    'groupUids' => Cp::checkboxSelectFieldHtml([
                        'label' => Craft::t('app', 'Groups'),
                        'id' => 'groupUids',
                        'values' => $groups,
                        'options' => $groupsOptions,
                        'disabled' => true,
                    ]),
                    'dateCreated' => Cp::dateTimeFieldHtml([
                        'label' => Craft::t('app', 'Date Created'),
                        'id' => 'dateCreated',
                        'value' => $element->dateCreated,
                        'disabled' => true,
                    ]),
                ],
            ]),
            new ImportFieldTab([
                'name' => Craft::t('zen', 'Permissions'),
                'fields' => [
                    'permissions' => Cp::checkboxSelectFieldHtml([
                        'label' => Craft::t('app', 'Permissions'),
                        'id' => 'permissions',
                        'values' => $permissions,
                        'options' => $permissionsOptions,
                        'disabled' => true,
                    ]),
                ],
            ]),
        ];
    }

    public static function afterImport(ElementImportAction $importAction): void
    {
        if (in_array($importAction->action, [ElementImportAction::ACTION_SAVE, ElementImportAction::ACTION_RESTORE])) {
            // Assign user groups and permissions
            $permissions = self::$_permissions[$importAction->element->email] ?? [];

            Craft::$app->getUserPermissions()->saveUserPermissions($importAction->element->id, $permissions);
            Craft::$app->getUsers()->assignUserToGroups($importAction->element->id, $importAction->element->groups);

            if ($photo = $importAction->element->photo) {
                // Because the photo might've already been taken care of (included in the payload as an asset) we need to check if already
                // uploaded and moved from the temp directory.
                if ($existingPhoto = AssetElement::find()->uid($photo->uid)->one()) {
                    $importAction->element->setPhoto($existingPhoto);
                } else {
                    // Check if we've already processed the asset file, which will have been moved out of the temp folder
                    $subpath = (string)Craft::$app->getProjectConfig()->get('users.photoSubpath');
                    $subpath = Craft::$app->getView()->renderObjectTemplate($subpath, $importAction->element);
                    $photo->folderId = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume($subpath, $photo->volume)->id;

                    Craft::$app->getElements()->saveElement($photo);

                    $importAction->element->setPhoto($photo);
                }

                Craft::$app->getElements()->saveElement($importAction->element, false);
            }
        }

        parent::afterImport($importAction);
    }


    // Private Methods
    // =========================================================================

    private static function _getPermissionsOptions(array $permissions, array &$combined): void
    {
        foreach ($permissions as $permissionKey => $permissionValue) {
            $combined[] = [
                'label' => $permissionValue['label'],
                'value' => strtolower($permissionKey),
            ];

            if (isset($permissionValue['nested'])) {
                self::_getPermissionsOptions($permissionValue['nested'], $combined);
            }
        }
    }
}
