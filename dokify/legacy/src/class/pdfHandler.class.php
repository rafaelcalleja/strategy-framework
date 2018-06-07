<?php

require_once __DIR__ . '/../../vendor/fpdi/fpdi.php';

use Dokify\Exception\InvalidPdfTextsException;
use mikehaertl\wkhtmlto\Pdf;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class pdfHandler extends FPDI {

    const LOG_FILE = '/var/log/dokify/pdf.log';

    protected $file;
    protected $filesize;
    protected $original;
    protected $numPages;
    protected $cache;
    protected $words;
    protected $dates;
    protected $cifLines;
    protected $dateDocument;
    protected $employees;
    protected $clears = array();
    protected $gs = false;
    public $creator;

    static $debug = false;

    static $creatorsToConvert = array('Crystal', 'pdfFactory');

    static $unknowns = [' y', ' v', ' c'];

    public $buffers = array();
    public $_buffers = array();

    const MAX_FILE_SIZE = 6291456;


    const PATTERN_EXTRACT_TEXTS = "/\\((.*?)(?<!\\\)\\)/";
    const PATTERN_EXTRACT_BT = "/BT\s(.*?)\sET/s";

    const LINE_TYPE_START       = 1;
    const LINE_TYPE_FONT        = 2;
    const LINE_TYPE_COORDS      = 3;
    const LINE_TYPE_OFFSET      = 4;
    const LINE_TYPE_TEXT        = 5;
    const LINE_TYPE_UNKNOWN     = 6;
    const LINE_TYPE_POSITION    = 7;
    const LINE_TYPE_SAVE        = 8;
    const LINE_TYPE_REVERT      = 9;
    const LINE_TYPE_RE          = 10;
    const LINE_TYPE_BOX         = 11;
    const LINE_TYPE_LEADING     = 12;
    const LINE_TYPE_TXTCURSOR   = 13;
    const LINE_TYPE_TXTPOSITION = 14;
    const SEARCH_METHOD_FIRST_TIME = "search_in_time";
    const SEARCH_METHOD_FIRST_DOCUMENT = "search_in_document";
    const SEARCH_METHOD_LAST_DOCUMENT = "search_last_document";
    const SEARCH_METHOD_MOST_REPEATED = "search_most_repeated";


    // --- random num value
    const EXCEPTION_CODE_BREAK_LOOP = 12345678;

    protected static $commands = [];

    public function __construct($file, $read = true)
    {
        ini_set('memory_limit', '512M');
        $this->cache = cache::singleton();
        $this->original = $file;
        $this->filesize = filesize($file);

        // create a log channel
        $this->log = new Logger('pdf');
        $this->log->pushHandler(new StreamHandler(self::LOG_FILE, Logger::DEBUG));
        $this->log->pushProcessor(function ($record) use ($file) {
            $record['extra']['tmp'] = basename($file);
            return $record;
        });

        $size = archivo::formatBytes($this->filesize);
        // $this->log->addInfo("processing", ["size" => $size]);

        if (!$read) {
            return $this->file = $file;
        }

        try {
            return $this->readBuffers($file);
        } catch (InvalidPdfTextsException $e) {
            if (null !== $this->parsers) {
                $this->cleanUp();
            }

            $this->gs = true;

            // try to normalize file with GhostScript
            $normalized = self::gs($file);
            $this->log->addNotice("normalize", ["out" => $normalized]);
        } catch (Exception $e) {
            // this means FPDI cannot read the file
            // next steps expect $normalized to be set
            $normalized = $file;
        }


        try {
            return $this->readBuffers($normalized);
        } catch (InvalidPdfTextsException $e) {
            if (null !== $this->parsers) {
                $this->cleanUp();
            }
        } catch (Exception $e) {
            // this means FPDI cannot read the file
            // next steps expect $file
        }

        $uncompressed = $this->uncompress($file);
        // $this->log->addNotice("uncompress", ["out" => $uncompressed]);

        $this->readBuffers($uncompressed);
    }

    public function cleanUp () {
        parent::cleanUp();
        $this->cache->clear('pdf-obj-*');
        $this->buffers = [];
        $this->_buffers = [];
        $this->_importedPages = [];
        $this->tpls = [];
        return true;
    }


    public static function getCreationTimestamp($file) {
        if ($info = self::getInfoFromFile($file)) {
            return strtotime($info->creationdate);
        }
    }


    public static function getInfoFromFile($file) {
        $cmd = "pdfinfo {$file}";
        if (self::$debug === false) {
            $cmd .= " 2>&1";
        }

        list($out, $code) = self::runCommand($cmd);

        if ($code === 0 && $out && count($out) > 1) {
            $props = array();

            foreach ($out as $ln) {
                $pos = strpos($ln, ':');
                $prop = substr($ln, 0, $pos);
                $value = substr($ln, $pos+1);

                $key = str_replace(' ', '_', trim(strtolower($prop)));
                $val = trim(strtolower($value));

                $props[$key] = $val;
            }

            return (object) $props;
        }

        return false;
    }

    /**
     * @param $command
     * @return array
     */
    private static function runCommand($command)
    {
        $commandHash = md5($command);

        if(true === empty(static::$commands[$commandHash])) {
            exec($command, $out, $code);
            static::$commands[$commandHash] = [$out, $code];
        }

        return static::$commands[$commandHash];
    }

    public static function extractText ($file, $ocr = false, $forceOCR = false, $forceConvert = false, $onlyFirstPage = false)
    {
        $cache = cache::singleton();

        // --- check cache
        $cacheKey = implode('-', array(__CLASS__, __FUNCTION__, base64_encode($file), $ocr, $forceOCR, $forceConvert, $onlyFirstPage));
        if (($texts = $cache->getData($cacheKey)) !== null) {
            return $texts;
        }

        $app = \Dokify\Application::getInstance();
        $pdfHandlerLog = $app['log.pdfhandler'];
        $pdfHandlerLogTrace = [
            'method' => 'extractText',
            'commands' => [],
        ];

        // $info   = self::getInfoFromFile($file);
        // $ext    = isset($info->title) ? archivo::getExtension($info->title) : null;
        $words  = "";

        if ($forceOCR === false) {
            $cmd = "pdftotext -layout {$file} -";
            if (self::$debug === false) {
                $cmd .= " 2>&1";
            }

            $pdfHandlerLogTrace['commands'][] = $cmd;

            list($out, $code) = self::runCommand($cmd);
            if ($code === 0 && $out && count($out) > 1) {
                $texts  = implode("\n", $out);
                $errors = stripos($texts, "Syntax Error: ");
                $len    = strlen($texts);

                if (($errors && $len <= 300) || self::isUnrecognizableText($texts)) {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdfHandlerLogTrace['commands'][] = "\Smalot\PdfParser\Parser::parseFile($file)";
                    try {
                        $pdf = $parser->parseFile($file);
                        $texts = $pdf->getText();
                    } catch (\Exception $e) {
                        $texts = null;
                    }
                }

                if ($texts) {
                    $cache->set($cacheKey, $texts);
                    $pdfHandlerLog->addDebug("Return text without OCR", $pdfHandlerLogTrace);
                    return $texts;
                }
            }

            if ($ocr === false) {
                $pdfHandlerLog->addDebug("No text to return without OCR", $pdfHandlerLogTrace);
                return false;
            }
        }

        // --- not able to read directly, try tesseract
        $temp = tempnam('/tmp', 'ocr-');

        //$convert = "nice -n 19 convert -quality 100 -density 150 -sharpen 0x1.0 {$file} -append {$temp}.jpg";

        $pages = "";
        if ($onlyFirstPage) {
            $pages = "-dFirstPage=1 -dLastPage=2";
        }

        $convert = "gs -o {$temp}.tiff -r330 {$pages} -sDEVICE=tiffgray {$file}";
        if (self::$debug === false) {
            $convert .= " 2>&1";
        }
        $pdfHandlerLogTrace['commands'][] = $convert;
        list($out, $code) = self::runCommand($convert);

        if ($code !== 0) {
            error_log("[pdfHandler] Cannot execute command ({$convert}). Exit with code {$code}");
            $pdfHandlerLog->addDebug("Convert crash", $pdfHandlerLogTrace);
            return $words;
        }

        // try cleaning the image
        if ($forceConvert) {
            $params = "-morphology Convolve DoG:1,100,0 -negate -normalize -blur 0x1 -channel RBG -level 60%,91%,0.1";
            $convert = "convert {$temp}.tiff {$params} {$temp}.clean.tiff";
            $pdfHandlerLogTrace['commands'][] = $convert;
            list($out, $code) = self::runCommand($convert);
            if ($code !== 0) {
                error_log("[pdfHandler] Cannot execute command ({$convert}). Exit with code {$code}");
                $pdfHandlerLog->addDebug("Force convert crash", $pdfHandlerLogTrace);
                return $words;
            }

            // remove old tiff
            unlink("{$temp}.tiff");

            // change tmp name
            $temp   = $temp . ".clean";
        }

        $tesseract = "timeout 300 tesseract {$temp}.tiff -psm 1 -l spa {$temp}";
        if (self::$debug === false) {
            $tesseract .= " 2>&1";
        }
        $pdfHandlerLogTrace['commands'][] = $tesseract;
        list($out, $code) = self::runCommand($tesseract);
        if ($code !== 0) {
            error_log("[pdfHandler] Cannot execute command ({$tesseract}). Exit with code {$code}");
            $pdfHandlerLog->addDebug("Tesseract crash", $pdfHandlerLogTrace);
            return $words;
        }

        $fileData = file_get_contents("{$temp}.txt");

        // unlink("{$temp}.tiff");
        unlink("{$temp}.txt");

        $cache->set($cacheKey, $fileData);

        if ($forceOCR === false) {
            // if we are here, event with forceOCR set to false, we're going to cache the key
            // as if it has been called with it to true, so next call will use cache because
            // the result will be the same
            $cacheKey = implode('-', array(__CLASS__, __FUNCTION__, base64_encode($file), $ocr, 1, $forceConvert));
            $cache->set($cacheKey, $fileData);
        }

        $pdfHandlerLog->addDebug("Return text with OCR", $pdfHandlerLogTrace);
        return $fileData;
    }

    public static function getPlainWords ($file, $cb = NULL, $ocr = false, $forceORC = false) {
        $words = array();
        $symbols = array('€', '$');

        $text = self::extractText($file, $ocr, $forceORC);
        if (!trim($text)) return $words;
        $out = explode("\n", $text);


        foreach ($out as $i => $line) {
            if (!is_string($line) || !trim($line)) continue;
            $strings = preg_split("/[\s\/\.]/", $line);

            while (($str = array_shift($strings)) !== NULL) {
                //$str = strtolower($str);
                $str = mb_strtolower($str, 'UTF-8');

                $str = ltrim($str, '0');
                $str = ltrim($str, '(');

                foreach ($symbols as $symbol) {
                    if ($str && strstr($str, $symbol) !== false) {
                        $str = str_replace($symbol, '', $str);
                        $words[] = $symbol;
                    }
                }

                $str = rtrim($str, ':');
                $str = rtrim($str, '?');
                $str = rtrim($str, ',');
                $str = rtrim($str, '.');
                $str = rtrim($str, ')');
                $str = rtrim($str, '(');

                $str = trim($str);

                // --- palabras unidas por - (no numeros)
                if ($str && strstr($str, '-')) {
                    $pieces = explode('-', $str);
                    foreach ($pieces as $piece) {
                        if (!is_numeric($piece)) $strings[] = $piece;
                    }
                }

                // --- sometimes words are separated with multiple 0
                if ($str && strstr($str, '00000')) {
                    $pieces = explode('00000', $str);
                    foreach ($pieces as $piece) {
                        if (!is_numeric($piece)) $strings[] = $piece;
                    }
                }


                // parece que tenemos un año, vamos a ver si encontramos en los strings previos algo...
                if (is_numeric($str) && $str > 1900 && $str < 2099) {
                    $newline = str_replace(' ', '-', $line);
                    if (preg_match('/\d{2}-\d{2}-\d{4}/', $newline, $matches) && $date = reset($matches)) {
                        $strings[] = $date;
                    }
                }

                if (is_callable($cb) && ($returnStr = call_user_func($cb, $str))) return $returnStr;

                $l = strlen($str);

                if ($l > 2) $words[] = $str;
            }
        }

        if (is_callable($cb)) return false;
        $words = array_unique($words);

        return $words;
    }

    private function uncompress ($file) {
        $tmpfilename = uniqid() . ".uncompressed.pdf";
        $tmpfile = "/tmp/{$tmpfilename}";

        $cmd = "pdftk {$file} output {$tmpfile} uncompress";
        if (self::$debug === false) {
            $cmd .= " 2>&1";
        }

        list($out, $code) = self::runCommand($cmd);

        if ($code === 0) {
            $size = filesize($tmpfile);

            if (self::$debug) echo archivo::formatBytes(memory_get_usage()) . " uncompress \n"; // 36640

            // if ($size > self::MAX_FILE_SIZE) {
            //  throw new Exception("file {$tmpfilename} size {$size} > " . self::MAX_FILE_SIZE);
            // }

            return $tmpfile;
        }

        throw new Exception("error using pdftk [$code]: " . implode("\n", $out));
    }

    public static function merge ($files = array(), $delete = true) {
        $output = tempnam(sys_get_temp_dir(), '') . '.pdf';

        self::runCommand("cd ".sys_get_temp_dir()." && pdftk ".implode(' ', $files)." cat output ".$output);

        if ($delete) {
            foreach ($files as $file) {
                unlink($file);
            }
        }

        return $output;
    }

    public static function htmlToPdf ($html) {
        $pdf = new Pdf($html);

        $pdf->setOptions(
            [
                'commandOptions' => array(
                    'enableXvfb' => true
                ),
                'margin-left' => '0mm',
                'margin-right' => '0mm',
                'margin-top' => '8mm',
                'page-width' => '210mm',
                'page-height' => '297mm',
            ]
        );

        return $pdf->toString();
    }

    /**
     * Get the pdf creator
     * @return string|false
     */
    public function getCreator()
    {
        if (isset($this->creator)) {
            return $this->creator;
        }

        $info = self::getInfoFromFile($this->original);

        if (isset($info->creator)) {
            return $this->creator = $info->creator;
        }

        return $this->creator = false;
    }

    private function readBuffers($file)
    {
        if (is_readable($file)) {
            $info = self::getInfoFromFile($file);

            // we need to convert when certain creators
            if (false === $this->gs && true === isset($info->creator)) {
                $convert = false;

                foreach (self::$creatorsToConvert as $creator) {
                    if (false !== stripos($info->creator, $creator)) {
                        $convert = true;
                        break;
                    }
                }

                if (true === $convert) {
                    throw new InvalidPdfTextsException;
                }
            }

            $this->file = $file;
            $this->numPages = $this->setSourceFile($this->file);

            parent::__construct('L');

            if (self::$debug) echo archivo::formatBytes(memory_get_usage()) . " read before \n";

            for ($page=1; $page <= $this->numPages; $page++) {
                $tplIdx = $this->importPage($page);

                // --- montar adecuadamente las lineas
                $this->tpls[$tplIdx]['buffer'] = $this->normalizeBuffer($this->tpls[$tplIdx]['buffer'], $page);

                $this->buffers[$tplIdx] =& $this->tpls[$tplIdx]['buffer'];
                $this->_buffers[$tplIdx] = $this->tpls[$tplIdx]['buffer'];
            }

            if (self::$debug) echo archivo::formatBytes(memory_get_usage()) . " read after \n"; // 36640

            $this->setOrientation();
        } else {
            error_log('pdfhandler cant read file ['. $file . ']');
        }

        return false;
    }

    public function isReadable () {
        return (bool) $this->numPages;
    }

    public function getFonts($page = 1) {
        if (isset($this->tpls[$page])) {
            return $this->tpls[$page]['fonts'];
        }

        return false;
    }

    public function getDifferences($page = 1) {
        if (isset($this->tpls[$page])) {
            return $this->tpls[$page]['differences'];
        }

        return false;
    }

    public function getFile() {
        return $this->file;
    }

    public function getCopy() {
        return new self($this->file, $this->DefOrientation);
    }


    public function getOrientation ($pageIndex = 1) {
        if (isset($this->tpls[$pageIndex])) {
            $w = @$this->tpls[$pageIndex]['w'];
            $h = @$this->tpls[$pageIndex]['h'];

            return $w > $h ? 'L' : 'P';
        }

        if ($info = self::getInfoFromFile($this->original)) {
            if (preg_match('/(\d+)\ x\ (\d+)/', $info->page_size, $matches)) {
                list ($match, $width, $height) = $matches;

                return $width > $height ? 'L' : 'P';
            }
        }
    }


    public function setOrientation ($orientation = false) {
        if (!$orientation) $orientation = $this->getOrientation(1);

        $size = $this->DefPageSize;


        if ($orientation == 'P') {
            $this->DefOrientation = 'P';
            $this->w = $size[0];
            $this->h = $size[1];
        } else {
            $this->DefOrientation = 'L';
            $this->w = $size[1];
            $this->h = $size[0];
        }
    }

    /***
       * Get PDF as data
       *
       */
    public function getAsPDF($pageIndex) {
        $this->compress = false;
        $this->pageIndex = $pageIndex;
        if ($this->state < 3) $this->Close();

        return $this->buffer;
    }

    public function reset () {
        $this->page = 0;
        $this->buffer = '';
        $this->state = 0;
        $this->n = 2;

        // --- needed to redo some checks at fpdi.php:480
        $this->_don_obj_stack = array();

        foreach ($this->_buffers as $i => $buffer) {
            $this->tpls[$i]['buffer'] = $buffer;
        }
    }


    public function getNumPages() {
        return $this->numPages;
    }


    public function remove($content, $page = NULL) {
        if ($page) {
            if (isset($this->buffers[$page])) {
                $this->buffers[$page] = str_replace($content, '', $this->buffers[$page]);
            }
        } else {
            foreach ($this->buffers as $i => $buffer) {
                $this->buffers[$i] = str_replace($content, '', $buffer);
            }
        }
    }


    public function addTexts($page, $texts, $line) {
        if (!isset($this->buffers[$page])) return false;


        $buffer = $this->buffers[$page];
        $y = $line['coords']['originalY'];
        $btIndex = $line['btIndex'];


        foreach ($texts as $i => $text) {

            if (preg_match_all("/(.*?Tm)/i", $text, $matches)) {
                $coordsData = explode(" ", $matches[0][0]);

                $rotated = (bool) $coordsData[2];

                $index = ($rotated == -1) ? 4 : 5;
                $coordsData[$index] = $y;

                $Tm = implode(' ', $coordsData);

                $texts[$i] = str_replace($matches[0][0], $Tm, $text);
            }
        }

        $new = implode("\n", $texts);

        // --- añadimos los textos junto al BT al que pertenece la linea de referencia
        // --- de esta forma no es necesario calcular las posiciones x/y de forma que nos valen
        // --- las posiciones raltivas en ese punto del buffer
        $bufferLines = $this->bufferToArray($buffer);
        if (isset($bufferLines[$btIndex])) {
            $bufferLines[$btIndex] = "{$new}\nBT";
        }


        // -- remove box layout
        foreach ($line['box'] as $boxLine) {
            $index = $boxLine['index'];
            if (isset($bufferLines[$index])) {
                $bufferLines[$index] = '';
            }
        }

        // --- join the lines again
        $buffer = implode("\n", $bufferLines);


        // -- save new buffer
        $this->buffers[$page] = $buffer;
    }


    public function getTexts($page, $lineY) {
        if (!isset($this->buffers[$page])) return false;


        $foundTexts = $this->each(function ($coords, $page, $index, $font, $raw, $handler, $lineY) {
            $x = $coords['x'];
            $y = $coords['y'];

            $originalX = $coords['originalX'];
            $originalY = $coords['originalY'];

            $start = $coords['start'];
            $end = $coords['end'];
            $inverted = $coords['inverted'];
            $rotated = $coords['rotated'];
            $fontSize = $font['size'];
            $map = $font['map'];
            $fontRaw = $font['raw'];
            $box = $coords['box'];

            $margin = (float) $fontSize / 6;
            $minY = $y - $margin;
            $maxY = $y + $margin;
            $match = ($lineY > $minY && $lineY < $maxY);

            $textPosition = $coords['textX'] || $coords['textY'];


            if ($match) {
                // $y = 300; $x = 10;
                if ($rotated == -1) {
                    $matrix = "{$originalY} {$originalX}";
                } else {
                    $matrix = "{$originalX} {$originalY}";
                }

                $bt = [];
                $bt[] = 'BT';
                $bt[] = "{$font['name']} {$font['size']} Tf";
                $bt[] = '0 0 0 rg';
                if ($textPosition) {
                    $bt[] = "{$coords['textX']} {$coords['textY']} TD";
                } else {
                    $bt[] = "{$start} {$end} {$rotated} {$inverted} {$matrix} Tm";
                }
                $bt[] = $raw;
                $bt[] = 'ET';

                return (object) [
                    'textX'     => $coords['textX'],
                    'textY'     => $coords['textY'],
                    'buffer'    => $bt,
                    'raw'       => $raw
                ];
            }
        }, array($lineY), $page, true);


        $texts = [];

        // Unificar bloques de texto que deben ir unidos
        foreach ($foundTexts as $i => $text) {
            $textPosition = $text->textX || $text->textY ? "{$text->textX}-{$text->textY}" : false;

            // si tenemos disposicion de texto que viene dada por un "TD"
            if ($textPosition) {

                // guardamos todos los bloques que tengan la misma disposicion
                if (isset($texts[$textPosition])) {
                    $texts[$textPosition]->raw .= "\n{$text->raw}";
                } else {
                    $texts[$textPosition] = $text;
                }
            } else {
                $texts[] = $text;
            }
        }

        // Unimos todos los bloques con la misma posición en uno solo
        foreach ($texts as &$text) {
            $text->buffer[4] = $text->raw;
            $text = implode("\n", $text->buffer);
        }


        return $texts;
    }

    public function clearLines($page, $min, $max, $keep = null) {
        if (!isset($this->buffers[$page])) return false;

        $clearIndex = "$page $min $max $keep";

        // --- caching system
        if (isset($this->clears[$clearIndex]) && $indexes = $this->clears[$clearIndex]) {
            $pieces = $this->bufferToArray ($this->buffers[$page]);
            foreach ($indexes as $index) {
                $pieces[$index] = '[] TJ';
            }

            $this->buffers[$page] = implode("\n", $pieces);
            return true;
        }

        $indexes = array();

        $this->each(function ($coords, $page, $index, $font, $raw, $handler, $min, $max, &$indexes) use ($keep) {
            $map = $font['map'];
            $x = $coords['x'];
            $y = $coords['y'];
            $inverted = $coords['inverted'];

            if ($min > $max) {
                list($min, $max) = array($max, $min);
            }

            $match = false;
            if ($y <= $max && $y >= $min) $match = true;

            if ($keep) {

                $keepMax = $keep + ($font['size']/6);
                $keepMin = $keep - ($font['size']/6);
                if ($y <= $keepMax && $y >= $keepMin) {
                    return;
                }
            }

            if ($match) {


                $pieces = $handler->bufferToArray ($handler->buffers[$page]);
                $indexes[] = $index;
                $pieces[$index] = '[] TJ';

                $handler->buffers[$page] = implode("\n", $pieces);
            }

        }, array($min, $max, &$indexes), $page);

        $this->clears[$clearIndex] = $indexes;
    }

    public function getText($sanitize = false)
    {
        $identifier = md5($this->file) . '-'. $sanitize .'-pdftext.txt';
        $contentCacheFile = "/tmp/{$identifier}";

        if (is_readable($contentCacheFile) && $cachedContent = file_get_contents($contentCacheFile)) {
            return $cachedContent;
        }

        $content = '';

        $this->each(function($coords, $page, $index, $font, $raw, $handler) use (&$content) {
            $raw    = str_replace('\\\\)', '\\\\ )', $raw);
            $offset = 0;

            while (preg_match(pdfHandler::PATTERN_EXTRACT_TEXTS, $raw, $match, PREG_OFFSET_CAPTURE, $offset)) {
                list($search, $capture) = $match;
                $offset = strlen($search[0]) + $search[1];

                // normalize captured data
                $string = $capture[0];
                $string = str_replace(array('\n', '\t', '\r'), array("\n", "\t", "\r"), $string);
                $string = self::unescape($string);

                // font maps
                $map    = $font['map'];
                $diffs  = $font['diffs'];

                if (count($map) || count($diffs)) {
                    $missings = [];

                    if (mb_detect_encoding($string, "UTF-8", true) === false) {
                        $strChars = str_split($string);
                    } else {
                        $strChars = preg_split('//u', $string);
                    }

                    foreach ($strChars as $i => $char) {
                        $dec = ord($char);

                        if (array_key_exists($dec, $diffs)) {
                            $strChars[$i] = mb_convert_encoding($diffs[$dec], 'UTF-8', 'ISO-8859-1');
                        }

                        if ($map) {
                            if (array_key_exists($dec, $map)) {
                                $strChars[$i] = $map[$dec];
                            } else {
                                if ($dec === 0) {
                                    unset($strChars[$i]);
                                } else {
                                    $missings[$dec] = $char;
                                }
                            }
                        }
                    }

                    $string = implode('', $strChars);
                    // maybe the raw data is in non-utf8 format
                } elseif (mb_detect_encoding($string, "UTF-8", true) === false) {
                    $string = mb_convert_encoding($string, "UTF-8", 'ISO-8859-1');
                }

                $content .= $string;
            }
        }, [], null, false, true);

        if ($sanitize) {
            $content = preg_replace('/[^0-9\\p{L}\ ]|\W\W+/iu', '', $content);
        }

        file_put_contents($contentCacheFile, $content);

        return $content;
    }

    public function getWords($filter = NULL, $filterParams = array(), $bufferPage = NULL, $maxWordLength = 20, $readOnly = false) {

        if (!$filter && $this->words) return $this->words;

        $wordStack = array();

        $words = $this->each(function ($coords, $page, $index, $font, $raw, $handler, $filter, $filterParams, &$wordStack) use ($maxWordLength) {
            $x = $coords['x'];
            $y = $coords['y'];
            $originalX = $coords['originalX'];
            $originalY = $coords['originalY'];

            $btIndex = $coords['btIndex'];
            $coordsSrc = $coords['src'];
            $fontSize = $font['size'];
            $fontName = $font['name'];
            $map = $font['map'];
            $diffs = $font['diffs'];
            $chars = array();
            $results = array();
            $matchOffset = 0;

            // --- our regexp doesnt support double backslash ending, so prevent it
            $raw = str_replace('\\\\)', '\\\\ )', $raw);

            while(preg_match(pdfHandler::PATTERN_EXTRACT_TEXTS, $raw, $match, PREG_OFFSET_CAPTURE, $matchOffset)) {
                list($search, $capture) = $match;

                $matchOffset    = strlen($search[0]) + $search[1];
                $endWord        = false;
                $string         = $capture[0];

                // literal line breaks
                $string     = str_replace(array('\n', '\t', '\r'), array("\n", "\t", "\r"), $string);
                $len        = strlen($string);
                $string     = self::unescape($string);
                $pos        = $capture[1];

                if (count($map) || count($diffs)) {
                    $missings = [];

                    if (mb_detect_encoding($string, "UTF-8", true) === false) {
                        $strChars = str_split($string);
                    } else {
                        $strChars = preg_split('//u', $string);
                    }

                    foreach ($strChars as $i => $char) {
                        $dec = ord($char);

                        if (array_key_exists($dec, $diffs)) {
                            $strChars[$i] = mb_convert_encoding($diffs[$dec], 'UTF-8', 'ISO-8859-1');
                        }

                        if ($map) {
                            if (array_key_exists($dec, $map)) {
                                $strChars[$i] = $map[$dec];
                            } else {
                                if ($dec === 0) {
                                    unset($strChars[$i]);
                                }
                                $missings[$dec] = $char;
                            }
                        }
                    }

                    $string = implode('', $strChars);
                // maybe the raw data is in non-utf8 format
                } elseif (mb_detect_encoding($string, "UTF-8", true) === false) {
                    $string = mb_convert_encoding($string, "UTF-8", "ISO-8859-1");
                }

                // -- cualquier espacio es fin de palabra
                if ($string == " " || $string == "\n") {
                    $endWord = true;

                } elseif (strlen($string)) {
                    // octal chars
                    $string = preg_replace_callback ('/.*(\\\\[0-9][0-9][0-9]).*/', function ($match) {
                        $octal = substr($match[1], 1);

                        if ($char = chr(octdec($octal))) {
                            $new = str_replace($match[1], utf8_encode($char), $match[0]);

                            return $new;
                        }

                        return $match[0];
                    }, $string);

                    $chars[] = $string;

                    $intSeparator = 0;

                    // --- miramos que hay despues de nuestro char, si otro char o un espacio
                    $startPos = $pos + $len + 1;

                    if ($endPos = strpos($raw, '(', $startPos)) {
                        $separator = substr($raw, $startPos, $endPos - $startPos);

                        $intSeparator = (float) $separator * -1;
                    }

                    $endChars = array(' ', '.', ',');
                    $endsWithSpace = in_array(substr($string, -1), $endChars);

                    // --- fin de una palabra?
                    $endWord = $intSeparator > ($fontSize*3.5) || !$endPos || $endsWithSpace;
                } else {
                    $endWord = true;
                }


                if ($endWord) {
                    $word = trim(implode('', $chars));

                    if ($word || $word == 0) {

                        // Caso de que la fecha venga separada por espacios
                        $date = str_replace(' ', '', $word);
                        if (strlen($word) == 10 && is_numeric($date)) {
                            $word = $date;
                        }

                        $strings = preg_split("/[\s,\.\&]+/u", $word);

                        $loop = 0;
                        $numreal = count($strings);
                        while (($word = array_shift($strings)) !== NULL) {

                            if (empty($word) && $word !== 0 && $word !== "0") continue;


                            if ($loop < $numreal) {

                                $incWord = '';
                                foreach (array_reverse($wordStack) as $pWord) {
                                    $incWord    = $pWord . $incWord;


                                    $strings[]  = $incWord . $word;
                                }


                                // ---- montar fechas
                                if (is_numeric($word) && $word > 1900 && $word < 2999) {
                                    $numbers = array_values(array_filter($wordStack, 'is_numeric'));
                                    if (count($numbers) === 2) {
                                        list($days, $month) = $numbers;
                                        if (checkdate($month, $days, $word)) $strings[] = "$days-$month-$word";
                                    }

                                }


                                // --- guardar las ultimas variables.. (según cuantos chars llevemos almacenados)
                                if (count($wordStack)) {
                                    while ($totalChars = strlen(implode('', $wordStack)) > $maxWordLength) {
                                        array_shift($wordStack);
                                    }
                                }

                                $wordStack[] = $word;
                            }




                            $ltrims = array('¿', '(', '-');
                            foreach ($ltrims as $ltrim) $word = ltrim($word, $ltrim);

                            $rtrims = array('.', ',', '?', ')', ':', '-');
                            foreach ($rtrims as $rtrim) $word = rtrim($word, $rtrim);


                            $loop++;
                            $wordData = array(
                                'font' => $fontName,
                                'size' => $fontSize,
                                'raw' => $raw,
                                'height' => ($fontSize * $coords['start']),
                                'string' => $word,
                                'page' => $page,
                                'btIndex' => $btIndex,
                                'coords' => array(
                                    //'raw' => $coords,
                                    'src' => $coordsSrc,
                                    'x' => $x,
                                    'y' => $y,
                                    'originalX' => $originalX,
                                    'originalY' => $originalY
                                ),
                                'box' => $coords['box']
                            );

                            if (is_callable($filter)) {
                                $auxParams = $filterParams;
                                array_unshift($auxParams, $word, $wordData);


                                $filterResult = call_user_func_array($filter, $auxParams);

                                if ($filterResult) {
                                    // -- si nos devuelve algo que no sea bool entonces modificamos el word
                                    if (!is_bool($filterResult)) $word = $filterResult;
                                } else {
                                    continue;
                                }
                            }


                            $results[] = $wordData;

                        }


                    }

                    $chars = array();
                }
            }

            return $results;

        }, array($filter, $filterParams, &$wordStack), $bufferPage, false, $readOnly);

        if (!$filter) $this->words = $words;
        return $words;
    }



    public function each($callback, $callbackParams = array(), $bufferPage = NULL, $doTextStack = false, $readOnly = false) {
        $results = array();
        $map = array();
        $buffersCount = count($this->buffers);

        foreach ($this->buffers as $page => $buffer) {
            if (is_numeric($bufferPage) && $page != $bufferPage) {
                continue;
            }

            $differences    = $this->getDifferences($page);
            $fonts          = $this->getFonts($page);

            $x = $baseX = $left = 0;
            $y = $baseY = $top = 0;
            $textTop = $textLeft = 0;

            // --- iniciarlizar variables que no son necesarias, pero si un pdf estuviera mal formado tendríamos warnings
            $inverted = $rotated = $originStart = $originEnd = $btIndex = 0;
            $coordsSrc = 1;

            $lines = $this->bufferToArray($buffer, $readOnly);
            $stack = array();
            $textStack = array();
            $box = array();

            if (self::$debug) {
                $memory     = archivo::formatBytes(memory_get_usage());
                $progress   = round($page*100/$buffersCount, 2);
                print "buffer page {$page} - {$memory} - {$progress}%\n";
            }

            // initialize font vars
            $fontSize = 0;
            $fontName = '';
            $fontLine = null;

            foreach ($lines as $ln => $line) {
                $lineType = self::getLineType($line);
                if ($lineType === self::LINE_TYPE_UNKNOWN) {
                    continue;
                }

                $diffs = array();

                switch ($lineType) {
                    case self::LINE_TYPE_BOX:
                        $box[] = ['index' => $ln, 'raw' => $line];

                        break;
                    case self::LINE_TYPE_SAVE:
                        $stack[] = array($baseX, $baseY);
                        $box = array();

                        break;
                    case self::LINE_TYPE_REVERT:
                        list($baseX, $baseY) = array_pop($stack);

                        break;
                    case self::LINE_TYPE_START:
                        $btIndex = $ln; // --- index of this BT block
                        $top = $left = $x = $y = 0;
                        $textTop = $textLeft = 0;

                        break;
                    case self::LINE_TYPE_FONT:
                        // Fix #8221: Discard the beginning of the line when the it is like "/DeviceRGB cs 0 0 0 scn /DeviceRGB CS 1 0 0 SCN 2 J [] 0 d 0 j 20 w 10 M /d 160 Tf"
                        $pos = strrpos($line, "/");
                        $line = substr($line, $pos);

                        $fontLine = $line;
                        list($fontName, $fontSize) = array_values(array_filter(explode(' ', $line)));

                        // font definition is in two lines
                        if (is_numeric($fontName)) {
                            $prevLine = $lines[$ln-1];
                            if (strpos($prevLine, '/') !== 0) {
                                break;
                            }

                            $fontSize = $fontName;
                            $fontName = trim($prevLine);
                        }

                        $map = isset($fonts[$fontName]) ? $fonts[$fontName] : array();
                        $diffs = isset($differences[$fontName]) ? $differences[$fontName] : array();

                        break;
                    case self::LINE_TYPE_POSITION:
                        $posData = preg_split("/[\s]+/", $line);

                        list($a, $b, $c, $d, $e, $f) = array_map('floatval', $posData);

                        $originStart = $a;

                        // fix the text matrix, reverting the orientation
                        $inverted = $d * -1;

                        // en este moment $d es un multiplicador para estirar el texto, asique calculamos el fontsize real
                        if (isset($fontSize)) {
                            $fontSize = $fontSize * $d;
                        }

                        $baseX = $a * $baseX + $c * $baseY + $e;
                        $baseY = $b * $baseX + $d * $baseY + $f;
                        break;
                    case self::LINE_TYPE_COORDS:
                        $coords = $line;
                        // --- get coords in pdf
                        $coordsData = preg_split("/[\s]+/", $line);
                        list ($a, $b, $c, $d, $e, $f) = array_map('floatval', $coordsData);

                        $originStart = (float) $coordsData[0];
                        $originEnd = (float) $coordsData[1];

                        $rotated = (float) $coordsData[2];
                        $inverted = (float) $coordsData[3];
                        $coordsSrc = ($inverted > 0) ? 1 : -1;
                        $xIndex = ($rotated == -1) ? 5 : 4;
                        $yIndex = ($rotated == -1) ? 4 : 5;
                        $x = $coordsData[$xIndex];
                        $y = $coordsData[$yIndex];

                        // $x = $a * $x + $c * $y + $e;
                        // $y = $b * $x + $d * $y + $f;

                        break;
                    case self::LINE_TYPE_LEADING:
                        $pieces = explode(' ', $line);
                        if ($inverted >= 0) {
                            $top -= $pieces[0];
                        } else {
                            $top += $pieces[0];
                        }

                        break;
                    case self::LINE_TYPE_TXTCURSOR:
                        $pieces = explode(' ', $line);
                        $left -= $pieces[0];

                        break;
                    case self::LINE_TYPE_TXTPOSITION:
                        $pieces = preg_split("/[\s]+/", $line);

                        // make sure we get the info we need
                        $pieces = array_slice($pieces, -3, 3);

                        if (count($pieces) > 1) {
                            $textTop = (float) $textTop + (float) $pieces[1];
                            $textLeft = (float) $textLeft + (float) $pieces[0];
                        }

                        break;
                    case self::LINE_TYPE_OFFSET:
                        $relative = preg_split("/[\s]+/", $line);

                        // make sure we get the info we need
                        $relative = array_slice($relative, -3, 3);

                        $top = (float) $top + (float) $relative[1];
                        $left = (float) $left + (float) $relative[0];
                        break;
                    case self::LINE_TYPE_TEXT:
                        $nextLineType = isset($lines[$ln+1]) ? self::getLineType($lines[$ln+1]) : self::LINE_TYPE_UNKNOWN;
                        if ($doTextStack && $nextLineType === self::LINE_TYPE_TEXT) {
                            $textStack[] = $line;
                            continue;
                        }

                        $originalX = ($x + $left);
                        $xPos = $baseX + $originalX;

                        if ($inverted >= 0) {
                            if ($rotated == -1) {
                                $originalY = ($y - $top);
                            } else {
                                $originalY = ($y + $top);
                            }

                            $yPos = $baseY + $originalY;
                        } else {
                            $originalY = ($y - $top);
                            $yPos = $baseY + $originalY;
                        }

                        $xPos += $textLeft;
                        $yPos += $textTop;

                        if (count($textStack)) {
                            $line = implode("\n", $textStack) . "\n" . $line;
                            $textStack = array();
                        }

                        if ($originalY == 0) {
                            $originalY = $yPos;
                        }

                        if ($originalX == 0) {
                            $originalX = $xPos;
                        }

                        $params = array(
                            array(
                                'x' => $xPos,
                                'y' => $yPos,
                                'originalX' => $originalX,
                                'originalY' => $originalY,
                                'src' => $coordsSrc,
                                'rotated' => $rotated,
                                'inverted' => $inverted,
                                'start' => $originStart,
                                'end' => $originEnd,
                                'btIndex' => $btIndex,
                                'box' => $box,
                                'textX' => $textLeft,
                                'textY' => $textTop
                            ),
                            $page,
                            $ln,
                            array('size' => $fontSize, 'name' => $fontName, 'raw' => $fontLine, 'map' => $map, 'diffs' => $diffs),
                            $line,
                            $this
                        );

                        //$this->eachLines[] = $params; // cache data
                        $params = array_merge($params, $callbackParams);

                        try {
                            if ($result = call_user_func_array($callback, $params)) {
                                if (is_array($result)) {
                                    $results = array_merge($results, $result);
                                } else {
                                    $results[] = $result;
                                }
                            }
                        } catch (Exception $e) {
                            if ($e->getCode() === self::EXCEPTION_CODE_BREAK_LOOP) {
                                return $results;
                            }
                        }

                        break;
                }
            }
        }

        return $results;
    }


    // public function translate ($line, $map) {
    //  $pieces = array();
    //  $matchOffset = 0;

    //  // --- our regexp doesnt support double backslash ending, so prevent it
    //  $line = str_replace('\\)', '\\ )', $line);

    //  while(preg_match(pdfHandler::PATTERN_EXTRACT_TEXTS, $line, $match, PREG_OFFSET_CAPTURE, $matchOffset)) {
    //      list($search, $capture) = $match;

    //      $matchOffset = strlen($search[0]) + $search[1];


    //      $endWord = false;
    //      $string = $capture[0];

    //      if (count($map)) {
    //          $strChars = str_split($string);
    //          foreach ($strChars as $i => $char) {
    //              if (array_key_exists($char, $map)) {
    //                  $strChars[$i] = $map[$char];
    //              }
    //          }

    //          $string = implode('', $strChars);
    //      }

    //      $pieces[] = $string;
    //  }

    //  return implode(' ', $pieces);
    // }

    public function getDates($page = NULL) {
        if ($this->dates) return $this->dates;


        $dates = $this->getWords(function($str) {
            $value = str_replace('-', '', $str);

            if (strlen($value)!=8 || !is_numeric($value)) return false;

            $days = substr($value, 0, 2);
            $month = substr($value, 2 , 2);
            $year = substr($value, 4 , 4);

            if (is_numeric($days) && is_numeric($month) && is_numeric($year) && checkdate($month, $days, $year)) {
                return $word['string'] = $days."-".$month."-".$year;
            }

            return false;
        }, array(), $page, 10);


        $this->dates = $dates;
        return $dates;
    }

    public function getWordsWithNIF($specificVat = null)
    {
        if (null !== $specificVat) {
            $specificVat = self::normalizeVAT($specificVat);
        }

        $words = $this->getWords(function ($str) use ($specificVat) {
            $vat = self::normalizeVAT($str);
            $specificVatCondition = false;
            if (null !== $specificVat) {
                $specificVatCondition = $vat == $specificVat;
            }
            return vat::isValidSpainId($vat) || $specificVatCondition;
        }, [], null, 11);

        return new ArrayObjectList($words);
    }

    /**
     * Check if the given vats are inside the document and return them
     * @param  array $vats
     * @return array
     */
    public function vatsInDocument($vats)
    {
        $vats = array_map(function ($vat) {
            return str_pad(strtoupper(ltrim(trim($vat), "0")), 9, "0", STR_PAD_LEFT);
        }, $vats);

        $words = $this->getWords(function ($str) use ($vats) {
            $vat = self::normalizeVAT($str);

            return in_array($vat, $vats);
        }, [], null, 11);

        $vatsInDocument = array_map(function ($line) {
            return self::normalizeVAT($line['string']);
        }, $words);

        return array_unique($vatsInDocument);
    }

    public function getVats() {
        $text = $this->getText();
        $vats = vat::extractSpainVats($text);
        return $vats;
    }

    public static function getOcrAlternativeStrings($str) {
        $alternatives = [
            '0' => 'Q',
            '6' => 'G',
            '6' => 'C',
        ];

        $strings = [];

        foreach ($alternatives as $src => $alternative) {
            $offset = -1;
            while (($offset = strpos($str, (string) $src, $offset+1)) !== false) {
                $strings[] = substr_replace($str, $alternative, $offset, 1);
            }
        }

        return $strings;
    }

    /**
     * Get all the document vats using different ways
     * @return Array Vats list
     */
    public function getAllPossiblesVats()
    {
        $stringsNifs = $this->getNIFStrings();
        $stringsNifs = array_map('mb_strtoupper', $stringsNifs);

        $vats = $this->getVats();
        $vats = array_map('mb_strtoupper', $vats);

        // First merge $vats, because they can contain a CIF which can corresponse
        // with de enterpreneur code (usually at the begining of the document)
        $allPossibleVats = array_merge($vats, $stringsNifs);
        $allEquivalentVats = [];

        foreach ($allPossibleVats as $vat) {
            $allEquivalentVats = array_merge($allEquivalentVats, vat::getEquivalentVats($vat));
        }

        return array_unique($allEquivalentVats);
    }

    public function getNIFStrings ($forceOCR = false) {
        $vats = array();
        $strings = self::getPlainWords($this->original, null, true, $forceOCR);

        foreach ($strings as $str) {
            $vat = self::normalizeVAT($str);

            if (vat::isValidSpainId($vat)) {
                $vats[] = $vat;
            } elseif (true === $forceOCR && $alternatives = self::getOcrAlternativeStrings($vat)) {
                // test alternatives
                foreach ($alternatives as $vat) {
                    if (vat::isValidSpainId($vat)) {
                        $vats[] = $vat;
                        break;
                    }
                }
            }
        }

        if (count($vats) === 0 && $forceOCR === false) {
            return $this->getNIFStrings(true);
        }

        return $vats;
    }

    /**
     * Searchs a vat (cif/nif) in document and returns its position (false if it is not found)
     * @param  string  $vat
     * @param  boolean $ocr
     * @param  boolean $forceOCR
     * @param  boolean $forceConvert
     * @return int|boolean
     */
    public function searchVAT($vat, $ocr = false, $forceOCR = false, $forceConvert = false, $fuzzy = false)
    {
        $searchPosition = false;

        $text = self::extractText($this->original, $ocr, $forceOCR, $forceConvert);
        if ($searchPosition = stripos($text, $vat)) {
            return $searchPosition;
        }

        // standard replacement for OCR
        $fuzzyText = str_replace("0", "O", $text);
        $fuzzyVat = str_replace("0", "O", $vat);

        // optional replacement for OCR
        if ($fuzzy) {
            $fuzzyText = str_replace("8", "6", $fuzzyText);
            $fuzzyVat = str_replace("8", "6", $fuzzyVat);
        }

        if ($searchPosition = stripos($fuzzyText, $fuzzyVat)) {
            return $searchPosition;
        }

        if ($forceOCR === false) {
            return self::searchVAT($vat, $ocr, true);
        }

        if ($forceConvert === false) {
            return self::searchVAT($vat, $ocr, true, true, true);
        }

        return $searchPosition;
    }

    /**
     * Searchs a vat (cif/nif) in document and returns its position (false if it is not found)
     * @param  string  $vat
     * @return int|boolean
     */
    public function searchVatFirstPosition($vat)
    {
        $vatPosition = self::searchVAT($vat);

        $fuzzyTransformations = [
            '8' => 'B',
            'B' => '8',
        ];

        $firstVatDigit = substr($vat, 0, 1);
        $otherVatDigits = substr($vat, 1);

        if (true === in_array($firstVatDigit, array_keys($fuzzyTransformations))) {
            $ocrAlternativeVat = $fuzzyTransformations[$firstVatDigit] . $otherVatDigits;

            $ocrAlternativeVatPosition = self::searchVAT($ocrAlternativeVat);
            if (false !== $ocrAlternativeVatPosition && ($vatPosition === false || $ocrAlternativeVatPosition < $vatPosition)) {
                $vatPosition = $ocrAlternativeVatPosition;
            }
        }

        return $vatPosition;
    }

    public function getFirstCIF ($ocr = false, $search = false, $forceOCR = false, $forceConvert = false) {
        if ($search) {
            $text = self::extractText($this->original, $ocr, $forceOCR, $forceConvert);
            if (stristr($text, $search)) {
                return $search;
            }

            if (stristr(str_replace("0", "O", $text), str_replace("0", "O", $search))) {
                return $search;
            }

            $numbers = implode('', array_filter(str_split($search), 'is_numeric'));
            if (stristr($text, $numbers)) {
                return $search;
            }

            if ($forceOCR === false) {
                return self::getFirstCIF($ocr, $search, true);
            }

            if ($forceConvert === false) {
                return self::getFirstCIF($ocr, $search, true, true);
            }

            return false;
        }

        $possibleEnterpreneurCodes = $this->getPossibleEnterpreneurCodes();
        return $this->selectBestPossibleEnterpreneurCode($possibleEnterpreneurCodes);
    }

    /**
     * For "ITA", "TC2" and "Alta SS" documents, we need to extract the enterpreneur code,
     * which it can be (in Spain) a CIF or NIF/NIE (freelances).
     * This method get the values in document that are candidates to be the enterpreneur code.
     *
     * @return array
     */
    private function getPossibleEnterpreneurCodes()
    {
        $cif = self::getPlainWords($this->original, function ($str) {
            if (strlen($str) < 5) {
                return false;
            }

            $vat = pdfHandler::normalizeVAT($str);

            if (vat::isValidSpainVAT($vat) && !vat::isValidSpainId($vat)) {
                return $vat;
            }
        });

        if (false !== $cif) {
            return [$cif];
        }

        $possibleEnterpreneurCodes = [];

        if (count($vats = $this->getVats())) {
            $possibleEnterpreneurCodes[] = mb_strtoupper(reset($vats));
        }

        if (count($stringsNifs = $this->getNIFStrings())) {
            $possibleEnterpreneurCodes[] = mb_strtoupper(reset($stringsNifs));
        }

        if (count($cifs = $this->getWordsWithCIF())) {
            $cifs = array_map(function ($word) {
                return self::normalizeVAT($word['string']);
            }, $cifs->getArrayCopy());

            foreach ($cifs as $cif) {
                if (self::searchVatFirstPosition($cif)) {
                    $possibleEnterpreneurCodes[] = $cif;
                    break;
                }
            }
        }

        return $possibleEnterpreneurCodes;
    }

    /**
     * Select the best candidate for enterpreneur code (usually the first position in document)
     * @param  array $possibleEnterpreneurCodes
     * @return string|false
     */
    private function selectBestPossibleEnterpreneurCode($possibleEnterpreneurCodes)
    {
        if (0 === count($possibleEnterpreneurCodes)) {
            return false;
        }

        if (1 === count($possibleEnterpreneurCodes)) {
            return reset($possibleEnterpreneurCodes);
        }

        $firstEnterpreneurCode = false;
        $firstPosition = false;
        foreach ($possibleEnterpreneurCodes as $possibleEnterpreneurCode) {
            $position = self::searchVatFirstPosition($possibleEnterpreneurCode);

            if (false === $firstEnterpreneurCode) {
                $firstEnterpreneurCode = $possibleEnterpreneurCode;
                $firstPosition = $position;
                continue;
            }

            if (false === $position) {
                continue;
            }

            if (false === $firstPosition || $position < $firstPosition) {
                $firstEnterpreneurCode = $possibleEnterpreneurCode;
                $firstPosition = $position;
            }
        }

        return $firstEnterpreneurCode;
    }

    public function getCIFStrings () {
        $vats = array();
        $strings = self::getPlainWords($this->original);

        foreach ($strings as $str) {
            $vat = self::normalizeVAT($str);

            if (vat::isValidSpainVAT($vat) && !vat::isValidSpainId($vat)) {
                $vats[] = $vat;
            }
        }

        return $vats;
    }

    public function getWordsWithCIF() {
        if ($this->cifLines) return $this->cifLines;

        $words = $this->getWords(function($str) {
            $vat = self::normalizeVAT($str);

            return vat::isValidSpainVAT($vat) && !vat::isValidSpainId($vat);
        }, array(), NULL, 10);


        return $this->cifLines = new ArrayObjectList($words);
    }

    public static function getDateFromFile(
        $file,
        $date = self::SEARCH_METHOD_FIRST_TIME,
        $maxdiff = null,
        $periods = null,
        $ocr = false,
        $fpdi = false,
        $firstDateContextYear = null
    ) {
        try {
            $handler = new pdfHandler($file, false);
        } catch (Exception $e) {
            // do nothing..
        }

        return $handler->getFirstDate($ocr, $date, $maxdiff, $periods, false, $fpdi, $firstDateContextYear);
    }

    public static function isUnrecognizableText($text)
    {
        $text       = preg_replace('/Syntax\ Warning\:.+/', '', $text);
        $text       = preg_replace('/\s/', '', $text);
        $letters    = preg_replace('/[^a-z]/i', '', $text);

        // check if text contains at least 50 "letters", not other kind of chars
        if (strlen($letters) < 50) {
            return true;
        }

        $chars      = preg_split('//', $text);
        $total      = count($chars);

        // if more than the 2% of the all chars in the documet are $
        // looks like this file is encripted or malformed
        $repeats = substr_count($text, '$$$') * 3;
        $repeatsPercent = $repeats * 100 / $total;
        if ($repeatsPercent > 2) {
            return true;
        }

        $stranges   = 0;
        $whiteList  = [10, 11, 12];
        $blackList  = [146];

        foreach ($chars as $char) {
            $ord = ord($char);

            // decimal value of strange chars. see http://www.asciitable.com/
            if ($ord < 30 && false === in_array($ord, $whiteList)) {
                $stranges++;
                continue;
            }

            if ($ord > 176 || true === in_array($ord, $blackList)) {
                // decimal value of strange chars. see http://www.asciitable.com/
                $stranges++;
                continue;
            }
        }

        $strangePercent = $stranges * 100 / $total;
        if ($strangePercent > 5) {
            return true;
        }

        return false;
    }

    public static function cleanString($string)
    {
        $find = array("á","à","é","è","í","ì","ó","ò","ù","ú","Á","À","É","È","Í","Ì","Ó","Ò","Ù","Ú","Ñ","ñ");
        $replace = array("a","a","e","e","i","i","o","o","u","u","A","A","E","E","I","I","O","O","U","U","N","n");

        $string = str_ireplace($find, $replace, $string);

        return $string;
    }

    /**
     * Get the first date in the document
     * @param  boolean $ocr
     * @param  mixed $searchMethod
     * @param  mixed $maxdiff
     * @param  mixed $periods
     * @param  boolean $forceOCR
     * @param  boolean $fpdi
     * @param  int $firstDateContextYear The context year (usually the current year, but it can be different in tests)
     * @return mixed
     */
    public function getFirstDate(
        $ocr = false,
        $searchMethod = self::SEARCH_METHOD_FIRST_TIME,
        $maxdiff = null,
        $periods = null,
        $forceOCR = false,
        $fpdi = false,
        $firstDateContextYear = null,
        $returnContext = 'date'
    ) {
        if ($fpdi) {
            $handler = new pdfHandler($this->original);
            $text = $handler->getText(true);
        } else {
            $text = self::extractText($this->original, $ocr, $forceOCR, false, true);
        }

        // if we can't recognize text, try via OCR
        if (false === $forceOCR && true === $this->isUnrecognizableText($text)) {
            if ($fpdi === false) {
                if (php_sapi_name() === 'cli') {
                    error_log("text is unrecognizable, using FDI...");
                }
                return $this->getFirstDate($ocr, $searchMethod, $maxdiff, $periods, $forceOCR, true, $firstDateContextYear, $returnContext);
            }

            if (php_sapi_name() === 'cli') {
                error_log("text is unrecognizable, using OCR...");
            }
            return $this->getFirstDate($ocr, $searchMethod, $maxdiff, $periods, true, false, $firstDateContextYear, $returnContext);
        }

        $baseOffset = 0;
        $basePeriod = null;

        /**
         * When search method is an array, lets try to match every ocurrence before the date we want
         * then we use the SEARCH_METHOD_FIRST_DOCUMENT to get a proper date
         */
        $originalSearchMethod = $searchMethod;
        if (is_array($searchMethod)) {
            $prevOffset     = 0;
            foreach ($searchMethod as $occurrence) {
                $cleaned    = self::cleanString($occurrence);
                $versions   = array_unique([$occurrence, $cleaned]);

                $regex = '/('. implode('|', $versions) .')/ium';

                if (!preg_match($regex, $text, $matches, PREG_OFFSET_CAPTURE, $prevOffset)) {
                    // in some scenarios the regex can't match the ocurrence, so try via stripos
                    if (false !== $pos = stripos($text, $occurrence, $prevOffset)) {
                        $prevOffset = $pos;
                        continue;
                    }

                    if ($fpdi === false) {
                        return $this->getFirstDate($ocr, $searchMethod, $maxdiff, $periods, $forceOCR, true, $firstDateContextYear, $returnContext);
                    }

                    if ($forceOCR === false) {
                        return $this->getFirstDate($ocr, $searchMethod, $maxdiff, $periods, true, false, $firstDateContextYear, $returnContext);
                    }

                    return false;
                }

                list ($match, $offset) = $matches[0];
                if ($offset > $prevOffset) {
                    $prevOffset = $offset + strlen($match);
                }
            };

            // this will make use the next after offset
            $searchMethod = self::SEARCH_METHOD_FIRST_DOCUMENT;

            // set the new base offset
            $baseOffset = $prevOffset;
        }

        if ($ocr || $forceOCR) {
            // fix ocr issues
            $text = str_replace('—', '-', $text, $count);
            // Character '—' is multibyte (its lenght is 3) and we have to recalculate the base offset
            $baseOffset -= 2*$count;
        }

        $offset = $baseOffset;

        $dates = [];
        $dateStrings = [];

        $currentYear = (int) $firstDateContextYear;
        if (null === $firstDateContextYear) {
            $currentYear = (int) (new DateTime("now"))->format('Y');
        }

        // what kind of dates look for
        $searchForDates = $periods === null || $periods === false;
        $searchForPeriods = $periods === null || $periods === true;

        // when OCR is applied, sometimes we get O instead of 0
        $offset = $baseOffset;
        while (preg_match("/[\\s-\\/](O[\\ds])[\\s-\\/]/i", $text, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            list ($match, $offset) = $matches[1];
            $clean = str_ireplace('O', '0', $match);
            $text = substr_replace($text, $clean, $offset, 2);
            $offset = $offset += strlen($match);
        }

        // when OCR is applied, sometimes we get S instead of 5 (important to replace after zeros)
        $offset = $baseOffset;
        while (preg_match("/\\d(s)/i", $text, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            list ($match, $offset) = $matches[1];
            $clean = str_ireplace('S', '5', $match);
            $text = substr_replace($text, $clean, $offset, 1);
            $offset = $offset += strlen($match);
        }


        // sometimes we get dates with spaces betwen the numbers, we're cleaning that here
        $offset = $baseOffset;
        while (preg_match('/(20\d\s\d)/', $text, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            list ($match, $offset) = $matches[0];

            $clean = str_replace(' ', '', $match);
            $text = substr_replace($text, $clean, $offset, 5);
            $offset = $offset += strlen($match);
        }

        // match periods in wich we're going to use the fisrt day of it
        $offset = $baseOffset;
        $period = $basePeriod;
        while (preg_match('/(\d{2})\/(\d{4})\s?.\s?(\d{2})\/(\d{4})/', $text, $matches, PREG_OFFSET_CAPTURE, $offset) && $searchForPeriods) {
            list ($match, $month, $year, $endMonth, $endYear) = $matches;

            // --- set offset
            list ($match, $moffset) = $match;
            $offset = $moffset += strlen($match);

            // --- make sure we have a month
            list ($monthString, $monthOffset) = $month;
            list ($endMonthString, $yearOffset) = $endMonth;
            if ($monthString < 1 || $monthString > 12) {
                continue;
            }

            $period = $endMonthString - $monthString + 1;
            // the period must be positive
            if ($period < 1) {
                continue;
            }

            // --- make sure we have a day
            list ($yearString, $yearOffset) = $year;
            list ($endYearString, $yearOffset) = $endYear;
            $diff = abs($yearString - $currentYear);

            if ($yearString != $endYearString) {
                continue;
            }

            // Only return the date if it is nearly to current date (or context year in tests)
            if ($diff > 1) {
                continue;
            }

            $monthString = str_pad($monthString, 2, "0", STR_PAD_LEFT);

            $dateString = "{$yearString}-{$monthString}-01";
            $time = strtotime($dateString);

            $dateStrings[] = $dateString;
            $dates[] = array("01", $monthString, $yearString, $time, $moffset, $period);
        }

        // match periods in wich we're going to use the fisrt day of it
        $offset = $baseOffset;
        $period = $basePeriod;
        while (preg_match('/(\d{2})-(\d{2})\s(\d{4})/', $text, $matches, PREG_OFFSET_CAPTURE, $offset) && $searchForPeriods) {
            list ($match, $monthStart, $monthEnd, $year) = $matches;

            // --- set offset
            list ($match, $moffset) = $match;
            $offset = $moffset += strlen($match);

            // --- make sure we have a month
            list ($monthStart, $monthOffset) = $monthStart;
            if ($monthStart < 1 || $monthStart > 12) {
                continue;
            }

            list ($monthEnd, $monthOffset) = $monthEnd;
            $period = $monthEnd - $monthStart + 1;
            // the period must be positive
            if ($period < 1) {
                continue;
            }

            // --- make sure we have a year
            list ($yearString, $yearOffset) = $year;
            if ($yearString < 1970 || $yearString > 2100) {
                continue;
            }

            $monthStart = str_pad($monthStart, 2, "0", STR_PAD_LEFT);

            $dateString = "{$yearString}-{$monthStart}-01";
            $time = strtotime($dateString);

            $dateStrings[] = $dateString;
            $dates[] = array("01", $monthStart, $yearString, $time, $moffset, $period);
        }

        // prevent spaces between numbers
        $offset = $baseOffset;
        $period = $basePeriod;
        $preDateRegExp = "/[\\d\\ ]{3,4}[-][\\d\\ ]{3,4}[-]\\d{4,5}/";
        while (preg_match($preDateRegExp, $text, $matches, PREG_OFFSET_CAPTURE, $offset) && $searchForDates) {
            // --- set offset
            list ($match, $moffset) = $matches[0];

            $len = strlen($match);
            $offset = $moffset + $len;

            $date = str_replace(" ", "", $match);
            $replace = str_pad($date, $len, " ", STR_PAD_RIGHT);

            $text = substr_replace($text, $replace, $moffset, $len);
        }

        $offset = $baseOffset;
        $period = $basePeriod;
        $dateRegExp = '/(\d?\d)[^\d!\n]?(\d?\d)[^\d!\n]?(\d\d\d?\d?)/';
        while (preg_match($dateRegExp, $text, $matches, PREG_OFFSET_CAPTURE, $offset) && $searchForDates) {
            list ($match, $day, $month, $year) = $matches;

            // --- set offset
            list ($match, $moffset) = $match;
            $offset = $moffset += strlen($match);

            if ($month[1] === $day[1]+1 && $year[1] === $month[1]+1) {
                // this means we have a string like: ": 3 de enero de 1994.
                // and have match of: `1`, `9`, `94`
                continue;
            }

            // --- make sure we have a day
            list ($dayString, $dayOffset) = $day;
            if ($dayString < 1 || $dayString > 32) {
                continue;
            }

            // --- make sure we have a month
            list ($monthString, $monthOffset) = $month;
            if ($monthString < 1 || $monthString > 12) {
                continue;
            }

            // --- make sure we have a year
            list ($yearString, $yearOffset) = $year;

            if (strlen($yearString) == 2) {
                // to the future Jose
                // first three decades, we asume century XXI otherwise, XX
                $nextCentury = [0, 1, 2];
                $prefix = in_array($yearString[0], $nextCentury) ? "20" : "19";
                $yearString = "{$prefix}{$yearString}";
            }

            if ($yearString < 1970 || $yearString > 2100) {
                continue;
            }


            $monthString    = str_pad($monthString, 2, "0", STR_PAD_LEFT);
            $dayString      = str_pad($dayString, 2, "0", STR_PAD_LEFT);

            $dateString = "{$yearString}-{$monthString}-{$dayString}";
            $time = strtotime($dateString);

            $dateStrings[] = $dateString;
            $dates[] = array($dayString, $monthString, $yearString, $time, $moffset, $period);
        }

        $offset = $baseOffset;
        $period = $basePeriod;
        while (preg_match('/(\d?\d)\s*de\s*(\S+)\s*de\s*(\d{4})/', $text, $matches, PREG_OFFSET_CAPTURE, $offset) && $searchForDates) {
            list ($match, $day, $month, $year) = $matches;

            // --- set offset
            list ($match, $moffset) = $match;
            $offset = $moffset += strlen($match);

            $locales = ['es_ES', 'ca_ES'];

            foreach ($locales as $locale) {
                // save locale, parse string, reset locale
                $cLocale = setlocale(LC_TIME, 0);
                setlocale(LC_TIME, $locale);
                setlocale(LC_TIME, $locale . '.utf8');
                $match = ltrim($match, '0');
                $ptime = strptime($match, "%e de %B de %Y");
                setlocale(LC_TIME, $cLocale);

                if ($ptime) {
                    $year = 1900 + $ptime["tm_year"];
                    $month = $ptime["tm_mon"] + 1;
                    $day = $ptime["tm_mday"];

                    $month  = str_pad($month, 2, "0", STR_PAD_LEFT);
                    $day    = str_pad($day, 2, "0", STR_PAD_LEFT);

                    $dateString = "{$year}-{$month}-{$day}";
                    $dateStrings[] = $dateString;
                    $dates[] = array($day, $month, $year, strtotime($dateString), $moffset, $period);
                    break;
                }
            }
        }

        if (count($dates) && is_numeric($maxdiff)) {
            $dates = array_filter($dates, function ($date) use ($maxdiff) {
                // diff in seconds
                $diff = abs($date[3] - time());

                if ($diff > $maxdiff) return false;
                return $date;
            });
        }


        if (count($dates)) {
            switch ($searchMethod) {
                // -- we want the nearest date in time
                case self::SEARCH_METHOD_FIRST_TIME:

                    uasort($dates, function($a, $b) {
                        $atime = $a[3] - time();
                        $btime = $b[3] - time();

                        return $atime > $btime ? -1 : 1;
                    });
                    break;

                // --- first in document
                case self::SEARCH_METHOD_LAST_DOCUMENT:
                    $dates = array_reverse($dates);


                    break;

                // --- most repeated in document
                case self::SEARCH_METHOD_MOST_REPEATED:
                    $repeats = [];

                    // store repetitions
                    foreach ($dates as &$date) {
                        $timestamp = (int) $date[3];

                        if (empty($repeats[$timestamp])) {
                            $repeats[$timestamp] = 0;
                        }

                        $repeats[$timestamp]++;
                    }

                    $dates = array_reverse($dates);

                    // put the more repeated first
                    uasort($dates, function($a, $b) use ($repeats) {
                        $repeatsA = $repeats[$a[3]];
                        $repeatsB = $repeats[$b[3]];

                        if ($repeatsA === $repeatsB) {
                            return -1;
                        }

                        return $repeatsA < $repeatsB;
                    });

                    break;

                // --- first in document
                case self::SEARCH_METHOD_FIRST_DOCUMENT:
                    uasort($dates, function($a, $b) {
                        return $a[4] > $b[4];
                    });

                    break;

                // --- searching for a specific date
                default:
                    $time = strtotime(str_replace("/", "-", $searchMethod));
                    $compareDate = date("Y-m-d", $time);

                    if (in_array($compareDate, $dateStrings)) {
                        return $compareDate;
                    } else {
                        return false;
                    }
                break;
            }

            $date = reset($dates);

            if ('period' === $returnContext) {
                return $date[5];
            }

            return "{$date[0]}-{$date[1]}-{$date[2]}";
        }

        if ($fpdi === false) {
            return $this->getFirstDate($ocr, $originalSearchMethod, $maxdiff, $periods, $forceOCR, true, $firstDateContextYear, $returnContext);
        }

        if ($forceOCR === false) {
            return $this->getFirstDate($ocr, $originalSearchMethod, $maxdiff, $periods, true, false, $firstDateContextYear, $returnContext);
        }

        return false;
    }

    public function getDateFormated() {
        $strings = self::getPlainWords($this->original);

        foreach ($strings as $str) {
            $value = str_replace('-', '', $str);

            if (is_numeric($value) && strlen($value) == 7) $value = "0{$value}";
            if (strlen($value)<8 || !is_numeric($value)) continue;

            $days = substr($value, 0, 2);
            $month = substr($value, 2 , 2);
            $year = substr($value, 4 , 4);

            if (is_numeric($days) && is_numeric($month) && is_numeric($year) && $year > 1900 && checkdate($month, $days, $year)) {
                return $days."-".$month."-".$year;
            }
        }
    }


    public function hasWords($haystack, $process = true, $show = false) {
        $filter = function ($str, $line, &$haystack) use ($process) {
            $str        = trim(mb_strtolower($str, 'UTF-8'));
            $strings    = preg_split("/[\s\/\.\\\\]/u", $str);

            foreach ($strings as $str) {
                $test = array_combine($haystack, array_keys($haystack));
                if (isset($test[$str])) {
                    $i = $test[$str];
                    unset($haystack[$i]);

                    // use exception to stop the process
                    if (count($haystack) === 0) throw new Exception('no more words to match', pdfHandler::EXCEPTION_CODE_BREAK_LOOP);

                    return true;
                } elseif ($process == false) {
                    // do not check similarity when process needed

                    foreach ($haystack as $i => $word) {
                        $similarity = similar_text($str, $word, $sim);

                        if ($similarity > 95) {
                            unset($haystack[$i]);

                            // use exception to stop the process
                            if (count($haystack) === 0) throw new Exception('no more words to match', pdfHandler::EXCEPTION_CODE_BREAK_LOOP);
                        }

                    }

                    return true;
                }
            }

            return false;
        };

        if ($process) {
            // --- get minimun wordStack
            $maxLength = array_reduce($haystack, function ($a, $b) {
                return max([$a, strlen($b)]);
            });

            // --- filter function will empty $haystack if strings are found
            $this->getWords($filter, array(&$haystack), NULL, $maxLength, true);
        } else {
            $words = pdfHandler::getPlainWords($this->original, NULL, true);
            foreach ($words as $i => $word) {
                try {
                    $filter($word, $i, $haystack);
                } catch (Exception $e) {
                    break;
                }
            }

            // try forcing the ocr
            if (count($haystack)) {
                $words = pdfHandler::getPlainWords($this->original, NULL, true, true);

                foreach ($words as $i => $word) {
                    try {
                        $filter($word, $i, $haystack);
                    } catch (Exception $e) {
                        break;
                    }
                }
            }


        }

        if ($show && count($haystack)) {
            print_r($haystack);
        }

        return count($haystack) === 0;
    }

    public function getMinMaxFromLines($lines)
    {
        $firstLineStructure = $lines[0];
        $lastLineStructure = $lines[count($lines)-1];

        $docDirection = $firstLineStructure['coords']['src'];

        // sort by coords
        usort($lines, function ($a, $b) use ($docDirection) {
            if ($docDirection === 1) {
                return $a['coords']['y'] < $b['coords']['y'];
            } else {
                return $a['coords']['y'] > $b['coords']['y'];
            }
        });

        $firstLineDoc = $lines[0];
        $lastLineDoc = $lines[count($lines)-1];

        if ($firstLineStructure['coords']['y'] < $firstLineDoc['coords']['y']) {
            $firstLine = $firstLineStructure;
        } else {
            $firstLine = $firstLineDoc;
        }

        if ($lastLineStructure['coords']['y'] < $lastLineDoc['coords']['y']) {
            $lastLine = $lastLineStructure;
        } else {
            $lastLine = $lastLineDoc;
        }

        $max = (float) $firstLine['coords']['y'];
        $min = (float) $lastLine['coords']['y'];

        $margin = ($firstLine['height'] / 2) * $firstLine['coords']['src'];
        $max = $max + ($margin/2);

        $margin = ($lastLine['height'] / 2) * $lastLine['coords']['src'];
        $min = $min - $margin;

        return (object) ['max' => $max, 'min' => $min];
    }

    public function getVersionFromVAT($vat, $place = null)
    {
        $vat = self::normalizeVAT($vat);
        $cif = $this->getFirstCIF();
        $lines = $this->getWordsWithNIF($vat)->getArrayCopy();
        $filePath = uniqid().".pdf";

        foreach ($lines as $i => $line) {
            $nif = $line['string'];
            $nif = self::normalizeVAT($nif);
            $nif = trim(strtoupper($nif));

            // --- autónomos los saltamos por que figuran como CIF
            if ($cif == $nif) {
                unset($lines[$i]);
                continue;
            }

            $equivalentVats = vat::getEquivalentVats($nif);

            if (in_array($vat, $equivalentVats)) {
                //Para nuesto item añadimos la página, borramos las lineas y añadimos de nuevo la linea del item
                $pageIndex = $line['page'];
                $pageLines = array_values(array_filter($lines, create_function('$l', 'return $l["page"] == '. $pageIndex .';')));

                $maxMin = $this->getMinMaxFromLines($pageLines);
                $lineY = (float) $line['coords']['y'];

                $this->AddPage();
                $this->useTemplate($pageIndex);
                $this->clearLines($pageIndex, $maxMin->min, $maxMin->max, $lineY);
                break;
            }
        }

        if (!isset($pageIndex)) return false;

        $rawData = $this->getAsPDF($pageIndex);
        //Reseteamos el pdf
        $this->reset();

        if ($place === true) {
            return $rawData;
        } elseif ($place) {
            file_put_contents($place . '/' . basename($this->original), $rawData);
        } else {
            archivo::tmp($filePath, $rawData);
        }

        return $filePath;
    }


    public function getEmployees(Iusuario  $usuario, $checkCIF = true){
        if ($this->employees) return $this->employees;

        $items = new ArrayEmployeeList;
        $db = db::singleton();
        $userCompany =  $usuario->getCompany();
        $startIntList = $userCompany->getStartIntList();
        $corporacion = $userCompany->esCorporacion();
        $nifs = $this->getAllPossiblesVats();


        if ($checkCIF) {
            if (0 === count($nifs)) {
                return $items;
            }

            if (!$cif = $this->getFirstCIF()) {
                return $items;
            }

            $cif = mb_strtoupper($cif);

            // check if company VAT is also an employee VAT
            $match = array_search($cif, $nifs);
            if (false !== $match) {
                unset($nifs[$match]);
            }

            if (0 === count($nifs)) {
                return $items;
            }

            foreach ($nifs as &$nif) {
                $nif = "'{$nif}'";
            }

            $list = implode(',', $nifs);

            $SQL = "SELECT uid_empleado FROM ". TABLE_EMPLEADO . "
                INNER JOIN ". TABLE_EMPLEADO ."_empresa USING (uid_empleado)
                INNER JOIN ". TABLE_EMPRESA ." USING (uid_empresa)
                WHERE dni IN ({$list})
                AND uid_empresa IN ({$startIntList})
                AND cif LIKE '%{$cif}%'
                AND papelera = 0
            ";
        } else {
            $nifs = array_map(function($nif) {
                return '"'. $nif . '"';
            }, $nifs);

            if (!count($nifs)) {
                return $items;
            }

            $nifs = array_unique($nifs);
            $list = implode(',', $nifs);

            $SQL = "SELECT uid_empleado FROM ". TABLE_EMPLEADO . "
                INNER JOIN ". TABLE_EMPLEADO ."_empresa USING (uid_empleado)
                INNER JOIN ". TABLE_EMPRESA ." USING (uid_empresa)
                WHERE dni IN ({$list})
                AND uid_empresa IN ({$startIntList})
                AND papelera = 0
            ";
        }

        if (!$items = $db->query($SQL, "*", 0, 'empleado')) {
            return new ArrayEmployeeList;
        }

        if (is_array($items)) {
            $items = new ArrayEmployeeList($items);
        }

        $this->employees = $items;
        return $items;
    }



    public function bufferToArray($buffer, $readOnly = false)
    {
        if (false === strpos($buffer, '(') && false === strpos($buffer, '[')) {
            return [];
        }

        if ($readOnly) {
            $buffer = preg_replace("/.+ (cm|TL|Td|Tm|rg|re|c|y|v|m|l|h|n|g)\\v/", '', $buffer);
            $buffer = preg_replace("/\\v(ET|BT|W\\*|Q|q|h|f|n)/", '', $buffer);
        }

        // Fix #8206: Create new buffer line when the line is like "1 0 0 -1 2140 6594 Tm(.)Tj"
        $buffer = preg_replace("/(Tm\()/", "Tm\n(", $buffer);
        // Fix #8221: Create new buffer line when the line is like "3901 16090 TD[(!"#$%&'\(\)\(*+&\($#\),"\)-.$/$0$,*."1)]TJ"
        $buffer = preg_replace("/(TD\[)/", "TD\n[", $buffer);

        return preg_split("/[\n]/", $buffer);
    }


    public static function gs ($file) {
        $tmpfilename = md5_file($file) . ".gs.pdf";
        $tmpfile = "/tmp/{$tmpfilename}";

        // alreasdy gs'ed?
        if (file_exists($tmpfile) && is_readable($tmpfile)) {
            if (self::$debug) {
                echo "The file {$file} is already gs'ed on this disk!\n";
            }

            return $tmpfile;
        }


        $params = [];

        // default pdf settings
        $params[] = "-sDEVICE=pdfwrite";
        $params[] = "-dCompatibilityLevel=1.4";
        $params[] = "-dPDFSETTINGS=/screen";
        //$params[] = "-dPDFSETTINGS=/prepress";
        $params[] = "-dSAFER=true";

        // image settings
        $params[] = "-dEncodeColorImages=false";
        $params[] = "-dEncodeGrayImages=false";
        $params[] = "-dEncodeMonoImages=false";

        $params[] = "-dDownsampleColorImages=false";
        $params[] = "-dDownsampleGrayImages=false";
        $params[] = "-dDownsampleMonoImages=false";

        // font settings
        // $params[] = "-I /usr/share/fonts/truetype/msttcorefonts";
        // $params[] = "-c '/Arial findfont pop [/Arial /Font resourcestatus]'";
        $params[] = "-dCompressFonts=false";
        $params[] = "-dEmbedAllFonts=true";
        // $params[] = "-dSubsetFonts=false";
        //$params[] = "-c \"setpdfwrite <</NeverEmbed [ ]>> setdistillerparams\"";

        // exec settings
        $params[] = "-dNOPAUSE";
        $params[] = "-dQUIET";
        $params[] = "-dBATCH";

        // file params
        $params[] = "-sOutputFile={$tmpfile}";
        $params[] = "-f {$file}";


        $params = implode(' ', $params);
        $cmd = "gs {$params} ";
        if (self::$debug === false) {
            $cmd .= " 2>&1";
        }

        list($out, $code) = self::runCommand($cmd);

        if ($code === 0) {
            $size = filesize($tmpfile);
            if (self::$debug) echo archivo::formatBytes(memory_get_usage()) . " gs \n"; // 36640

            // if ($size > self::MAX_FILE_SIZE) {
            //  throw new Exception("file {$tmpfilename} size {$size} > " . self::MAX_FILE_SIZE);
            // }

            return $tmpfile;
        }

        throw new Exception("error using gs [$code]: " . implode("\n", $out));
    }


    private function normalizeBuffer ($buffer, $page) {
        $identifier = md5($this->file) . '-' . $page . '-pdfbuffer.txt';
        $bufferCacheFile = "/tmp/{$identifier}";

        if (is_readable($bufferCacheFile) && $cachedBuffer = file_get_contents($bufferCacheFile)) {
            return $cachedBuffer;
        }

        $hasWords = false;


        $stream = fopen('php://memory','wr+');
        fwrite($stream, $buffer);
        rewind($stream);


        while (!feof($stream)) {
            $piece = fgets($stream);
            $type = self::getLineType($piece);

            $loop   = 0;

            if ($type == self::LINE_TYPE_TEXT) {

                $offset = 0;
                $break  = false;

                while(preg_match(pdfHandler::PATTERN_EXTRACT_TEXTS, $piece, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                    @list($string, $offset) = @$matches[1];

                    if ($string) {
                        $string = str_replace(array('\n', '\t', '\r'), array("\n", "\t", "\r"), $string);
                        $string = trim(self::unescape($string));
                        if (strlen($string) > 1) {
                            $break = $hasWords = true;
                            break;
                        }
                    }

                    $offset++; // move cursor
                    $loop++;
                }

                unset($matches);
            }

            if ($loop > 1) {
                if (isset($break)) unset($break);
            }

            if (isset($break) && $break) break;
        }

        fclose($stream);



        // If we have no words, but we found text blocks
        if ($hasWords === false && isset($break)) {
            throw new InvalidPdfTextsException;
        }


        if (self::$debug) echo archivo::formatBytes(memory_get_usage()) . " replace \n";
        // more than 10 MB of buffer, cant do the str_replace
        // if (strlen($buffer) > (1024*1024*10)) {
        //
        //  throw new InvalidPdfTextsException;
        // }

        // $stream = fopen('php://memory','wr+');
        // fwrite($stream, $buffer);
        // rewind($stream);




        $commands = array("BT", "ET", "Q", "q");
        $buffer = str_replace("\r", "\n", $buffer);

        foreach($commands as $cmd) {
            $buffer = str_replace(" {$cmd} ", "\n{$cmd} ", $buffer);
            $buffer = str_replace("{$cmd} ", "{$cmd}\n", $buffer);
        }

        $commands = array("cm", "Tm", "Tf", "Td", "TD", "Tc", "Tj", "TJ");
        foreach($commands as $cmd) {
            $buffer = str_replace("\n{$cmd}", " {$cmd}", $buffer);
            $buffer = str_replace("{$cmd} ", "{$cmd}\n", $buffer);
        }

        file_put_contents($bufferCacheFile, $buffer);
        // $stream = fopen('php://memory','wr+');
        // fwrite($stream, $buffer);
        // rewind($stream);

        return $buffer;
    }

    /***
       *    Intenta eliminar los residuos de una cadena que contiene un NIF
       *
       *
       */
    public static function normalizeVAT ($str) {
        $vat = strtoupper(ltrim($str, "0"));
        $l = strlen($vat);



        $chars = array_filter(str_split($str), function ($char) { return is_numeric($char) === false; });
        if (count($chars) > 3) return false;


        if ($l < 6) return false;

        // edge case, we get something like this: 972400000A50001726EM or like this 900000000B5074104000
        if (($pos = strpos($vat, '00000')) !== false && $vat[0] == "9") {
            $vat        = ltrim(substr($vat, $pos+5), 0);
            $letters    = substr($vat, -2);
            $vat        = substr($vat, 0, 9);
        }


        // remove left zeros
        $vat = str_pad($vat, 9, "0", STR_PAD_LEFT);

        $nieStart = ['X', 'Y', 'Z'];
        if (strlen($vat) === 10 && in_array($vat[0], $nieStart)) {
            // a spanish residence ID
        } elseif (strlen($vat) > 9) {
            $vat = substr($vat, -9);
        }

        return $vat;
    }

    /***
       *    Eliminar las contrabarras de los bloques de texto
       *
       *
       */
    private static function unescape ($string) {
        $escaped = array('\\(', '\\)', '\\\\');
        $unescaped = array('(' , ')', '\\');
        return str_replace($escaped, $unescaped, $string);
    }

    private static function getLineType ($line) {
        if (!$line) {
            return self::LINE_TYPE_UNKNOWN;
        }

        $lastTwo    = substr($line, -2);
        $charOne    = substr($line, 0, 1);
        $firstTwo   = substr($line, 0, 2);
        $len        = strlen($line);
        $textLines  = $lastTwo === 'Tj' || $lastTwo === 'TJ' || $charOne == "(" || $charOne == "[";

        if (strpos($line, '(') !== false && strpos($line, ')') !== false) {
            $textLines = true;
        }

        if ($textLines)  {
            if (strpos($line, '(') === false) {
                return self::LINE_TYPE_UNKNOWN;
            }

            // Lineas que contienen texto
            return self::LINE_TYPE_TEXT;
        }

        if ($firstTwo === 'BT') {
            // Inicio de un bloque de texto
            return self::LINE_TYPE_START;

        } elseif ($lastTwo === 'Tf') {
            // Informacion sobre las fuentes
            return self::LINE_TYPE_FONT;

        } elseif ($lastTwo === 'TL') {
            // De: TextLeading
            return self::LINE_TYPE_LEADING;

        } elseif ($lastTwo === 'Tm') {
            // Lineas terminadas en Tm => define la posicion en el documento
            return self::LINE_TYPE_COORDS;

        } elseif ($lastTwo === 'Tc') {
            // Lineas terminadas en Tm => define la posicion en el documento
            return self::LINE_TYPE_TXTCURSOR;

        } elseif ($lastTwo === 'TD') {
            // Lineas terminadas en TD => define la posicion en el documento
            return self::LINE_TYPE_TXTPOSITION;

        } elseif ($lastTwo == 'Td') {
            // Lineas terminadas en Td => define la posicion relativa en el bloque de texto
            return self::LINE_TYPE_OFFSET;

        } elseif ($lastTwo === 'cm') {
            // Lineas que contienen texto
            return self::LINE_TYPE_POSITION;


        } elseif ($charOne === 'q' && $len == 1) {
            // Guardar un estado
            return self::LINE_TYPE_SAVE;

        } elseif ($charOne === 'Q' && $len == 1) {
            // Guardar un estado
            return self::LINE_TYPE_REVERT;

        } elseif ($lastTwo === ' l' || $lastTwo === ' m') {

            return self::LINE_TYPE_BOX;
        }

        return self::LINE_TYPE_UNKNOWN;
    }
}
