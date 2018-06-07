<?php

class traductor extends basic
{
    public $elementoFiltro;

    public function __construct($uid, $itemObjeto, $field = 'alias')
    {
        $this->db = db::singleton();
        $this->tipo = "traducible";
        $this->tabla = constant("TABLE_".strtoupper(get_class($itemObjeto))."_IDIOMA");
        $this->elementoFiltro = $itemObjeto;
        $this->field = $field;
        $this->instance($uid, false);
    }

    public function update($data = false, $fieldsMode = false, Iusuario $usuario = null)
    {
        $locales = $this->getLocales();

        $return = null;

        foreach ($locales as $i => $locale) {
            if (isset($_REQUEST[$locale])) {
                $currentValue = $this->getLocaleValue($locale);
                $newValue = db::scape($_REQUEST[$locale]);

                if ($newValue != $currentValue) {
                    if (!$this->setLocaleValue($locale, $newValue)) {
                        return false;
                    } else {
                        $return = true;
                    }
                }
            }
        }

        return $return;
    }

    public function publicFields($data = false, $fieldsMode = false, Iusuario $usuario = null)
    {
        $locales = $this->getLocales();

        $campo = new FieldList();
        foreach ($locales as $i => $locale) {
            $campo[$locale] = new FormField(array("tag" => "input", "type" => "text", "value" => $this->getLocaleValue($locale)));
        }

        return $campo;
    }

    public function setLocaleValue($locale, $value)
    {
        $primary = "uid_". strtolower(get_class($this->elementoFiltro));
        $value = utf8_decode($value);
        $sql = "INSERT IGNORE INTO $this->tabla ( $primary, locale, {$this->field} ) VALUES (
            '$this->uid', '$locale', '$value'
        ) ON DUPLICATE KEY UPDATE {$this->field} = '$value'
        ";

        return $this->db->query($sql);
    }

    public function getLocaleValue($locale)
    {
        $primary = "uid_". strtolower(get_class($this->elementoFiltro));
        $sql = "SELECT {$this->field} FROM $this->tabla WHERE $primary = ". $this->uid ." AND locale = '$locale'";
        $alias = $this->db->query($sql, 0, 0);
        return utf8_encode(str_replace("\\", "", $alias));
    }

    public function getLocales()
    {
        $arrayLocales = array();

        $arrayLocales[] = "en";
        $arrayLocales[] = "pt";
        $arrayLocales[] = "fr";
        $arrayLocales[] = "it";

        return $arrayLocales;
    }
}
