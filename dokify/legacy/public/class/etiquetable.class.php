<?php
abstract class etiquetable extends elemento implements Ietiquetable {

    public static function actualizarEtiquetasMasivamente($elementos, $list = array() )
    {
        if( count($elementos) && is_traversable($elementos) ){
            $done = true;
            foreach($elementos as $item){
                $ok = $item->actualizarEtiquetas($list);
                if(!$ok){
                    $done = false;
                }
            }
            return $done;
        }
        return null;
    }


    /** ACTUALIZAR ETIQUETAS ASIGNADAS A ESTE USUARIO */
    public function actualizarEtiquetas($list=array())
    {
        // $tabla = TABLE_PERFIL ."_etiqueta";
        //$currentUIDElemento = ( $uid ) ? $uid : obtener_uid_seleccionado();//db::scape( $_REQUEST["poid"] );

        $tabla = "{$this->tabla}_etiqueta";
        $campo = db::getPrimaryKey($this->tabla);
        $sql = "DELETE FROM $tabla WHERE $campo = {$this->getUID()}";

        if( $this->db->query($sql) ){
            if( !count($list) ){ return true; }
            $idEtiquetas = array_map("db::scape", $list);
            $inserts = array();

            foreach( $idEtiquetas as $idEtiqueta ){
                if( $idEtiqueta )
                    $inserts[] = "( {$this->getUID()}, $idEtiqueta )";
            }

            if( !count($inserts) ){ return "error_no_datos"; }

            $sql = "INSERT INTO $tabla ( $campo, uid_etiqueta ) VALUES ". implode(",", $inserts);
            $estado = $this->db->query( $sql );
            if( $estado ){ return true; } else { return $this->db->lastErrorString(); }
        } else {
            return $this->db->lastErrorString();
        }
    }

    /**
     * Delete the element labels
     * @return bool
     */
    public function deleteLabels()
    {
        $tabla = "{$this->tabla}_etiqueta";
        $campo = db::getPrimaryKey($this->tabla);
        $sql = "DELETE FROM $tabla WHERE $campo = {$this->getUID()}";

        return (bool) $this->db->query($sql);
    }


    public function obtenerEtiquetas()
    {
        $campo = db::getPrimaryKey($this->tabla);
        $sql = "
        SELECT e.uid_etiqueta
        FROM ". TABLE_ETIQUETA ." e
        INNER JOIN {$this->tabla}_etiqueta pe
        USING( uid_etiqueta )
        WHERE {$campo} = {$this->getUID()}";

        $datos = $this->db->query( $sql, "*", 0, "etiqueta" );

        if( $datos && count($datos) ){
            return new ArrayObjectList($datos);
        } else {
            return new ArrayObjectList;
        }
    }

    /**
     * Check the element has a label
     * @param  $labelUid
     * @return boolean
     */
    public function hasLabel($labelUid)
    {
        $campo = db::getPrimaryKey($this->tabla);
        $sql = "
        SELECT e.uid_etiqueta
        FROM ". TABLE_ETIQUETA ." e
        INNER JOIN {$this->tabla}_etiqueta pe
        USING( uid_etiqueta )
        WHERE {$campo} = {$this->getUID()}
        AND e.uid_etiqueta = {$labelUid}";

        return (bool) $this->db->query($sql, 0, 0);
    }

}
?>
