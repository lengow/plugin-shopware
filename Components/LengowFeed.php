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
 * Lengow Feed Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowFeed
{
    /**
     * @var string CSV Protection
     */
    const PROTECTION = '"';

    /**
     * @var string CSV separator
     */
    const CSV_SEPARATOR = '|';

    /**
     * @var string end of line
     */
    const EOL = "\r\n";

    /**
     * @var Shopware_Plugins_Backend_Lengow_Components_LengowFile Lengow File instance
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
     * @var string Lengow export folder
     */
    public static $lengowExportFolder = 'Export';

    /**
     * Construct
     * 
     * @var boolean $stream   export streaming or in a file
     * @var string  $format   export format
     * @var string  $shopName Shopware shop name
     */
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
     *
     * @throws Shopware_Plugins_Backend_Lengow_Components_LengowException unable to create folder
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
     *
     * @param string  $type    data type (header, body or footer)
     * @param array   $data    export data
     * @param boolean $isFirst is first product
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
     * @param array $data export data
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
     * @return boolean
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
     * For YAML, add spaces to have good indentation
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
     * @param array $fields list of fields to export
     *
     * @return integer
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
