<?php

class SpreadSheet
{
    const DEFAULT_PAGINATION = 60000;

    protected $db;
    protected $SQL;
    protected $pagination;
    protected $rs;
    protected $columTitles = true;
    protected $headers = array();
    protected $error = null;
    protected $autoExpand = true;
    protected $rowCount = 0;

    public function __construct($SQL, $pagination = null) {
        $this->db = db::singleton();
        $this->SQL= $SQL;
        $this->rs = $this->db->query($SQL);
        $this->pagination = $pagination ? $pagination : self::DEFAULT_PAGINATION;

        if (!$this->rs) {
            $this->error = $this->db->lastError();
            error_log('error al general fichero excel ['. $this->db->lastError() .']');
        }

        $this->rowCount = $this->db->getNumRows();
    }

    public function getRowCount()
    {
        return $this->rowCount;
    }

    public function getError () {
        return $this->error;
    }

    public function addHeader ($header) {
        $this->headers[] = $header;
    }

    public function showTitles($bool) {
        $this->columTitles = $bool;
    }

    public function send($filename = 'file', $excelFormat = 'xls')
    {
        $rowIndex = 1;
        $pageIndex = 0;
        $sheet = null;
        $excel = new PHPExcel();
        $titleFormat = $this->getFormat('title');

        while ($row = db::fetch_array($this->rs, MYSQLI_ASSOC)) {
            $firstLine = ($rowIndex-1) % $this->pagination === 0;

            if ($firstLine) {
                $rowIndex = 1;

                if ($pageIndex == 0) {
                    $sheet = $excel->setActiveSheetIndex($pageIndex);
                } else {
                    $excel->createSheet();
                    $sheet = $excel->setActiveSheetIndex($pageIndex);
                }

                if ($this->columTitles) {
                    $colIndex = 0;
                    foreach ($row as $key => $val) {
                        $cell = $sheet->getCellByColumnAndRow($colIndex, $rowIndex);
                        $style = $sheet->getStyleByColumnAndRow($colIndex, $rowIndex);


                        $cell->setValue(utf8_encode($key));
                        $style->applyFromArray($titleFormat);

                        $colIndex++;
                    }

                    $rowIndex++;
                }

                // -- by default, dont touch it
                $nextRow = array($rowIndex);

                if ($this->headers) {
                    foreach ($this->headers as $i => $header) {
                        $colIndex = 0;
                        foreach ($header as $block) {
                            // --- no hacemos nada si no hay colspan
                            if (!$block['colspan']) {
                                continue;
                            }

                            $color = str_replace("#", "", $block['color']);
                            $numcols = $block['colspan'];
                            $numrows = $block['rowspan'];

                            $format = array(
                                'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color'=> array('rgb' => $color)),
                            );

                            $lastCol = $colIndex + $numcols - 1;
                            $lastRow = $rowIndex + $numrows - 1;

                            $sheet->mergeCellsByColumnAndRow($colIndex, $rowIndex, $lastCol, $lastRow);

                            $style = $sheet->getStyleByColumnAndRow($colIndex, $rowIndex);
                            $style->applyFromArray($format);
                            $style->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                            $style->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                            $sheet->setCellValueByColumnAndRow($colIndex, $rowIndex, $block['title']);



                            $colIndex = $lastCol + 1;


                            // save the max position, not for the next header but for the first excel line of data
                            $nextRow[] = $lastRow + 1;
                        }

                        $rowIndex++;
                        $nextRow[] = $rowIndex;
                    }
                }

                $rowIndex = max($nextRow);
                $pageIndex++;
            }

            $colIndex = 0;
            foreach ($row as $key => $val) {
                if ($val) {
                    $sheet->setCellValueByColumnAndRow($colIndex, $rowIndex, utf8_encode($val));
                }

                $colIndex++;
            }

            $rowIndex++;
        }

        if ($rowIndex && $this->autoExpand && $sheet) {
            // auto-expand columns
            $cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(true);
            foreach ($cellIterator as $cell) {
                $sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
            }
        }


        switch ($excelFormat) {
            case 'xlsx':
                $docType = 'Excel2007';
                break;

            default:
                $docType = 'Excel5';
                break;
        }

        $writer = PHPExcel_IOFactory::createWriter($excel, $docType);

        $this->setHeaders($filename . '.' . $excelFormat);
        return $writer->save('php://output');
    }

    public function getFormat ($format) {

        switch ($format) {
            case 'title':
                return array(
                    'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color'=> array( 'argb' => 'FFFF0000')),
                    'font' => array('bold' => true, 'color' => array('rgb' => 'FFFFFF')),
                );

                break;

            default:
                return array();
                break;
        }

    }
    public function setHeaders($filename) {
        header("Content-type: application/vnd.ms-excel",true);
        header("Content-Disposition: attachment; filename=\"$filename\"",true);
        header("Expires: 0",true);
        header("Cache-Control: must-revalidate, post-check=0,pre-check=0",true);
        header("Pragma: public",true);
    }

}
