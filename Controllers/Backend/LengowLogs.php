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
 * @subpackage  Controllers
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

/**
 * Backend Lengow Logs Controller
 */
class Shopware_Controllers_Backend_LengowLogs extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Get list of log files
     */
    public function listAction()
    {
        $files = Shopware_Plugins_Backend_Lengow_Components_LengowLog::getFiles();
        $result = array();
        foreach ($files as $logFile) {
            $name = $logFile->fileName;
            $date = substr($name, 5, 11);
            $dateTime = new DateTime($date);
            $result[] = array(
                'name' => $logFile->fileName,
                'date' => date_format($dateTime, 'd m Y')
            );
        }
        $this->View()->assign(
            array(
                'success' => true,
                'data' => array_reverse($result),
                'total' => count($result)
            )
        );
    }

    /**
     * Launch log file download
     */
    public function downloadAction()
    {
        $fileName = $this->Request()->getParam('fileName');
        Shopware_Plugins_Backend_Lengow_Components_LengowLog::download($fileName);
    }
}
