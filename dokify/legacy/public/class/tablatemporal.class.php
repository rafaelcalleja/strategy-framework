<?php

class tablatemporal
{
    protected $tabla;
    protected $nombre_tabla;
    protected $campos;
    protected $db;
    protected $temporary;
    public $fulltext;

    public function __construct($tabla, $temporary = true)
    {
        $auxiliar = explode(".", $tabla);

        $this->campos = array();
        $this->tabla = $tabla;
        $this->db = db::singleton();
        $this->nombre_tabla = end($auxiliar);
        $this->fulltext = false;
        $this->temporary = $temporary ? "TEMPORARY" : "";
        $this->charset = false;
    }

    public function campo($sql)
    {
        $this->campos[] = $sql;
    }

    public function numeroCampos($numero)
    {
        for ($i = 0; $i < $numero; $i++) {
            $this->campos[] = "campo_$i VARCHAR(400) NOT NULL ";
        }
    }

    public function crear()
    {
        if (!count($this->campos)) {
            return false;
        }

        $sql = "CREATE {$this->temporary} TABLE IF NOT EXISTS {$this->tabla} (
            uid_{$this->nombre_tabla} INT(15) NOT NULL auto_increment," . implode(", ", $this->campos) . ",
            PRIMARY KEY (uid_{$this->nombre_tabla})
        ";

        $engine = "MyISAM";
        if ($this->fulltext) {
            $sql .= ",  FULLTEXT ({$this->fulltext})";

            // MySQL 5.6 has issues with FULLTEXT and MyISAM tables
            $engine = "InnoDB";
        }

        $sql .=" ) ENGINE={$engine}";

        if ($this->charset) {
            $sql .= " DEFAULT {$this->charset}";
        }

        return $this->db->query($sql);
    }
}
