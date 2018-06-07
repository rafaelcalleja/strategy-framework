<?php

class formato extends elemento
{
    /**
     * Constructor
     * @param [type]  $param         [description]
     * @param boolean $saveOnSession Default true (uid or data)
     */
    public function __construct($param, $saveOnSession = true)
    {
        $this->tipo = "formato";
        $this->tabla = TABLE_FORMATO;

        $this->instance($param, $saveOnSession);
    }

    public function getUserVisibleName()
    {
        $datos = $this->getInfo();
        return $datos["extension"];
    }

    public function getAssignName($fn, $parent = null)
    {
        $datos = $this->getInfo();
        return $datos["descripcion"]."  (".$datos["extension"].")";
    }

    public function getIcon($sixe = false)
    {
        $datosIcono = $this->getInfo();
        return $datosIcono["icono"];
    }

    public function getName()
    {
        switch ($this->getUserVisibleName()) {
            case 'application/msword':
                return 'word';
                break;
            case 'application/vnd.ms-excel':
                return 'excel';
                break;
            case 'application/vnd.oasis.opendocument.text':
                return 'openoffice';
                break;
            case 'application/pdf':
                return 'pdf';
                break;
            case 'image/jpeg':
                return 'picture';
                break;
            default:
                return '';
                break;
        }
    }

    public function toArray($app = null)
    {
        return [
            'uid'           => $this->getUID(),
            'description'   => $this->getAssignName(null),
            'mime_type'     => $this->getUserVisibleName(),
            'name'          => $this->getName(),
            'type'          => 'file_format'
        ];
    }

    public static function getFromExtension($ext)
    {
        $db = db::singleton();
        $sql = "SELECT uid_documento_atributo_formato_permitido FROM ".  TABLE_FORMATO ." WHERE SUBSTR(extension, INSTR(extension, '/')+1) = '". db::scape($ext) ."' ";
        if (($uid = $db->query($sql, 0, 0)) && is_numeric($uid)) {
            return new self($uid);
        }

        return false;
    }

    public static function publicFields()
    {
        $arrayCampos    = new FieldList();
        $fieldOptions   = ["tag" => "input", "type" => "text", "blank" => false];

        $arrayCampos["descripcion"] = new FormField($fieldOptions);
        $arrayCampos["extension"]   = new FormField($fieldOptions);

        return $arrayCampos;
    }
}
