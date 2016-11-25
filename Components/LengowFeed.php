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
class Shopware_Plugins_Backend_Lengow_Components_LengowFeed
{
    /**
     * Protection.
     */
    const PROTECTION = '"';

    /**
     * CSV separator
     */
    const CSV_SEPARATOR = '|';

    /**
     * End of line.
     */
    const EOL = "\r\n";

    /**
     * @var Shopware_Plugins_Backend_Lengow_Components_LengowFile temporary export file
     */
    protected $file;

    /**
     * @var string feed content
     */
    protected $content = '';

    /**
     * @var string feed format
     */
    protected $format;

    /**
     * @var string export shop folder
     */
    protected $shopFolder = null;

    /**
     * @var string full export folder
     */
    protected $exportFolder;

    /**
     * @var array formats available for export
     */
    public static $availableFormats = array(
        'csv',
        'yaml',
        'xml',
        'json',
    );

    /**
     * @var boolean $stream
     * @var string  $format
     * @var string  $shopName
     */
    public static $lengowExportFolder = 'Export';

    public function __construct($stream, $format, $shopName)
    {
        $this->stream = $stream;
        $this->format = $format;
        $this->shopFolder = $this->formatFields($shopName, 'shop');
        if (!$this->stream) {
            $this->initExportFile();
        }
    }

    /**
     * Create export file
     */
    public function initExportFile()
    {
        $sep = DIRECTORY_SEPARATOR;
        $this->exportFolder = self::$lengowExportFolder . $sep . $this->shopFolder;
        $folderPath = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLengowFolder()
            .$sep.$this->exportFolder;
        if (!file_exists($folderPath)) {
            if (!mkdir($folderPath)) {
                throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'log/export/error_unable_to_create_folder',
                        array('folder_path' => $folderPath)
                    )
                );
            }
        }
        $fileName = 'flux-'.time().'.'.$this->format;
        $this->file = new Shopware_Plugins_Backend_Lengow_Components_LengowFile($this->exportFolder, $fileName);
    }

    /**
     * Write data in file
     * @param string  $type     Type of data (header|body|footer)
     * @param array   $data     Data to write
     * @param boolean $isFirst True if first call (used for json format)
     */
    public function write($type, $data = array(), $isFirst = null)
    {
        switch ($type) {
            case 'header':
                if ($this->stream) {
                    header($this->getHtmlHeader());
                    if ($this->format == 'csv') {
                        header('Content-Disposition: attachment; filename=feed.csv');
                    }
                }
                $header = $this->getHeader($data);
                $this->flush($header);
                break;
            case 'body':
                $body = $this->getBody($data, $isFirst);
                $this->flush($body);
                break;
            case 'footer':
                $footer = $this->getFooter();
                $this->flush($footer);
                break;
        }
    }

    /**
     * Return feed header
     *
     * @param array $data Data to display
     *
     * @return string
     */
    protected function getHeader($data)
    {
        switch ($this->format) {
            case 'csv':
                $header = '';
                foreach ($data as $field) {
                    $header.= self::PROTECTION.$this->formatFields($field).self::PROTECTION.self::CSV_SEPARATOR;
                }
                return rtrim($header, self::CSV_SEPARATOR).self::EOL;
            case 'xml':
                return '<?xml version="1.0" encoding="UTF-8"?>'.self::EOL
                .'<catalog>'.self::EOL;
            case 'json':
                return '{"catalog":[';
            case 'yaml':
                return '"catalog":'.self::EOL;
        }
    }

    /**
     * Get feed body
     *
     * @param array   $data    feed data
     * @param boolean $isFirst is first product
     *
     * @return string
     */
    protected function getBody($data, $isFirst)
    {
        switch ($this->format) {
            case 'csv':
                $content = '';
                foreach ($data as $value) {
                    $content.= self::PROTECTION.$value.self::PROTECTION.self::CSV_SEPARATOR;
                }
                return rtrim($content, self::CSV_SEPARATOR).self::EOL;
            case 'xml':
                $content = '<product>';
                foreach ($data as $field => $value) {
                    $field = $this->formatFields($field);
                    $content.= '<'.$field.'><![CDATA['.$value.']]></'.$field.'>'.self::EOL;
                }
                $content.= '</product>'.self::EOL;
                return $content;
            case 'json':
                $content = $isFirst ? '' : ',';
                $jsonArray = array();
                foreach ($data as $field => $value) {
                    $field = $this->formatFields($field);
                    $jsonArray[$field] = $value;
                }
                $content .= json_encode($jsonArray);
                return $content;
            case 'yaml':
                $content = '  '.self::PROTECTION.'product'.self::PROTECTION.':'.self::EOL;
                $fieldMaxSize = $this->getFieldMaxSize($data);
                foreach ($data as $field => $value) {
                    $field = $this->formatFields($field);
                    $content.= '    '.self::PROTECTION.$field.self::PROTECTION.':';
                    $content.= $this->indentYaml($field, $fieldMaxSize).(string)$value.self::EOL;
                }
                return $content;
        }
    }

    /**
     * Return feed footer
     *
     * @return string
     */
    protected function getFooter()
    {
        switch ($this->format) {
            case 'xml':
                return '</catalog>';
            case 'json':
                return ']}';
            default:
                return '';
        }
    }

    /**
     * Flush feed content
     *
     * @param string $content feed content to be flushed
     *
     */
    public function flush($content)
    {
        if ($this->stream) {
            echo $content;
            flush();
        } else {
            $this->file->write($content);
        }
    }

    /**
     * Finalize export generation
     *
     * @return bool
     */
    public function end()
    {
        $this->write('footer');
        if (!$this->stream) {
            $oldFileName = 'flux.'.$this->format;
            $oldFile = new Shopware_Plugins_Backend_Lengow_Components_LengowFile($this->exportFolder, $oldFileName);
            if ($oldFile->exists()) {
                $oldFilePath = $oldFile->getPath();
                $oldFile->delete();
            }
            if (isset($oldFilePath)) {
                $rename = $this->file->rename($oldFilePath);
                $this->file->fileName = $oldFileName;
            } else {
                $sep = DIRECTORY_SEPARATOR;
                $rename = $this->file->rename($this->file->getFolderPath().$sep.$oldFileName);
                $this->file->fileName = $oldFileName;
            }
            return $rename;
        }
        return true;
    }

    /**
     * Get feed URL
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->file->getLink();
    }

    /**
     * Get file name
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->file->getPath();
    }

    /**
     * Return HTML header according to the given format
     *
     * @return string
     */
    protected function getHtmlHeader()
    {
        switch ($this->format) {
            case 'csv':
                return 'Content-Type: text/csv; charset=UTF-8';
            case 'xml':
                return 'Content-Type: application/xml; charset=UTF-8';
            case 'json':
                return 'Content-Type: application/json; charset=UTF-8';
            case 'yaml':
                return 'Content-Type: text/x-yaml; charset=UTF-8';
        }
    }

    /**
     * Format field names according to the given format
     *
     * @param string $str field name
     *
     * @return string
     */
    protected function formatFields($str)
    {
        switch ($this->format) {
            case 'csv':
                return substr(
                    preg_replace(
                        '/[^a-zA-Z0-9_]+/',
                        '',
                        str_replace(
                            array(' ', '\''),
                            '_',
                            Shopware_Plugins_Backend_Lengow_Components_LengowMain::replaceAccentedChars($str)
                        )
                    ),
                    0,
                    58
                );
            default:
                return strtolower(
                    preg_replace(
                        '/[^a-zA-Z0-9_]+/',
                        '',
                        str_replace(
                            array(' ','\''),
                            '_',
                            Shopware_Plugins_Backend_Lengow_Components_LengowMain::replaceAccentedChars($str)
                        )
                    )
                );
        }
    }

    /**
     * For YAML, add spaces to have good indentation.
     *
     * @param string $name    the field name
     * @param string $maxSize space limit
     *
     * @return string
     */
    protected function indentYaml($name, $maxSize)
    {
        $strlen = strlen($name);
        $spaces = '';
        for ($i = $strlen; $i <= $maxSize; $i++) {
            $spaces.= ' ';
        }
        return $spaces;
    }

    /**
     * Get the maximum length of the fields
     * Used for indentYaml function
     *
     * @param array $fields List of fields to export
     *
     * @return integer Length of the longer field
     */
    protected function getFieldMaxSize($fields)
    {
        $maxSize = 0;
        foreach ($fields as $key => $field) {
            $field = $this->formatFields($key);
            if (strlen($field) > $maxSize) {
                $maxSize = strlen($field);
            }
        }
        return $maxSize;
    }
}
