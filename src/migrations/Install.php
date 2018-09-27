<?php
/**
 * Portal plugin for Craft CMS 3.x
 *
 * Brings enhanced Live Preview capabilities to Craft, including a device toggle and additional targets in the main Live Preview tool.
 *
 * @link      https://angell.io
 * @copyright Copyright (c) 2018 Angell & Co
 */

namespace angellco\portal\migrations;

use angellco\portal\Portal;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

/**
 * Portal Install Migration
 *
 * If your plugin needs to create any custom database tables when it gets installed,
 * create a migrations/ folder within your plugin folder, and save an Install.php file
 * within it using the following template:
 *
 * If you need to perform any additional actions on install/uninstall, override the
 * safeUp() and safeDown() methods.
 *
 * @author    Angell & Co
 * @package   Portal
 * @since     1.0.0
 */
class Install extends Migration
{

    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * This method contains the logic to be executed when applying this migration.
     * This method differs from [[up()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[up()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

    /**
     * This method contains the logic to be executed when removing this migration.
     * This method differs from [[down()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[down()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables needed for the Records used by the plugin
     *
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

        // portal_targets table
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%portal_targets}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable('{{%portal_targets}}', [
                'id'          => $this->primaryKey(),
                'name'        => $this->string()->notNull()->defaultValue(''),
                'context'     => $this->string()->notNull()->defaultValue('global'),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid'         => $this->uid(),
            ]);
        }

        // portal_targets_sites table
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%portal_targets_sites}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable('{{%portal_targets_sites}}', [
                'id'          => $this->primaryKey(),
                'targetId'     => $this->integer()->notNull(),
                'siteId'      => $this->integer()->null(),
                'template'    => $this->string(500),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid'         => $this->uid(),
            ]);
        }

        return $tablesCreated;
    }

    /**
     * Creates the indexes needed for the Records used by the plugin
     *
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(null, '{{%portal_targets}}', [ 'name' ], true);
        $this->createIndex(null, '{{%portal_targets_sites}}', [ 'targetId', 'siteId' ], true);
        $this->createIndex(null, '{{%portal_targets_sites}}', [ 'targetId', 'siteId', 'template' ], true);
        $this->createIndex(null, '{{%portal_targets_sites}}', [ 'siteId' ], false);

    }

    /**
     * Creates the foreign keys needed for the Records used by the plugin
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(null, '{{%portal_targets_sites}}', [ 'targetId' ], '{{%portal_targets}}', [ 'id' ], 'CASCADE', null);
        $this->addForeignKey(null, '{{%portal_targets_sites}}', [ 'siteId' ], '{{%sites}}', [ 'id' ], 'CASCADE', 'CASCADE');
    }

    /**
     * Populates the DB with the default data.
     *
     * @return void
     */
    protected function insertDefaultData()
    {
    }

    /**
     * Removes the tables needed for the Records used by the plugin
     *
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists('{{%portal_targets_sites}}');
        $this->dropTableIfExists('{{%portal_targets}}');
    }

}
