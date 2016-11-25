<?php

/*
 * New Translation system base on YAML files
 * We need to edit yml file for each languages
 * /Snippets/backend/Lengow/yml/en.yml
 * /Snippets/backend/Lengow/yml/de.yml
 *
 * Execute this script to generate files
 *
 * Installation de YAML PARSER
 *
 * sudo apt-get install php5-dev libyaml-dev
 * sudo pecl install yaml
 */

error_reporting(E_ALL);
ini_set("display_errors", 1);

$defaultLocale = 'en';
$logFile = 'log';
$defaultTranslation = null;
$locales = array(
    'en' => 'GB',
    'de' => 'DE',
    'fr' => 'FR'
);

$directory = dirname(dirname(__FILE__)).'/Snippets/backend/Lengow/yml/';
$listFiles = array_diff(scandir($directory), array('..', '.', 'index.php'));
$listFiles = array_diff($listFiles, array('en.yml'));
array_unshift($listFiles, "en.yml");

$defaultValues = array();

$fp = fopen(dirname(dirname(__FILE__)).'/Snippets/backend/Lengow/translation.ini', 'w+');
// Get translation for each locale
foreach ($locales as $key => $value) {
    $fileName = $directory.$key.'.yml';
    $ymlFile = yaml_parse_file($fileName);
    $header = '['.$key.'_'.$value.']'.PHP_EOL;
    fwrite($fp, $header);
    foreach ($ymlFile as $language => $categories) {
        writeIniFile($fp, $categories);
    }
    fwrite($fp, "\n");
}

// Put default locale (en) and log translations into [default] section
// Used by Shopware when user locale is not detected
$englishTranslation = yaml_parse_file($directory.$defaultLocale.'.yml');
$logTranslation = yaml_parse_file($directory.$logFile.'.yml');
$defaultTranslation = array_merge($englishTranslation, $logTranslation);
$header = '[default]'.PHP_EOL;
fwrite($fp, $header);
foreach ($defaultTranslation as $language => $categories) {
    writeIniFile($fp, $categories);
}
fclose($fp);

/**
 * Write Yaml content in ini file
 * @param $fp resource File to edit
 * @param $text string Text to write
 * @param array $frontKey
 */
function writeIniFile($fp, $text, &$frontKey = array())
{
    if (is_array($text)) {
        foreach ($text as $k => $v) {
            $frontKey[]= $k;
            writeIniFile($fp, $v, $frontKey);
            array_pop($frontKey);
        }
    } else {
        $content = addslashes(str_replace("\n", '<br />', $text));
        $line = join('/', $frontKey).' = "'.$content.'"'.PHP_EOL;
        fwrite($fp, $line);
    }
}
