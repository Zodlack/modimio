<?php

use yii\db\Migration;

/**
 * Class m240101_000001_create_log_entries_table
 */
class m240101_000001_create_log_entries_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%log_entries}}', [
            'id' => $this->primaryKey(),
            'ip_address' => $this->string(45)->notNull()->comment('IP адрес'),
            'request_datetime' => $this->dateTime()->notNull()->comment('Дата/время запроса'),
            'url' => $this->text()->notNull()->comment('URL запроса'),
            'user_agent' => $this->text()->notNull()->comment('User-Agent'),
            'operating_system' => $this->string(100)->comment('Операционная система'),
            'architecture' => $this->string(10)->comment('Архитектура (x86/x64)'),
            'browser' => $this->string(100)->comment('Браузер'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Индексы для оптимизации запросов
        $this->createIndex('idx_log_entries_ip_address', '{{%log_entries}}', 'ip_address');
        $this->createIndex('idx_log_entries_request_datetime', '{{%log_entries}}', 'request_datetime');
        $this->createIndex('idx_log_entries_operating_system', '{{%log_entries}}', 'operating_system');
        $this->createIndex('idx_log_entries_architecture', '{{%log_entries}}', 'architecture');
        $this->createIndex('idx_log_entries_browser', '{{%log_entries}}', 'browser');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%log_entries}}');
    }
}
