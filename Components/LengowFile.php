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
class Shopware_Plugins_Backend_Lengow_Components_LengowFile
{
    /**
     * @var string file name
     */
    public $file_name;

    /**
     * @var string folder name that contains the file
     */
    public $folder_name;

    /**
     * @var ressource file hande
     */
    public $instance;


    public function __construct($folder_name, $file_name = null, $mode = 'a+')
    {
        $this->file_name = $file_name;
        $this->folder_name = $folder_name;
        $this->instance = self::getRessource($this->getPath(), $mode);
        if (!is_resource($this->instance)) {
            throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                    'log.export.error_unable_to_create_file',
                    array(
                        'file_name'   => $file_name,
                        'folder_name' => $folder_name
                    )
                )
            );
        }
    }

    /**
     * Write content in file
     *
     * @param string $txt text to be written
     */
    public function write($txt)
    {
        if (!$this->instance) {
            $this->instance = fopen($this->getPath(), 'a+');
        }
        fwrite($this->instance, $txt);
    }

    /**
     * Delete file
     */
    public function delete()
    {
        if ($this->exists()) {
            if ($this->instance) {
                $this->close();
            }
            unlink($this->getPath());
        }
    }

    /**
     * Get resource of a given stream
     *
     * @param string $path path to the file
     * @param string $mode type of access
     *
     * @return resource
     */
    public static function getRessource($path, $mode = 'a+')
    {
        return fopen($path, $mode);
    }

    /**
     * Get file link
     *
     * @return string
     */
    public function getLink()
    {
        if (empty($this->link)) {
            if (!$this->exists()) {
                $this->link = null;
            }
            $sep = DIRECTORY_SEPARATOR;
            $base = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getBaseUrl();
            $lengowDir = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getPathPlugin();
            $this->link = $base . $lengowDir . $this->folder_name . $sep . $this->file_name;
        }
        return $this->link;

    }

    /**
     * Get file path
     *
     * @return string
     */
    public function getPath()
    {
        $sep = DIRECTORY_SEPARATOR;
        return Shopware()->Plugins()->Backend()->Lengow()->Path() . $this->folder_name . $sep . $this->file_name;
    }

    /**
     * Get folder path of current file
     *
     * @return string
     */
    public function getFolderPath()
    {
        $sep = DIRECTORY_SEPARATOR;
        return Shopware()->Plugins()->Backend()->Lengow()->Path() . $this->folder_name;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Rename file
     *
     * @return boolean
     */
    public function rename($new_name)
    {
        return rename($this->getPath(), $new_name);
    }

    /**
     * Close file handle
     */
    public function close()
    {
        if (is_resource($this->instance)) {
            fclose($this->instance);
        }
    }

    /**
     * Check if current file exists
     *
     * @return bool
     */
    public function exists()
    {
        return file_exists($this->getPath());
    }

    /**
     * Get a file list for a given folder
     *
     * @param string $folder folder name
     *
     * @return array
     */
    public static function getFilesFromFolder($folder)
    {
        $sep = DIRECTORY_SEPARATOR;
        $folder_path = Shopware()->Plugins()->Backend()->Lengow()->Path() . $folder;
        if (!file_exists($folder_path)) {
            return false;
        }
        $folder_content = scandir($folder_path);
        $files = array();
        foreach ($folder_content as $file) {
            if (!preg_match('/^\.[a-zA-Z\.]+$|^\.$|index\.php/', $file)) {
                $files[] = new Shopware_Plugins_Backend_Lengow_Components_LengowFile($folder, $file);
            }
        }
        return $files;
    }
}
