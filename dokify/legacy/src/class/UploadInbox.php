<?php

class UploadInbox extends elemento implements Ielemento
{
    private $files;

    public function __construct($param, $extra = false) {
        $this->tabla = DB_DATA . ".uploadinbox";
        $this->nombre_tabla = "uploadinbox";
        parent::instance($param, NULL);
    }

    public function getUserVisibleName () {
        return $this->getUID();
    }

    public static function getOldest () {
        $table = DB_DATA . ".uploadinbox";
        $SQL = "SELECT uid_uploadinbox FROM {$table} WHERE progress = 0 ORDER BY uid_uploadinbox ASC LIMIT 1";

        if ($uid = db::get($SQL, 0, 0)) {
            return new self($uid);
        }

        return NULL;
    }

    public function getText () {
        return $this->obtenerDato('text');
    }


    public function setProgress ($progress) {
        return $this->update(array("progress" => $progress), elemento::PUBLIFIELDS_MODE_PROGRESS);
    }

    public function getProgress () {
        return $this->obtenerDato("progress");
    }

    public function getEmail () {
        return $this->obtenerDato("from");
    }

    public function locateUser () {
        $email = $this->getEmail();

        $SQL = "SELECT uid_usuario FROM ". TABLE_USUARIO . " WHERE email LIKE '". db::scape($email) ."' LIMIT 1";
        if ($uid = db::get($SQL, 0, 0)) {
            return new usuario($uid);
        }

        return NULL;
    }

    public function updateFiles($files)
    {
        $this->files = $files;
        $files = db::scape(json_encode($files));

        $sql = "UPDATE {$this->tabla} SET files = '{$files}' WHERE uid_uploadinbox = {$this->getUID()}";
        return $this->db->query($sql);
    }

    public function getFiles () {
        if ($this->files) {
            return $this->files;
        }

        $setFiles = $this->obtenerDato('files');

        if ($setFiles) {
            return $this->files = json_decode($setFiles, true);
        }
    }

    public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false) {
        $fieldList = new FieldList;

        switch ($modo) {
            case elemento::PUBLIFIELDS_MODE_PROGRESS:
                $fieldList["progress"] = new FormField(array("tag" => "input"));
                break;
            
            default:
                $fieldList["from"] = new FormField(array("tag" => "input"));
                $fieldList["fromName"] = new FormField(array("tag" => "input"));
                $fieldList["files"] = new FormField(array("tag" => "input"));
                $fieldList["text"] = new FormField(array("tag" => "input"));
                $fieldList["subject"] = new FormField(array("tag" => "input"));
                break;
        }

        return $fieldList;
    }
}
