<?php
/**
 * Copyright 2017 Lengow SAS
 *
 * NOTICE OF LICENSE
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * It is available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/agpl-3.0
 *
 * @category    Lengow
 * @package     Lengow
 * @subpackage  Upgrade
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

use Shopware_Plugins_Backend_Lengow_Bootstrap as LengowBootstrap;
use Shopware_Plugins_Backend_Lengow_Bootstrap_Database as LengowBootstrapDatabase;

if (!LengowBootstrapDatabase::isInstallationInProgress()) {
    exit();
}

$orderTable = LengowBootstrapDatabase::TABLE_ORDER;
if (LengowBootstrapDatabase::tableExist($orderTable)) {
    $db = Shopware()->Db();
    try {
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'order_id')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `order_id` INTEGER(11) UNSIGNED NULL DEFAULT NULL');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'order_sku')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `order_sku` VARCHAR(40) NULL DEFAULT NULL');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'delivery_country_iso')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `delivery_country_iso` VARCHAR(3) NULL DEFAULT NULL');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'marketplace_label')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `marketplace_label` VARCHAR(100) NULL DEFAULT NULL');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'order_lengow_state')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `order_lengow_state` VARCHAR(100) NOT NULL');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'order_process_state')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `order_process_state` INTEGER(11) UNSIGNED NOT NULL');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'order_item')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `order_item` INTEGER(11) UNSIGNED NULL DEFAULT NULL');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'currency')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `currency` VARCHAR(3) NULL DEFAULT NULL');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'total_paid')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `total_paid` DECIMAL(17,2) NULL DEFAULT NULL');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'commission')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `commission` DECIMAL(17,2) NULL DEFAULT NULL');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'customer_name')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `customer_name` VARCHAR(255) NULL DEFAULT NULL');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'customer_email')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `customer_email` VARCHAR(255) NULL DEFAULT NULL');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'carrier')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `carrier` VARCHAR(100) NULL DEFAULT NULL');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'carrier_method')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `carrier_method` VARCHAR(100) NULL DEFAULT NULL');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'carrier_tracking')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `carrier_tracking` VARCHAR(100) NULL DEFAULT NULL');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'carrier_id_relay')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `carrier_id_relay` VARCHAR(100) NULL DEFAULT NULL');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'sent_marketplace')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `sent_marketplace` INTEGER(11) NOT NULL DEFAULT 0');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'is_in_error')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `is_in_error` INTEGER(11) NOT NULL DEFAULT 0');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'is_reimported')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `is_reimported` INTEGER(11) NOT NULL DEFAULT 0');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'message')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `message` TEXT NULL DEFAULT NULL');
        }
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'updated_at')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `updated_at` DATETIME NULL DEFAULT NULL');
        }
    } catch (Exception $e) {
        $errorMessage = '[Shopware error]: "' . $e->getMessage()
            . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
        LengowBootstrap::log('log/install/add_upgrade_error', array('error_message' => $errorMessage));
    }
}
