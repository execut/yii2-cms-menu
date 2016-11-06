<?php

use yii\db\Schema;
use yii\db\Migration;

class m150521_135055_add_anchor_field extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%menu_item}}', 'anchor', Schema::TYPE_STRING.'(255) NOT NULL');
    }

    public function safeDown()
    {
        $this->dropColumn('{{%menu_item}}', 'anchor');
    }
}
