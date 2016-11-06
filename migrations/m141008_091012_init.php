<?php

use yii\db\Schema;
use yii\db\Migration;

class m141008_091012_init extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;

        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        
        // Drop the default user table
        if ($this->db->schema->getTableSchema('menu', true) !== null) {
            $this->dropTable('{{%menu}}');
        }

        if ($this->db->driverName === 'pgsql') {
            $this->execute('CREATE TYPE menu_item_entity AS ENUM (\'page\',\'menu-item\', \'url\')');
        }

        // Create 'menu' table
        $this->createTable('{{%menu}}', [
            'id'                    => $this->primaryKey(),
            'name'                  => $this->string()->notNull(),
            'max_level'             => $this->integer(3)->unsigned()->notNull()->defaultValue(2),
            'created_at'            => $this->integer()->unsigned()->notNull(),
            'updated_at'            => $this->integer()->unsigned()->notNull(),
        ], $tableOptions);

        // Create 'menu_item' table
        $this->createTable('{{%menu_item}}', [
            'id'                    => $this->primaryKey(),
            'menu_id'               => $this->integer()->notNull(),
            'parent_id'             => $this->integer()->unsigned()->notNull(),
            'entity'                => "menu_item_entity NOT NULL DEFAULT 'page'",
            'entity_id'             => $this->integer()->unsigned()->notNull(),
            'level'                 => $this->integer()->notNull()->defaultValue('0'),
            'url'                   => $this->string()->notNull(),
            'position'              => $this->integer()->notNull()->defaultValue('0'),
            'active'                => $this->integer(3)->unsigned()->notNull()->defaultValue('1'),
            'created_at'            => $this->integer()->unsigned()->notNull(),
            'updated_at'            => $this->integer()->unsigned()->notNull(),
        ], $tableOptions);
        
        // Create indexes on the 'menu_item' table
        $this->createIndex('menu_item_menu_id_i', '{{%menu_item}}', 'menu_id');
        $this->createIndex('menu_item_parent_id_i', '{{%menu_item}}', 'parent_id');
        $this->createIndex('menu_item_entity_i', '{{%menu_item}}', 'entity');
        $this->createIndex('menu_item_entity_id_i', '{{%menu_item}}', 'entity_id');
        $this->addForeignKey('FK_MENU_ITEM_MENU_ID', '{{%menu_item}}', 'menu_id', '{{%menu}}', 'id', 'CASCADE', 'RESTRICT');

        // Create 'menu_item_lang' table
        $this->createTable('{{%menu_item_lang}}', [
            'menu_item_id'               => $this->integer()->notNull(),
            'language'              => $this->string(10)->notNull(),
            'name'                  => $this->string()->notNull(),
            'created_at'            => $this->integer()->unsigned()->notNull(),
            'updated_at'            => $this->integer()->unsigned()->notNull()
        ], $tableOptions);
       
        // Create indexes on the 'menu_item_lang' table
        $this->addPrimaryKey('menu_item_menu_id_language', '{{%menu_item_lang}}', ['menu_item_id', 'language']);
        $this->createIndex('menu_item_lang_language_i', '{{%menu_item_lang}}', 'language');
        $this->addForeignKey('FK_MENU_ITEM_LANG_MENU_ITEM_ID', '{{%menu_item_lang}}', 'menu_item_id', '{{%menu_item}}', 'id', 'CASCADE', 'RESTRICT');
    }

    public function safeDown()
    {
        $this->dropTable('menu_item_lang');
        $this->dropTable('menu_item');
        $this->dropTable('menu');
    }
}
