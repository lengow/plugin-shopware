<?php
/**
 * Copyright 2020 Lengow SAS
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
 * @copyright   2020 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

use Shopware_Plugins_Backend_Lengow_Bootstrap as LengowBootstrap;
use Shopware_Plugins_Backend_Lengow_Bootstrap_Database as LengowBootstrapDatabase;

if (!LengowBootstrapDatabase::isInstallationInProgress()) {
    exit();
}

$orderTable = 's_lengow_order';
if (LengowBootstrapDatabase::tableExist($orderTable)) {
    $db = Shopware()->Db();
    try {
        if (!LengowBootstrapDatabase::columnExists($orderTable, 'customer_vat_number')) {
            $db->exec('ALTER TABLE `s_lengow_order` ADD `customer_vat_number` TEXT NULL DEFAULT NULL');
        }
    } catch (Exception $e) {
        $errorMessage = '[Shopware error] "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
        LengowBootstrap::log('log/install/add_upgrade_error', array('error_message' => $errorMessage));
    }
}