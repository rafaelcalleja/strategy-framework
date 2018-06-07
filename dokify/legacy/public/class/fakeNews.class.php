<?php

class fakeNews extends noticia
{

    protected $data     = array();
    protected $usuario  = null;

    public function __construct($data, $usuario = false)
    {
        $this->data = $data;
        $this->usuario = $usuario;

        $this->uid = uniqid();
    }

    public function getUserVisibleName()
    {
        return $this->data["title"];
    }

    public function getCompany()
    {
        return $this->usuario->getCompany();
    }

    public function getDate()
    {
        return "Noticia patrocinada";
    }

    public function getHTML()
    {
        return $this->data["html"];
    }

    /**
     *
     * @param  usuario $usuario the viewer
     * @param  integer $index
     * @return ArraObjectList
     *
     * @SuppressWarnings("unused")
     *
     */
    public static function getPubli($usuario, $index = 1)
    {
        $publicidad = new ArrayObjectList;

        return $publicidad;
    }
}
