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

use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;

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
        $files = LengowLog::getPaths();
        $result = array();
        foreach ($files as $logFile) {
            $date = $logFile[LengowLog::LOG_DATE];
            $dateTime = new DateTime($date);
            $result[] = array(
                'name' => $date,
                'date' => date_format($dateTime, 'd m Y'),
            );
        }
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $result,
                'total' => count($result),
            )
        );
    }

    /**
     * Launch log file download
     */
    public function downloadAction()
    {
        $date = $this->Request()->getParam('date');
        LengowLog::download($date);
    }
}
