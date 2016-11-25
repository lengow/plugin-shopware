<?php

/**
 * Copyright 2016 Lengow SAS.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 *
 * @author    Team Connector <team-connector@lengow.com>
 * @copyright 2016 Lengow SAS
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowLog
    extends Shopware_Plugins_Backend_Lengow_Components_LengowFile
{
    /**
     * @var string name of logs folder
     */
    public static $lengowLogFolder = 'Logs';

    protected $file;

    public function __construct()
    {
        if (empty($fileName)) {
            $this->fileName = 'logs-'.date('Y-m-d').'.txt';
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
     * @param string  $category       Category
     * @param string  $message        log message
     * @param boolean $display        display on screen
     * @param string  $marketplaceSku lengow order id
     */
    public function write($category, $message = "", $display = false, $marketplaceSku = null)
    {
        $decodedMessage = Shopware_Plugins_Backend_Lengow_Components_LengowMain::decodeLogMessage($message);
        $log = date('Y-m-d H:i:s');
        $log.= ' - '.(empty($category) ? '' : '['.$category.'] ');
        $log.= ''.(empty($marketplaceSku) ? '' : 'order '.$marketplaceSku.' : ');
        $log.= $decodedMessage."\r\n";
        if ($display) {
            echo $log.'<br />';
            flush();
        }
        $this->file->write($log);
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
            preg_match('/logs-([0-9]{4}-[0-9]{2}-[0-9]{2})\.txt/', $file->getPath(), $match);
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
        return Shopware()->Plugins()->Backend()->Lengow()->Path().self::$lengowLogFolder.'/'.$this->fileName;
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
            $filename = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLengowFolder().
                self::$lengowLogFolder.'/'.$file;
            $handle = fopen($filename, "r");
            $contents = fread($handle, filesize($filename));
            header('Content-type: text/plain');
            header('Content-Disposition: attachment; filename="'.$match[1].'.txt"');
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
