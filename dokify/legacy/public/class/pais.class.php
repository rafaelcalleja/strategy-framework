<?php

class pais extends elemento implements Ielemento
{
    const SPAIN_CODE = 174;
    const PORTUGAL_CODE = 150;
    const FRANCE_CODE = 63;
    const CHILE_CODE = 111;
    const UK_CODE = 194;
    const ITALY_CODE = 91;
    const GERMANY_CODE = 69;

    public function __construct($param, $extra = false)
    {
        $this->tabla = TABLE_PAIS;
        $this->nombre_tabla = "pais";
        parent::instance($param, $extra);
    }

    public static function getFromName($name)
    {
        $cache = cache::singleton();
        $name = strtolower($name);
        if (($cacheString = "pais-getFromName-{$name}") && ($estado = $cache->getData($cacheString)) !== null) {
            return $estado;
        }



        $db = db::singleton();
        $sql = "SELECT uid_pais FROM ". TABLE_PAIS ." WHERE nombre LIKE '%". db::scape($name) ."%'";
        $item = false;
        if ($uid = $db->query($sql, 0, 0)) {
            $item = new self($uid);
        }


        $cache->addData($cacheString, $item);
        return $item;
    }

    /**
     * Get the regions of this country
     * @return array
     */
    public function getRegions()
    {
        $regions = array_filter(explode(',', $this->obtenerDato("regions")));

        $regions[] = $this->obtenerDato("char_code");

        return $regions;
    }

    /**
     * Check if the region @param matches this country region
     * @param  string $region
     * @return bool
     */
    public function matchRegion($region)
    {
        if ($region === 'ALL') {
            return true;
        }

        $exclude = strpos($region, '!') !== false;

        if ($exclude) {
            $match = false === in_array(str_replace('!', '', $region), $this->getRegions());
        } else {
            $match = true === in_array($region, $this->getRegions());
        }

        return $match;
    }

    public function getUserVisibleName()
    {
        return $this->obtenerDato("nombre");
    }

    public function getLanguage()
    {
        return $this->obtenerDato("language");
    }

    public function getLanguageId()
    {
        $lan = $this->getLanguage();
        return system::getIdLanguage($lan);
    }

    public function getCharCode()
    {
        return $this->obtenerDato("char_code");
    }

    public function getLocale()
    {
        $availableLocales = getAvailableLanguages();
        $availableLocales = array_keys($availableLocales);

        $lang = $this->getLanguage();
        $countryCharCode = $this->getCharCode();

        $locale = $lang . "_" . $countryCharCode;

        if (false === in_array($locale, $availableLocales)) {
            $localeMap = getLocaleMap();
            if (isset($localeMap[$lang])) {
                $locale = $localeMap[$lang];
            } else {
                $locale = "en_GB"; // Default locale
            }
        }

        return $locale . ".utf8";
    }

    public static function getValidCountries()
    {
        /* Comprobación de paises que tinen provinvias/estados asociados, útil para la validación*/
        /* En el furuto devolverá el conjunto de paises que tengan provincias o municipios*/
        return new ArrayObjectList(array(new self(174)));
    }

    public static function obtenerTodos()
    {
        $db = db::singleton();
        $sql = "SELECT uid_pais FROM ". TABLE_PAIS ."
                WHERE 1
                ORDER BY uid_pais=". self::SPAIN_CODE." DESC,
                        uid_pais=". self::CHILE_CODE." DESC,
                        uid_pais=". self::FRANCE_CODE." DESC,
                        uid_pais=". self::PORTUGAL_CODE." DESC,
                        uid_pais=". self::ITALY_CODE." DESC,
                        uid_pais=". self::GERMANY_CODE." DESC,
                        uid_pais=". self::UK_CODE." DESC,
                        nombre ASC";
        $paises = $db->query($sql, "*", 0, "pais");

        return new ArrayObjectCountries($paises);
    }

    public static function getCountriesSpanishSpeaking()
    {
        return array(
            174 => 'es',
            111 => 'cl',
            156 => 'pe',
            139 => 'ar',
            204 => 've',
            95 => 'ec',
            155 => 'py',
            126 => 'bo',
            200 => 'uy'
        );
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false)
    {
        $fieldList = new FieldList;
        $fieldList["nombre"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false));
        return $fieldList;
    }

    public function getTableFields()
    {
        return array(
            array("Field" => "uid_pais",                "Type" => "int(11)",        "Null" => "NO",     "Key" => "PRI",     "Default" => "",        "Extra" => "auto_increment"),
            array("Field" => "codigo",                  "Type" => "varchar(11)",    "Null" => "NO",     "Key" => "",        "Default" => "",        "Extra" => ""),
            array("Field" => "nombre",                  "Type" => "varchar(512)",   "Null" => "NO",     "Key" => "",        "Default" => "",        "Extra" => ""),
            array("Field" => "language",                "Type" => "varchar(2)",     "Null" => "YES",    "Key" => "",        "Default" => "",        "Extra" => ""),
            array("Field" => "char_code",               "Type" => "varchar(2)",     "Null" => "YES",    "Key" => "",        "Default" => "",        "Extra" => ""),
            array("Field" => "regions",                 "Type" => "varchar(254)",   "Null" => "YES",    "Key" => "",        "Default" => "",        "Extra" => ""),
            array("Field" => "enable_target_region",    "Type" => "int(1)",         "Null" => "NO",     "Key" => "",        "Default" => "0",       "Extra" => "")
        );
    }
}
