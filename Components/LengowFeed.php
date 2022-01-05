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

/**
 * Lengow Feed Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowFeed
{
    /* Feed formats */
    const FORMAT_CSV = 'csv';
    const FORMAT_YAML = 'yaml';
    const FORMAT_XML = 'xml';
    const FORMAT_JSON = 'json';

    /* Content types */
    const HEADER = 'header';
    const BODY = 'body';
    const FOOTER = 'footer';

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
     * @var array formats available for export
     */
    public static $availableFormats = array(
        self::FORMAT_CSV,
        self::FORMAT_YAML,
        self::FORMAT_XML,
        self::FORMAT_JSON,
    );

    /**
     * @var LengowFile Lengow File instance
     */
    private $file;

    /**
     * @var boolean stream or file
     */
    private $stream;

    /**
     * @var string feed format
     */
    private $format;

    /**
     * @var string|null export shop folder
     */
    private $shopFolder;

    /**
     * @var string full export folder
     */
    private $exportFolder;

    /**
     * Construct
     *
     * @var boolean $stream export streaming or in a file
     * @var string $format export format
     * @var string $shopName Shopware shop name
     *
     * @throws LengowException
     */
    public function __construct($stream, $format, $shopName)
    {
        $this->stream = $stream;
        $this->format = $format;
        $this->shopFolder = self::formatFields($shopName, $format);
        if (!$this->stream) {
            $this->initExportFile();
        }
    }

    /**
     * Create export file
     *
     * @throws LengowException
     */
    public function initExportFile()
    {
        $sep = DIRECTORY_SEPARATOR;
        $this->exportFolder = LengowMain::FOLDER_EXPORT . $sep . $this->shopFolder;
        $folderPath = LengowMain::getLengowFolder() . $sep . $this->exportFolder;
        if (!file_exists($folderPath) && !mkdir($folderPath) && !is_dir($folderPath)) {
            throw new LengowException(
                LengowMain::setLogMessage(
                    'log/export/error_unable_to_create_folder',
                    array('folder_path' => $folderPath)
                )
            );
        }
        $fileName = 'flux-' . time() . '.' . $this->format;
        $this->file = new LengowFile($this->exportFolder, $fileName);
    }

    /**
     * Write data in file
     *
     * @param string $type data type (header, body or footer)
     * @param array $data export data
     * @param boolean|null $isFirst is first product
     */
    public function write($type, $data = array(), $isFirst = null)
    {
        switch ($type) {
            case self::HEADER:
                if ($this->stream) {
                    header($this->getHtmlHeader());
                    if ($this->format === self::FORMAT_CSV) {
                        header('Content-Disposition: attachment; filename=feed.csv');
                    }
                }
                $header = $this->getHeader($data);
                $this->flush($header);
                break;
            case self::BODY:
                $body = $this->getBody($data, $isFirst);
                $this->flush($body);
                break;
            case self::FOOTER:
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
    private function getHeader($data)
    {
        switch ($this->format) {
            case self::FORMAT_CSV:
            default:
                $header = '';
                foreach ($data as $field) {
                    $header .= self::PROTECTION . self::formatFields($field, self::FORMAT_CSV)
                        . self::PROTECTION . self::CSV_SEPARATOR;
                }
                return rtrim($header, self::CSV_SEPARATOR) . self::EOL;
            case self::FORMAT_XML:
                return '<?xml version="1.0" encoding="UTF-8"?>' . self::EOL
                . '<catalog>' . self::EOL;
            case self::FORMAT_JSON:
                return '{"catalog":[';
            case self::FORMAT_YAML:
                return '"catalog":' . self::EOL;
        }
    }

    /**
     * Get feed body
     *
     * @param array $data feed data
     * @param boolean $isFirst is first product
     *
     * @return string
     */
    private function getBody($data, $isFirst)
    {
        switch ($this->format) {
            case self::FORMAT_CSV:
            default:
                $content = '';
                foreach ($data as $value) {
                    $content .= self::PROTECTION . $value . self::PROTECTION . self::CSV_SEPARATOR;
                }
                return rtrim($content, self::CSV_SEPARATOR) . self::EOL;
            case self::FORMAT_XML:
                $content = '<product>';
                foreach ($data as $field => $value) {
                    $field = self::formatFields($field, self::FORMAT_XML);
                    $content .= '<' . $field . '><![CDATA[' . $value . ']]></' . $field . '>' . self::EOL;
                }
                $content .= '</product>' . self::EOL;
                return $content;
            case self::FORMAT_JSON:
                $content = $isFirst ? '' : ',';
                $jsonArray = array();
                foreach ($data as $field => $value) {
                    $field = self::formatFields($field, self::FORMAT_JSON);
                    $jsonArray[$field] = $value;
                }
                $content .= json_encode($jsonArray);
                return $content;
            case self::FORMAT_YAML:
                $content = '  ' . self::PROTECTION . 'product' . self::PROTECTION . ':' . self::EOL;
                $fieldMaxSize = $this->getFieldMaxSize($data);
                foreach ($data as $field => $value) {
                    $field = self::formatFields($field, self::FORMAT_YAML);
                    $content .= '    ' . self::PROTECTION . $field . self::PROTECTION . ':';
                    $content .= $this->indentYaml($field, $fieldMaxSize) . $value . self::EOL;
                }
                return $content;
        }
    }

    /**
     * Return feed footer
     *
     * @return string
     */
    private function getFooter()
    {
        switch ($this->format) {
            case self::FORMAT_XML:
                return '</catalog>';
            case self::FORMAT_JSON:
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
     *
     * @throws LengowException
     */
    public function end()
    {
        $this->write(self::FOOTER);
        if (!$this->stream) {
            $oldFileName = 'flux.' . $this->format;
            $oldFile = new LengowFile($this->exportFolder, $oldFileName);
            if ($oldFile->exists()) {
                $oldFilePath = $oldFile->getPath();
                $oldFile->delete();
            }
            if (isset($oldFilePath)) {
                $rename = $this->file->rename($oldFilePath);
            } else {
                $sep = DIRECTORY_SEPARATOR;
                $rename = $this->file->rename($this->file->getFolderPath() . $sep . $oldFileName);
            }
            $this->file->fileName = $oldFileName;
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
    private function getHtmlHeader()
    {
        switch ($this->format) {
            case self::FORMAT_CSV:
            default:
                return 'Content-Type: text/csv; charset=UTF-8';
            case self::FORMAT_XML:
                return 'Content-Type: application/xml; charset=UTF-8';
            case self::FORMAT_JSON:
                return 'Content-Type: application/json; charset=UTF-8';
            case self::FORMAT_YAML:
                return 'Content-Type: text/x-yaml; charset=UTF-8';
        }
    }

    /**
     * Format field names according to the given format
     *
     * @param string $str field name
     * @param string $format export format
     *
     * @return string
     */
    public static function formatFields($str, $format)
    {
        switch ($format) {
            case self::FORMAT_CSV:
                return substr(
                    preg_replace(
                        '/[^a-zA-Z0-9_]+/',
                        '',
                        str_replace(array(' ', '\''), '_', LengowMain::replaceAccentedChars($str))
                    ),
                    0,
                    58
                );
            default:
                return strtolower(
                    preg_replace(
                        '/[^a-zA-Z0-9_]+/',
                        '',
                        str_replace(array(' ', '\''), '_', LengowMain::replaceAccentedChars($str))
                    )
                );
        }
    }

    /**
     * For YAML, add spaces to have good indentation
     *
     * @param string $name the field name
     * @param string $maxSize space limit
     *
     * @return string
     */
    private function indentYaml($name, $maxSize)
    {
        $strlen = strlen($name);
        $spaces = '';
        for ($i = $strlen; $i <= $maxSize; $i++) {
            $spaces .= ' ';
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
    private function getFieldMaxSize($fields)
    {
        $maxSize = 0;
        foreach ($fields as $key => $field) {
            $field = self::formatFields($key, self::FORMAT_YAML);
            if (strlen($field) > $maxSize) {
                $maxSize = strlen($field);
            }
        }
        return $maxSize;
    }
}
