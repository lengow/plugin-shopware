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
 * @subpackage  Components
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

use Shopware_Plugins_Backend_Lengow_Components_LengowException as LengowException;
use Shopware_Plugins_Backend_Lengow_Components_LengowFile as LengowFile;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowToolbox as LengowToolbox;
use Shopware_Plugins_Backend_Lengow_Components_LengowTranslation as LengowTranslation;

/**
 * Lengow Log Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowLog
    extends Shopware_Plugins_Backend_Lengow_Components_LengowFile
{
    /* Log category codes */
    const CODE_INSTALL = 'Install';
    const CODE_CONNECTION = 'Connection';
    const CODE_SETTING = 'Setting';
    const CODE_CONNECTOR = 'Connector';
    const CODE_EXPORT = 'Export';
    const CODE_IMPORT = 'Import';
    const CODE_ACTION = 'Action';
    const CODE_MAIL_REPORT = 'Mail Report';
    const CODE_ORM = 'Orm';

    /* Log params for export */
    const LOG_DATE = 'date';
    const LOG_LINK = 'link';

    /**
     * @var LengowFile Lengow file instance
     */
    protected $file;

    /**
     * Construct
     *
     * @param string $fileName|null log file name
     *
     * @throws LengowException
     */
    public function __construct($fileName = null)
    {
        if (empty($fileName)) {
            $this->fileName = 'logs-' . date('Y-m-d') . '.txt';
        } else {
            $this->fileName = $fileName;
        }
        $this->file = new LengowFile(LengowMain::FOLDER_LOG, $this->fileName);
    }

    /**
     * Write log
     *
     * @param string $category log category
     * @param string $message log message
     * @param boolean $display display on screen
     * @param string|null $marketplaceSku Lengow order id
     */
    public function write($category, $message = '', $display = false, $marketplaceSku = null)
    {
        $decodedMessage = LengowMain::decodeLogMessage($message, LengowTranslation::DEFAULT_ISO_CODE);
        $log = date('Y-m-d H:i:s');
        $log .= ' - ' . (empty($category) ? '' : '[' . $category . '] ');
        $log .= '' . (empty($marketplaceSku) ? '' : 'order ' . $marketplaceSku . ': ');
        $log .= $decodedMessage . "\r\n";
        if ($display) {
            echo $log . '<br />';
            flush();
        }
        $this->file->write($log);
    }

    /**
     * Get log files path
     *
     * @return array
     */
    public static function getPaths()
    {
        $logs = array();
        $files = self::getFiles();
        if (empty($files)) {
            return $logs;
        }
        foreach ($files as $file) {
            preg_match('/^logs-([0-9]{4}-[0-9]{2}-[0-9]{2})\.txt$/', $file->fileName, $match);
            $date = $match[1];
            $logs[] = array(
                self::LOG_DATE => $date,
                self::LOG_LINK => LengowMain::getToolboxUrl()
                    . '&' . LengowToolbox::PARAM_TOOLBOX_ACTION . '=' . LengowToolbox::ACTION_LOG
                    . '&' . LengowToolbox::PARAM_DATE . '=' . urlencode($date),
            );
        }
        return array_reverse($logs);
    }

    /**
     * Get current file
     *
     * @return string
     */
    public function getFileName()
    {
        $sep = DIRECTORY_SEPARATOR;
        return Shopware()->Plugins()->Backend()->Lengow()->Path() . LengowMain::FOLDER_LOG . $sep . $this->fileName;
    }

    /**
     * Get log files
     *
     * @return array
     */
    public static function getFiles()
    {
        return LengowFile::getFilesFromFolder(LengowMain::FOLDER_LOG);
    }

    /**
     * Download log file
     *
     * @param string|null $date date for a specific log file
     */
    public static function download($date = null)
    {
        /** @var LengowFile[] $logFiles */
        if ($date && preg_match('/^(\d{4}-\d{2}-\d{2})$/', $date, $match)) {
            $logFiles = false;
            $file = 'logs-' . $date . '.txt';
            $fileName = $date . '.txt';
            $sep = DIRECTORY_SEPARATOR;
            $filePath = LengowMain::getLengowFolder() . LengowMain::FOLDER_LOG . $sep . $file;
            if (file_exists($filePath)) {
                try {
                    $logFiles = array(new LengowFile(LengowMain::FOLDER_LOG, $file));
                } catch (LengowException $e) {
                    $logFiles = array();
                }
            }
        } else {
            $fileName = 'logs.txt';
            $logFiles = self::getFiles();
        }
        $contents = '';
        if ($logFiles) {
            foreach ($logFiles as $logFile) {
                $filePath = $logFile->getPath();
                $handle = fopen($filePath, 'r');
                $fileSize = filesize($filePath);
                if ($fileSize > 0) {
                    $contents .= fread($handle, $fileSize);
                }
            }
        }
        header('Content-type: text/plain');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        echo $contents;
        exit();
    }
}
