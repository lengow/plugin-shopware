<?php

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

	public function t($message, $args = array(), $iso_code)
	{
        if (!isset(self::$translation[$iso_code])) {
            $this->loadFile();
        }
        if (isset(self::$translation[$iso_code][$message])) {
            return $this->translateFinal(self::$translation[$iso_code][$message], $args);
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
            return str_replace($params, $values, $text);
        } else {
            return $text;
        }
    }

    /**
     * Load ini file
     *
     * @param string $iso_code
     * @param string $filename file location
     *
     * @return boolean
     */
    public function loadFile($fileName = null, $isoCode = null)
    {
        if (!$fileName) {
        	$pluginPath = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getLengowFolder();
            $fileName = $pluginPath . 'Snippets/backend/Lengow/translation.ini';
        }
        $translation = array();
        if (file_exists($fileName)) {
        	try {
    			self::$translation = parse_ini_file($fileName, true);
    		} catch (Exception $e) {
	            throw new Shopware_Plugins_Backend_Lengow_Components_LengowException(
	                Shopware_Plugins_Backend_Lengow_Components_LengowMain::setLogMessage('Translation file could not be load : ' . $e)
	            );
    		}
        }
        self::$translation[$isoCode] = $translation;
        return count($translation) > 0;
    }

}