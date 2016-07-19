<?php

/**
 * Created by PhpStorm.
 * User: nicolasmaugendre
 * Date: 17/06/16
 * Time: 16:31
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowLog
    extends Shopware_Plugins_Backend_Lengow_Components_LengowFile
{
    /**
     * @var string name of logs folder
     */
    public static $LENGOW_LOGS_FOLDER = 'Logs';

    protected $file;

    public function __construct()
    {
        if (empty($file_name)) {
            $this->file_name = 'logs-'.date('Y-m-d').'.txt';
        } else {
            $this->file_name = $file_name;
        }
        $this->file = new Shopware_Plugins_Backend_Lengow_Components_LengowFile(
            self::$LENGOW_LOGS_FOLDER,
            $this->file_name
        );
    }

    /**
     * Write log
     *
     * @param string  $category        Category
     * @param string  $message         log message
     * @param boolean $display         display on screen
     * @param string  $marketplace_sku lengow order id
     */
    public function write($category, $message = "", $display = false, $marketplace_sku = null)
    {
        $decoded_message = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage($message, 'en');
        $log = date('Y-m-d H:i:s');
        $log.= ' - '.(empty($category) ? '' : '['.$category.'] ');
        $log.= ''.(empty($marketplace_sku) ? '' : 'order '.$marketplace_sku.' : ');
        $log.= $decoded_message."\r\n";
        if ($display) {
            echo $log.'<br />';
            flush();
        }
        $this->file->write($log);
    }

    /**
     * Get log files links
     *
     * @return mixed
     *
     */
    public static function getLinks()
    {
        $files = self::getFiles();
        if (empty($files)) {
            return false;
        }
        $logs = array();
        foreach ($files as $file) {
            $logs[] = $file->getLink();
        }
        return $logs;
    }

    /**
     * Get log files path
     *
     * @return mixed
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
            preg_match('/\/lengow\/logs\/logs-([0-9]{4}-[0-9]{2}-[0-9]{2})\.txt/', $file->getPath(), $match);
            $logs[] = array(
                'full_path' => $file->getPath(),
                'short_path' => 'logs-'.$match[1].'.txt',
                'name' => $match[1].'.txt'
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
        return Shopware()->Plugins()->Backend()->Lengow()->Path() .
                self::$LENGOW_LOGS_FOLDER . '/' . $this->file_name;
    }

    /**
     * Get log files
     *
     * @return array
     */
    public static function getFiles()
    {
        return Shopware_Plugins_Backend_Lengow_Components_LengowFile::getFilesFromFolder(self::$LENGOW_LOGS_FOLDER);
    }

    /**
     * Download log file
     *
     * @param string $file name of file to download
     */
    public static function download($file = null)
    {
        if ($file && preg_match('/^logs-([0-9]{4}-[0-9]{2}-[0-9]{2})\.txt$/', $file, $match)) {
            $filename = Shopware()->Plugins()->Backend()->Lengow()->Path() .
                        self::$LENGOW_LOGS_FOLDER . '/' . $file;
            $handle = fopen($filename, "r");
            $contents = fread($handle, filesize($filename));
            header('Content-type: text/plain');
            header('Content-Disposition: attachment; filename="'.$match[1].'.txt"');
            echo $contents;
        } else {
            $files = self::getPaths();
            header('Content-type: text/plain');
            header('Content-Disposition: attachment; filename="logs.txt"');
            foreach ($files as $file) {
                $handle = fopen($file['full_path'], "r");
                $contents = fread($handle, filesize($file['full_path']));
                echo $contents;
            }
        }
    }
}
