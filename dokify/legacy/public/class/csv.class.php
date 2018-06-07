<?php

class csv extends excel
{
    //VOLCADO DE LOS DATOS AL FICHERO
    public function Generar($filename, $cabeceras = false)
    {
        if (!$this->db->getNumRows($this->recordset)) {
            return false;
        }

        if (strpos(".csv",$filename) === false) {
            $filename .= ".csv";
        }

        // HTTP headers
        $this->Cabecera($filename);

        // Dibujar..
        return $this->getData($cabeceras, true);
    }

    public function getData($cabeceras = false, $flush = false)
    {
        $lines = array();
        $i = 0;

        while ($records = db::fetch_array($this->recordset, MYSQLI_ASSOC)) {
            if ($i == 0 && $cabeceras) {
                $linea = array();
                foreach ($records as $id => $valor) {
                    $linea[] = self::encapsulate($id);
                }

                if ($flush) {
                    echo implode(";", $linea) . "\n";
                } else {
                    $lineas[] = implode(";", $linea);
                }
            }

            $records = array_map("csv::encapsulate", $records);

            if ($flush) {
                echo implode(";", $records) . "\n";
            } else {
                $lineas[] = implode(";", $records);
            }

            $i++;
        }

        if ($flush) {
            return true;
        } else {
            return implode("\n", $lineas);
        }
    }

    public static function encapsulate($string)
    {
        return '"'. str_replace(array("\n","\r"), " ", $string).'"';
    }

    private function Cabecera($filename)
    {
        header("Content-type: text/csv", true);
        header("Content-Disposition: attachment; filename=$filename", true);
        header("Expires: 0", true);
        header("Cache-Control: must-revalidate, post-check=0,pre-check=0", true);
        header("Pragma: public", true);
    }
}
