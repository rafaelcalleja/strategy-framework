<?php
class excel {
    public $sql;        //CONSULTA
    public $recordset;
    //public $campos;
    public $paginacion;//NUMERO DE REGISTROS POR PAGINA
    public $workbook;
    public $titulo;
    public $worksheet = 0;
    public $db;
    public $headers;

    public function __construct($sql=null,$paginacion=null){
        $this->paginacion=($paginacion)?$paginacion:30000;
        if( $sql ){
            $this->db = db::singleton();
            $this->sql=$sql;
            $this->recordset = $this->db->query($sql);

            if (!$this->recordset) error_log('error al general fichero excel ['. $this->db->lastError() .']');
        }
    }

    public function getRows () {
        if (!isset($this->recordset->num_rows)) return 0;
        return $this->recordset->num_rows;
    }

    public function addHeader ($header) {
        $this->headers[] = $header;
    }

    //VOLCADO DE LOS DATOS AL FICHERO EXCEL
    public function Generar($filename, $cabeceras = false)
    {
        if (!$this->db->getNumRows($this->recordset)) {
            return false;
        }

        if (strpos($filename, ".xls") === false){
            $filename .= ".xls";
        }

        // HTTP headers
        $this->Cabecera($filename);

        require_once(DIR_CLASS . 'excel/Worksheet.php');
        require_once(DIR_CLASS . 'excel/Workbook.php');
        // Creating a workbook
        $this->workbook = new Workbook("-");

        $CabeceraCampo =& $this->workbook->add_format();
        $CabeceraCampo->set_size(10);
        $CabeceraCampo->set_bold();
        $CabeceraCampo->set_align('center');
        $CabeceraCampo->set_color('white');
        $CabeceraCampo->set_pattern();
        $CabeceraCampo->set_fg_color('red');

        $EstiloTitulo =& $this->workbook->add_format();
        $EstiloTitulo->set_size(10);
        $EstiloTitulo->set_bold();
        $EstiloTitulo->set_align('center');
        $EstiloTitulo->set_color('red');

        // --- cache styles
        $customStyles = array();

        $dateFormat =& $this->workbook->add_format();
        $dateFormat->set_num_format('D-MMM-YY');

        $i = 0;
        while ($records = db::fetch_array($this->recordset, MYSQLI_ASSOC)) {
            if (($i % $this->paginacion) == 0) {
                $Pagina= ($i+1) . '-' . ($i+$this->paginacion);
                $row = 0;
                $c = 0;

                $worksheet[$Pagina] = $this->workbook->add_worksheet($Pagina);
                $this->worksheet++;

                if ($this->titulo) {
                    $worksheet[$Pagina]->write_string($row, $c, $this->titulo, $EstiloTitulo);
                    $row++;
                }

                if ($cabeceras) {
                    foreach ($records as $id => $valor) {
                        $worksheet[$Pagina]->write_string($row, $c, $id, $CabeceraCampo);
                        $c++;
                    }
                    $row++;
                }

                // -- by default, dont touch it
                $nextRow = array($row);

                if ($this->headers) {
                    foreach ($this->headers as $i => $header) {
                        $colIndex = 0;
                        foreach ($header as $block) {
                            // --- no hacemos nada si no hay colspan
                            if (!$block['colspan']) {
                                continue;
                            }

                            $color = $block['color'];
                            $numcols = $block['colspan'];
                            $numrows = $block['rowspan'];

                            // --- create or use a style
                            if (isset($customStyles[$color])) {
                                $custom = $customStyles[$color];
                            } else {
                                $custom =& $this->workbook->add_format();
                                $custom->set_size(10);
                                $custom->set_bold();
                                $custom->set_align('center');
                                $custom->set_align('vcenter');
                                $custom->set_pattern();
                                $custom->set_fg_color($color);

                                $customStyles[$color] =& $custom;
                            }

                            $lastCol = $colIndex + $numcols - 1;
                            $lastRow = $row + $numrows - 1;

                            $worksheet[$Pagina]->merge_cells($row, $colIndex, $lastRow, $lastCol);
                            $worksheet[$Pagina]->write_string($row, $colIndex, utf8_decode($block['title']), $custom);

                            $colIndex = $lastCol + 1;

                            $nextRow[] = $lastRow + 1;
                        }

                        $row++;
                        $nextRow[] = $row;
                    }
                }

                $row = max($nextRow);
            }

            $col = 0;
            foreach ($records as $id => $valor) {
                $format = null;
                if ($daysInExcell = self::isDate($valor)) {
                    $format = $dateFormat;
                    $valor = $daysInExcell;
                }

                $worksheet[$Pagina]->write($row, $col, $valor, $format);
                $col++;
            }

            $i++;
            $row++;
        }

        $this->workbook->close();

        return true; // jose - esta libreria la tenemos que actualizar ya (2013-05) pero para resolver el bug nos sirve así
        //return (bool) @$this->_fileclosed;
    }

    public function generate($filename, $cabeceras = false)
    {
        return $this->Generar($filename, $cabeceras);
    }

    public static function isDate($val){
        @list($day, $month, $year) = explode("/", $val);
        if( is_numeric($day) && $day && is_numeric($month) && $month && is_numeric($year) && $year ){
            if( $day < 32 && $month < 13 && $year > 999 && $year < 10000){
                $timestamp = strtotime($year.'/'.$month.'/'.$day);
                if( $timestamp ){
                    $seconds_in_a_day = 86400; //60*60*24; // number of seconds in a day
                    $ut_to_ed_diff = $seconds_in_a_day * 25569; // 25569 Días desde el "Day Zero" de excell hasta el uso de timestamp pasados a segundos

                    return ( $timestamp + $ut_to_ed_diff) / $seconds_in_a_day;
                }
            }
        }

        return false;
    }

    public function getNewWorkSheet(){
        if( !$this->workbook ){
            require_once( DIR_CLASS . 'excel/Worksheet.php');
            require_once( DIR_CLASS . 'excel/Workbook.php');

            $this->workbook = new Workbook("-");
        }

        return $this->workbook->add_worksheet("P" . $this->worksheet++ );
    }

    public function send($filename){
        if( !trim($filename) ){ $filename = "excel.xls"; }
        if( strpos($filename,".xls") === false ){ $filename .= ".xls"; }
        $this->Cabecera($filename);
        $this->workbook->close();
    }


    private function Cabecera($filename){
        header("Content-type: application/vnd.ms-excel",true);
        header("Content-Disposition: attachment; filename=\"$filename\"",true);
        header("Expires: 0",true);
        header("Cache-Control: must-revalidate, post-check=0,pre-check=0",true);
        header("Pragma: public",true);
    }



    public static function getCharIndex($i){
        $chars = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z");
        return strtoupper($chars[$i]);
    }

    public static function getFormat(excel $excel, $format){
        $workbook = $excel->workbook;
        $formato =& $workbook->add_format();

        switch( $format ){
            case "h1":
                $formato->set_size(24);
                $formato->set_bold();
                //$formato->set_align('center');
            break;
            case "h2":
                $formato->set_size(18);
                $formato->set_bold();
                //$formato->set_align('center');
            break;
            case "h3":
                $formato->set_size(12);
                $formato->set_bold();
                //$formato->set_align('center');
            break;
            case "bold":
                $formato->set_bold();
            break;
            case "bold-right":
                $formato->set_bold();
                $formato->set_align('right');
            break;
            case "light":
                $formato->set_size(8);
                $formato->set_color("grey");
            break;
        }

        return $formato;
    }
}
?>
