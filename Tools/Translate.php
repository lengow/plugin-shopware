<?php

/*
 * New Translation system base on YAML files
 * We need to edit yml file for each languages
 * /translations/yml/en.yml
 * /translations/yml/fr.yml
 * /translations/yml/es.yml
 * /translations/yml/it.yml
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

$default_locale = 'en';
$defaultTranslation = null;
$iso = array(
    'en' => 'GB',
    'fr' => 'FR',
    'de' => 'DE'
);

$directory = dirname(dirname(__FILE__)).'/Snippets/backend/Lengow/yml/';
$listFiles = array_diff(scandir($directory), array('..', '.', 'index.php'));
$listFiles = array_diff($listFiles, array('en.yml'));
array_unshift($listFiles, "en.yml");

$defaultValues = array();

$fp = fopen(dirname(dirname(__FILE__)).'/Snippets/backend/Lengow/translation.ini', 'w+');

foreach ($listFiles as $list) {
    $ymlFile = yaml_parse_file($directory.$list);
    $locale =  basename($directory.$list, '.yml');
    if ($locale == 'en') {
        $defaultTranslation = $ymlFile;
    }
    $header = '[' . $locale . '_' . $iso[$locale] . ']' . PHP_EOL;
    fwrite($fp, $header);

    foreach ($ymlFile as $language => $categories) {
        writeIniFile($fp, $categories);
    }

    fwrite($fp, "\n");
}

// Write default translation (english) if selected locale is missing
if ($defaultTranslation) {
    $header = '[default]' . PHP_EOL;
    fwrite($fp, $header);

    foreach ($defaultTranslation as $language => $categories) {
        writeIniFile($fp, $categories);
    }
}

fclose($fp);

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
        $line = join('/', $frontKey) . ' = "' . $content . '"' . PHP_EOL;
        fwrite($fp, $line);
    }
}