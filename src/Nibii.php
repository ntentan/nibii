<?php
namespace ntentan\nibii;

use ntentan\utils\Text;

class Nibii
{
    private static $defaultDatastoreSettings;
    private static $defaultDatastoreInstance;
    
    /**
     * Set the settings used for creating default datastores.
     * @param array $datastoreSettings
     */
    public static function setDefaultDatastoreSettings($datastoreSettings)
    {
        self::$defaultDatastoreSettings = $datastoreSettings;
    }
    
    public static function getDefaultDatastoreInstance()
    {
        if(self::$defaultDatastoreInstance === null)
        {
            $class = "\\ntentan\\nibii\\datastores\\" . Text::ucamelize(self::$defaultDatastoreSettings['datastore']) . "DataStore";
            self::$defaultDatastoreInstance = new $class();
            self::$defaultDatastoreInstance->setSettings(self::$defaultDatastoreSettings);
            self::$defaultDatastoreInstance->init();
        }
        return self::$defaultDatastoreInstance;
    }
}
