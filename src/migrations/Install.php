<?php
namespace verbb\zen\migrations;

use craft\db\Migration;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->createTables();
        $this->addForeignKeys();

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropForeignKeys();
        $this->removeTables();
        $this->dropProjectConfig();

        return true;
    }

    public function createTables(): void
    {
        $this->archiveTableIfExists('{{%zen_elements}}');
        $this->createTable('{{%zen_elements}}', [
            'id' => $this->primaryKey(),
            'type' => $this->enum('type', ['delete', 'restore'])->notNull(),
            'exportKey' => $this->string(),
            'elementId' => $this->string()->notNull(),
            'elementSiteId' => $this->string()->notNull(),
            'elementType' => $this->string()->notNull(),
            'draftId' => $this->string(),
            'canonicalId' => $this->string(),
            'data' => 'longblob',
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    public function addForeignKeys(): void
    {

    }

    public function insertDefaultData(): void
    {

    }

    public function removeTables(): void
    {
        $this->dropTableIfExists('{{%zen_elements}}');
    }

    public function dropForeignKeys(): void
    {

    }

    public function dropProjectConfig(): void
    {

    }
}
