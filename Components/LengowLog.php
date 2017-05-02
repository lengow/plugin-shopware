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

/**
 * Lengow Log Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowLog
    extends Shopware_Plugins_Backend_Lengow_Components_LengowFile
{
    /**
     * @var string name of logs folder
     */
    public static $lengowLogFolder = 'Logs';

    /**
     * @var Shopware_Plugins_Backend_Lengow_Components_LengowFile Lengow file instance
     */
    protected $file;

    /**
     * Construct
     *
     * @param string $fileName log file name
     */
    public function __construct($fileName = null)
    {
        if (empty($fileName)) {
            $this->fileName = 'logs-' . date('Y-m-d') . '.txt';
        } else {
            $this->fileName = $fileName;
        }
        $this->file = new Shopware_Plugins_Backend_Lengow_Components_LengowFile(
            self::$lengowLogFolder,
            $this->fileName
        );
    }

    /**
     * Write log
     *
     * @param string $category log category
     * @param string $message log message
     * @param boolean $display display on screen
     * @param string $marketplaceSku Lengow order id
     */
    public function write($category, $message = "", $display = false, $marketplaceSku = null)
    {
        $decodedMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage($message);
        $log = date('Y-m-d H:i:s');
        $log .= ' - ' . (empty($category) ? '' : '[' . $category . '] ');
        $log .= '' . (empty($marketplaceSku) ? '' : 'order ' . $marketplaceSku . ' : ');
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
     * @return array|false
     *
     */
    public static function getPaths()
    {
        $files = self::getFiles();
        if (empty($files)) {
            return false;
        }
        $logs = array();
        foreach ($files as $file) {
            preg_match('/logs-([0-9]{4}-[0-9]{2}-[0-9]{2})\.txt/', $file->getPath(), $match);
            $logs[] = array(
                'full_path' => $file->getPath(),
                'short_path' => 'logs-' . $match[1] . '.txt',
                'link' => $file->getLink(),
                'name' => $match[1] . '.txt'
            );
        }
        return $logs;
    }

    /**
     * Get current file
     *
     * @return string
     *
     */
    public function getFileName()
    {
        return Shopware()->Plugins()->Backend()->Lengow()->Path() . self::$lengowLogFolder . '/' . $this->fileName;
    }

    /**
     * Get log files
     *
     * @return array
     */
    public static function getFiles()
    {
        return Shopware_Plugins_Backend_Lengow_Components_LengowFile::getFilesFromFolder(self::$lengowLogFolder);
    }

    /**
     * Download log file
     *
     * @param string $file name of file to download
     */
    public static function download($file = null)
    {
        if ($file && preg_match('/^logs-([0-9]{4}-[0-9]{2}-[0-9]{2})\.txt$/', $file, $match)) {
            $filename = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLengowFolder() .
                self::$lengowLogFolder . '/' . $file;
            $handle = fopen($filename, "r");
            $contents = fread($handle, filesize($filename));
            header('Content-type: text/plain');
            header('Content-Disposition: attachment; filename="' . $match[1] . '.txt"');
            echo $contents;
            exit();
        } else {
            $files = self::getPaths();
            header('Content-type: text/plain');
            header('Content-Disposition: attachment; filename="logs.txt"');
            foreach ($files as $file) {
                $handle = fopen($file['full_path'], "r");
                $contents = fread($handle, filesize($file['full_path']));
                echo $contents;
            }
            exit();
        }
    }
}
