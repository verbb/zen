<?php
namespace verbb\zen\helpers;

use Craft;
use craft\db\Connection;
use craft\db\Query;
use craft\helpers\Db as CraftDb;

class Db extends CraftDb
{
    // Properties
    // =========================================================================

    private static array $idsByUids = [];
    private static array $uidsByIds = [];


    // Static Methods
    // =========================================================================

    public static function idByUid(string $table, string $uid, ?Connection $db = null): ?int
    {
        // Wrap database calls with an in-memory cache for performance
        $cachedId = self::$idsByUids[$table][$uid] ?? null;

        if ($cachedId) {
            return $cachedId;
        }

        return self::$idsByUids[$table][$uid] = parent::idByUid($table, $uid, $db);
    }

    public static function uidById(string $table, int $id, ?Connection $db = null): ?string
    {
        // Wrap database calls with an in-memory cache for performance
        $cachedUid = self::$uidsByIds[$table][$id] ?? null;

        if ($cachedUid) {
            return $cachedUid;
        }

        return self::$uidsByIds[$table][$id] = parent::uidById($table, $id, $db);
    }

    public static function idByEmail(string $email): ?int
    {
        $table = '{{%users}}';

        // Wrap database calls with an in-memory cache for performance
        $cachedId = self::$idsByUids[$table][$email] ?? null;

        if ($cachedId) {
            return $cachedId;
        }

        return self::$idsByUids[$table][$email] = (new Query())
            ->select(['id'])
            ->from([$table])
            ->where(['email' => $email])
            ->scalar();
    }

    public static function emailById(int $id): ?string
    {
        $table = '{{%users}}';

        // Wrap database calls with an in-memory cache for performance
        $cachedEmail = self::$uidsByIds[$table][$id] ?? null;

        if ($cachedEmail) {
            return $cachedEmail;
        }

        return self::$uidsByIds[$table][$id] = (new Query())
            ->select(['email'])
            ->from([$table])
            ->where(['id' => $id])
            ->scalar();
    }

}
