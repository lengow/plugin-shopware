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
class Shopware_Plugins_Backend_Lengow_Components_LengowTranslation
{
    /**
     * Version
     */
    protected static $translation = null;

    /**
     * Fallback iso code
     */
    public $fallbackIsoCode = 'default';

    /**
     * Translate message
     *
     * @param string $message
     * @param array  $args
     * @param string $isoCode
     *
     * @return string Final Translate string
     */
    public function t($message, $args = array(), $isoCode = null)
    {
        if (!isset(self::$translation[$isoCode])) {
            $this->loadFile();
        }
        if (isset(self::$translation[$isoCode][$message])) {
            return $this->translateFinal(self::$translation[$isoCode][$message], $args);
        } else {
            if (!isset(self::$translation[$this->fallbackIsoCode])) {
                $this->loadFile($this->fallbackIsoCode);
            }
            if (isset(self::$translation[$this->fallbackIsoCode][$message])) {
                return $this->translateFinal(self::$translation[$this->fallbackIsoCode][$message], $args);
            } else {
                return 'Missing Translation ['.$message.']';
            }
        }
    }

    /**
     * Translate string
     *
     * @param string $text
     * @param array  $args
     *
     * @return string Final Translate string
     */
    protected function translateFinal($text, $args)
    {
        if ($args) {
            $params = array();
            $values = array();
            foreach ($args as $key => $value) {
                $params[] = '%{'.$key.'}';
                $values[] = $value;
            }
            return stripslashes(str_replace($params, $values, $text));
        } else {
            return stripslashes($text);
        }
    }

    /**
     * Load ini file
     *
     * @param string $fileName file location
     * @param string $isoCode
     *
     * @return boolean
     */
    public function loadFile($fileName = null, $isoCode = null)
    {
        if (!$fileName) {
            $pluginPath = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLengowFolder();
            $fileName = $pluginPath.'Snippets/backend/Lengow/translation.ini';
        }
        $translation = array();
        if (file_exists($fileName)) {
            try {
                self::$translation = parse_ini_file($fileName, true);
            } catch (Exception $e) {
                throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
                    Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage(
                        'Translation file could not be load : '.$e
                    )
                );
            }
        }
        self::$translation[$isoCode] = $translation;
        return count($translation) > 0;
    }

    /**
     * File contains Iso code
     *
     * @param string $isoCode
     *
     * @return boolean
     */
    public static function containsIso($isoCode)
    {
        if (!isset(self::$translation[$isoCode])) {
            $this->loadFile();
        }
        return array_key_exists($isoCode, self::$translation);
    }
}
