<?php
putenv('~=/var/www/'); // intenta solucionar problemas con AWS

class archivo
{
    const LONGITUD_MAXIMA_EXTENSION = 5;
    const BUCKET_FILES = "dokify-files";
    const BUCKET_FILES_TMP = "dokify-tmp-files";
    const BUCKET_PUBLIC = 'dokify-public';

    const BUCKET_FILES_KEY      = 'aws.s3_bucket_files';
    const BUCKET_FILES_TMP_KEY  = 'aws.s3_bucket_tmp';
    const BUCKET_PUBLIC_KEY     = 'aws.s3_bucket_public';

    const PUBLIC_ROUTE_SSL = 'dokify.public_ssl';
    const PUBLIC_ROUTE = 'dokify.public';

    const AWS_KEY_ACCESS = "aws.s3_access";
    const AWS_KEY_SECRET = "aws.s3_secret";
    const MULTIPART_PIECE = 10;

    const PUBLIC_FILES_CDN = 'd1is4mys2klnrz.cloudfront.net';
    const PUBLIC_FILES_CNAME = 'public.dokify.net';

    const PHOTO_HEIGHT_LIMIT_PX = 350;

    protected $realfilename;
    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Returns the bucket name, reading the ini files if necessary
     * @param  string $key any of the following [FILES|FILES_TMP|PUBLIC]
     * @throws Exception If $key is not a valid constant
     * @return string the bucket name
     */
    public static function getBucketName($key)
    {
        $iniConst   = 'BUCKET_' . $key . '_KEY';
        $class      = __CLASS__;

        if (false === defined("{$class}::{$iniConst}")) {
            throw new Exception("Invalid key name {$key} for S3 bucket name");
        }

        $iniKey = constant("{$class}::{$iniConst}");

        // returns the default value if no ini is specified
        if (false === $bucket = get_cfg_var($iniKey)) {
            $default = 'BUCKET_' . $key;
            return constant("{$class}::{$default}");
        }

        return trim($bucket);
    }

    public static function getS3Route($ssl = true)
    {
        if ($ssl) {
            return get_cfg_var(self::PUBLIC_ROUTE_SSL);
        } else {
            return get_cfg_var(self::PUBLIC_ROUTE);
        }
    }

    public function download($filename = false)
    {
        self::output($this->path, $filename);
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getRealfilename()
    {
        return $this->realfilename;
    }

    public function setRealfilename($name)
    {
        return $this->realfilename = $name;
    }

    public static function getSupportedFormats()
    {
        return array(
                'pdf',
                'doc',
                'docx',
                'png',
                'jpg',
                'jpeg',
                'tiff'
            );
    }

    public static function getLocalVersion($tmpName)
    {
        $path = "/tmp/{$tmpName}";

        if (file_exists($path) && is_readable($path) && CURRENT_ENV !== 'dev') {
            return $path;
        }

        // If cant read the file from other sources
        if (!$fileData = archivo::tmp($tmpName)) {
            return false;
        }

        // write locally
        if (file_put_contents($path, $fileData)) {
            return $path;
        }

        return false;
    }

    public static function tmp($filename, $data = null, $delete = false, $intents = 0)
    {
        $path = "/tmp/$filename";
        $inMachine = file_exists($path) && is_readable($path);

        if (CURRENT_ENV === 'dev') {
            $inMachine = false;
        }

        if (($amazonS3 = self::getS3()) && (!$inMachine || $data || $delete)) {
            try {
                if ($data) {
                    $response = $amazonS3->create_object(self::getBucketName('FILES_TMP'), $filename, array('body' => $data));
                    if (!file_put_contents($path, $data)) {
                        error_log("Error: Copiar fichero a la ruta: ".$path);
                    }
                    return (bool) $response->isOK();
                } else {
                    // Si se indica valor truthy, esque vamos a eliminar el fichero
                    if ($delete) {
                        // Si $delete es un string, entonces vamos a copiarlo en otro destino y despues a eliminarlo
                        if (is_string($delete)) {
                            $response = $amazonS3->copy_object(
                                array('bucket' => self::getBucketName('FILES_TMP'),   'filename' => $filename ), // Source
                                array('bucket' => self::getBucketName('FILES'),       'filename' => $delete ) // Destination
                            );

                            if (!$response->isOK()) {
                                return false;
                            }
                        }

                        $response = $amazonS3->delete_object(self::getBucketName('FILES_TMP'), $filename);
                        return (bool) $response->isOK();
                    }

                    $response = self::getObjectResponse($amazonS3, $filename, 'FILES_TMP');

                    if ($response->isOK()) {
                        return $response->body;
                    } else {
                        $trace = implode("\n", trace(true));
                        error_log("error_leer_s3 [". self::getBucketName('FILES_TMP') ."] [$filename] code: {$response->status}\n{$trace}");
                        return false;
                    }
                }
            } catch (cURL_Exception $e) {
                sleep(1);

                if ($intents < 2) {
                    return self::tmp($filename, $data, $delete, ($intents+1));
                }

                error_log("Error! he intentado $intents veces el metodo ". __FUNCTION__ ." y no ha funcionado");
            }
        } else {
            if ($data) {
                return file_put_contents($path, $data);
            } else {
                if ($delete) {
                    if (is_string($delete)) {
                        if (!copy($path, $delete)) {
                            return false;
                        }
                    }

                    return unlink($path);
                }
                return file_get_contents($path);
            }
        }
    }

    public static function copy($source, $target, $intents = 0)
    {
        if ($amazonS3 = self::getS3()) {
            try {

                $response = $amazonS3->copy_object(
                    array('bucket' => self::getBucketName('FILES_TMP'),   'filename' => $source ), // Source
                    array('bucket' => self::getBucketName('FILES'),       'filename' => $target ) // Destination
                );

                if (!$response->isOK()) {
                    return false;
                }

                return true;

            } catch (cURL_Exception $e) {
                usleep(500000); // half-second
                if ($intents < 3) {
                    return self::copy($source, $target, ($intents+1));
                }

                error_log("Error! he intentado $intents veces el metodo ". __FUNCTION__ ." y no ha funcionado");
            }
        } else {
            if (!copy($source, $target)) {
                return false;
            }

            return true;
        }
    }

    public static function uploadPiecesToS3($filename, $path, $callback = false)
    {
        if ($amazonS3 = self::getS3()) {
            $path = str_replace(DIR_FILES, '', $path);
            $size = filesize($filename);

            // always remove first backslash
            if (strpos($path, '/') === 0) {
                $path = substr($path, 1);
            }

            // Initiate a new multipart upload
            $response = $amazonS3->initiate_multipart_upload(self::getBucketName('FILES'), $path);
            $uploadId = (string) $response->body->UploadId;

            $parts = $amazonS3->get_multipart_counts($size, self::MULTIPART_PIECE*1024*1024);

            // Queue batch requests
            foreach ($parts as $i => $part) {
                $response = null;

                do {
                    try {
                        $response = $amazonS3->upload_part(self::getBucketName('FILES'), $path, $uploadId, array(
                            'expect'     => '100-continue',
                            'fileUpload' => $filename,
                            'partNumber' => ($i + 1),
                            'seekTo'     => (integer) $part['seekTo'],
                            'length'     => (integer) $part['length'],
                        ));
                    } catch (cURL_Exception $e) {
                        usleep(500000); // half-second
                    }
                } while (!$response);

                if ($response->isOk()) {
                    if (is_callable($callback)) {
                        call_user_func($callback, $response, $i, $part, $parts);
                    }
                } else {
                    return false;
                }
            }

            $parts      = $amazonS3->list_parts(self::getBucketName('FILES'), $path, $uploadId);
            $response   = $amazonS3->complete_multipart_upload(self::getBucketName('FILES'), $path, $uploadId, $parts);

            return (bool) $response->isOK();
        }

        return false;
    }

    public function getUploadedFile ($inputName = 'file', $maxBytes = null)
    {
        $method = trim($_SERVER["REQUEST_METHOD"]);

        $path = false;
        $name = false;

        if ($method === "PUT") {
            if (isset($_SERVER['HTTP_X_FILE_NAME']) && !($name = trim($_SERVER['HTTP_X_FILE_NAME']))) {
                throw new Exception("Error reading filename", 500);
            }

            // --- we can determine the size before read
            if ($maxBytes && isset($_SERVER['HTTP_X_FILE_SIZE']) && ($size = $_SERVER['HTTP_X_FILE_SIZE'])) {
                if ($size > $maxBytes) {
                    throw new Exception("Request Entity Too Large", 413);
                }
            }

            $name   = urldecode($name);
            $ext    = archivo::getExtension($name);
            $path   = '/tmp/upload-'.uniqid().".".$ext;
            $put    = fopen('php://input', 'r');
            $write  = fopen($path, 'w');

            while ($data = fread($put, 1024)) {
                fwrite($write, $data);
            }

        } elseif (isset($_FILES[$inputName])) {
            $name   = $_FILES[$inputName]["name"];
            $ext    = archivo::getExtension($name);
            $tmp    = $_FILES[$inputName]["tmp_name"];
            $path =  "{$tmp}.{$ext}";

            if (move_uploaded_file($tmp, $path) === false) {
                throw new Exception("Error moving filename", 500);
            }

            $bodySize = $_SERVER['CONTENT_LENGTH'];

            if ($maxBytes && $bodySize > $maxBytes) {
                throw new Exception("Request Entity Too Large", 413);
            }
        } else {
            throw new Exception('no file found', 500);
        }


        if (!is_readable($path)) {
            throw new Exception('Bad Request', 400);
        }

        if ($maxBytes && !isset($size) && $size = filesize($path)) {
            if ($size > $maxBytes) {
                throw new Exception("Request Entity Too Large", 413);
            }
        }

        return (object) array(
            'path' => $path,
            'name' => $name,
            'ext'  => $ext
        );
    }

    public function getPublicLink ($filename, $preserveName = true, $ssl = false)
    {
        $prefix = md5($filename) . '-' . time();

        if ($preserveName) {
            $name = ($preserveName === true) ? $filename : $preserveName;
            $ext = self::getExtension($name);
            $path = $prefix . '/' . basename($name);
            $link = $prefix . '/' . rawurlencode(basename($name));
        } else {
            $ext = self::getExtension($filename);
            $path = $link = $prefix . '.' . $ext;
        }

        // Si no hay S3 damos error, pero simulamos un link accesible
        if (!self::getS3()) {
            error_log("Para crear links publics es necesario estar conectado a Amazon S3");

            $aux = DIR_ROOT . 'files';

            if (!is_dir($aux)) {
                mkdir($aux);
            }

            $target = $aux . "/" . $path;
            if (copy($filename, $target)) {
                $localPublic = CURRENT_DOMAIN . '/files/' . $path;
                return $localPublic;
            }


            return false;
        }

        $bucket = self::getBucketName('PUBLIC');
        if (self::uploadToS3($filename, $path, $bucket)) {
            if ($ssl) {
                return 'https://' . self::getS3Route(true) . '/' . $link;
            } else {
                return 'http://' . self::getS3Route(false) . '/' . $link;
            }
        }

        return false;
    }

    public static function uploadToS3($filename, $path, $bucket = null, $extraData = array(), $intents = 0)
    {
        if (!$bucket) {
            $bucket = self::getBucketName('FILES');
        }

        if ($amazonS3 = self::getS3()) {
            $path = str_replace(DIR_FILES, '', $path);
            try {
                $data = count($extraData) ? $extraData : array();
                $data['fileUpload'] = $filename;


                // --- for PDFs we need to set the right content type (chrome default pdf viewer fails if not)
                $ext = self::getExtension($path);
                if ($ext === 'pdf') {
                    $headers = isset($data['headers']) ? $data['headers'] : array();

                    if (!isset($headers['Content-Type'])) {
                        $headers['Content-Type'] = 'application/pdf';
                        $data['headers'] = $headers;
                    }
                }

                // always remove first backslash
                if (strpos($path, '/') === 0) {
                    $path = substr($path, 1);
                }

                $response = self::createObjectResponse($amazonS3, $path, $bucket, $data);

                return (bool) $response->isOK();
            } catch (cURL_Exception $e) {
                usleep(500000); // half-second
                if ($intents < 3) {
                    return self::uploadToS3($filename, $path, $bucket, $extraData, ($intents+1));
                }

                error_log("Error! he intentado $intents veces el metodo ". __FUNCTION__ ." y no ha funcionado");
                return false;
            }
        }
    }

    public static function downloadFromS3($path, $filename, $intents = 0)
    {
        if ($amazonS3 = self::getS3()) {
            $path = str_replace(DIR_FILES, '', $path);
            try {
                $response = self::getObjectResponse($amazonS3, $path, 'FILES');

                if ($response->isOK()) {
                    file_put_contents($filename, $response->body);
                    return true;
                }

                return false;
            } catch (cURL_Exception $e) {
                usleep(500000); // half-second
                if ($intents < 3) {
                    return self::downloadFromS3($path, $filename, ($intents+1));
                }

                error_log("Error! he intentado $intents veces el metodo ". __FUNCTION__ ." y no ha funcionado");
                return false;
            }
        }

        return false;
    }

    public static function filesize($filename, $intents = 0)
    {
        if (strstr($filename, DIR_FILES) && $amazonS3 = self::getS3()) {
            $path = str_replace(DIR_FILES, '', $filename);
            try {
                return $amazonS3->get_object_filesize(self::getBucketName('FILES'), $path);
            } catch (cURL_Exception $e) {
                sleep(1);
                if ($intents < 2) {
                    return self::filesize($filename, ($intents+1));
                }

                error_log("Error! he intentado $intents veces el metodo ". __FUNCTION__ ." y no ha funcionado");
                return false;
            }
        } else {
            return is_readable($filename);
        }
    }

    public static function tmpFileSize($filename, $intents = 0)
    {
        if ($amazonS3 = self::getS3()) {
            try {
                return $amazonS3->get_object_filesize(self::getBucketName('FILES_TMP'), $filename);
            } catch (cURL_Exception $e) {
                sleep(1);
                if ($intents < 2) {
                    return self::tmpFileSize($filename, ($intents+1));
                }

                error_log("Error! he intentado $intents veces el metodo ". __FUNCTION__ ." y no ha funcionado");
                return false;
            }
        } else {
            $local = "/tmp/{$filename}";
            if (false === is_readable($local)) {
                return false;
            }

            return filesize($local);
        }
    }

    public static function getTemporaryPublicURL($filename, $downloadName, $timeExpired = '3 hours', $intents = 0)
    {
        if ($amazonS3 = self::getS3()) {
            $path = str_replace(DIR_FILES, '', $filename);

            // always remove first backslash
            if (strpos($path, '/') === 0) {
                $path = substr($path, 1);
            }

            try {
                $downloadName = self::cleanFilenameString($downloadName);

                return $amazonS3->get_object_url(self::getBucketName('FILES'), $path, $timeExpired, array(
                    'response' => array(
                        'content-disposition' => 'attachment; filename="'.$downloadName.'"',
                    ),
                    'https' => true
                ));
            } catch (cURL_Exception $e) {
                usleep(500000); // half-second
                if ($intents < 3) {
                    return self::getTemporaryPublicURL($filename, $downloadName, $timeExpired, ($intents+1));
                }

                error_log("Error! he intentado $intents veces el metodo ". __FUNCTION__ ." y no ha funcionado");
                return false;
            }
        }

        return false;
    }

    public static function unlink($filename, $intents = 0)
    {
        if (strstr($filename, DIR_FILES) && $amazonS3 = self::getS3()) {
            $path = str_replace(DIR_FILES, '', $filename);
            try {
                $response = $amazonS3->delete_object(self::getBucketName('FILES'), $path);
                return (bool) $response->isOK();
            } catch (cURL_Exception $e) {
                usleep(500000); // half-second
                if ($intents < 3) {
                    return self::unlink($filename, ($intents+1));
                }

                error_log("Error! he intentado $intents veces el metodo ". __FUNCTION__ ." y no ha funcionado");
                return false;
            }
        } else {
            return unlink($filename);
        }
    }

    public static function filectime($filename, $intents = 0)
    {
        if (strstr($filename, DIR_FILES) && $amazonS3 = self::getS3()) {
            $path = str_replace(DIR_FILES, '', $filename);
            try {
                $response = self::getObjectResponse($amazonS3, $path, 'FILES');

                if ($response->isOK() && isset($response->header['last-modified'])) {
                    return strtotime($response->header['last-modified']);
                }
            } catch (cURL_Exception $e) {
                usleep(500000); // half-second
                if ($intents < 3) {
                    return self::filectime($filename, ($intents+1));
                }

                error_log("Error! he intentado $intents veces el metodo ". __FUNCTION__ ." y no ha funcionado");
                return false;
            }
        } else {
            return is_file($filename) ? filectime($filename) : null;
        }
    }

    public static function headers($filename)
    {
        if (false === strstr($filename, DIR_FILES)) {
            return false;
        }

        if (false === $amazonS3 = self::getS3()) {
            return false;
        }

        $path = str_replace(DIR_FILES, '', $filename);

        try {
            $response = $amazonS3->get_object_headers(self::getBucketName('FILES'), $path);
        } catch (cURL_Exception $e) {
            return false;
        }

        return $response->header;
    }

    public static function is_readable($filename, $intents = 0)
    {
        if ((strstr($filename, DIR_FILES) || strstr($filename, DIR_EXPORT)) && $amazonS3 = self::getS3()) {
            $path = str_replace(DIR_FILES, '', $filename);
            try {
                return (bool) $amazonS3->if_object_exists(self::getBucketName('FILES'), $path);
            } catch (cURL_Exception $e) {
                usleep(500000); // half-second
                if ($intents < 3) {
                    return self::is_readable($filename, ($intents+1));
                }

                error_log("Error! he intentado $intents veces el metodo ". __FUNCTION__ ." y no ha funcionado");
                return false;
            }
        } else {
            return is_file($filename) && is_readable($filename);
        }
    }

    public static function escribir($path, $data, $force = false, $intents = 0)
    {
        // Condicion acceso S3 -> Accedemos a dir_files Y se puede crear el objeto s3
        if (strstr($path, DIR_FILES) && $amazonS3 = self::getS3()) {
            $pathbucket = str_replace(DIR_FILES, '', $path);

            try {
                $response = $amazonS3->create_object(self::getBucketName('FILES'), $pathbucket, array('body' => $data));

                return (bool) $response->isOK();
            } catch (cURL_Exception $e) {
                usleep(500000); // half-second
                if ($intents < 3) {
                    return self::escribir($path, $data, $force, ($intents+1));
                }

                error_log("Error! he intentado $intents veces el metodo ". __FUNCTION__ ." y no ha funcionado");

                return false;
            }

        // NO Escribimos en S3
        } else {
            if ($force) {
                $dir = dirname($path);
                if (!is_dir($dir)) {
                    if (is_writable(dirname($dir))) {
                        mkdir($dir, 0777);
                    } else {
                        return false;
                    }
                }
            } elseif (!is_dir(dirname($path)) || !is_writable(dirname($path))) {
                return false;
            }

            return (bool) file_put_contents($path, $data);
        }
        return false;
    }

    public static function zipFolder($folder, $filename = false, $opts = "", $priority = "")
    {
        if (!$filename) {
            $filename = $folder . '.zip';
        }

        exec("cd {$folder} && $priority zip -9 $opts -r {$filename} *", $out);
        if (file_exists($filename)) {
            return $filename;
        }

        error_log("Error: unable tu create zip {$filename} from folder {$folder}. " . implode("\n", $out));
        return false;
    }

    public static function unzip($file, $slugify = false)
    {
        if (!file_exists($file)) {
            $file = self::getLocalVersion($file);
        }

        $tmp = "/tmp/". uniqid().time();

        exec("unzip {$file} -d {$tmp}");

        $files = new ArrayBinaryList;
        foreach (glob($tmp . "/*") as $file) {
            if (true === $slugify) {
                $slugifier = new \Cocur\Slugify\Slugify();
                $ext = self::getExtension($file);
                $basename = basename($file, ".{$ext}");
                $slug = $slugifier->slugify($basename);
                $slugPath = str_replace($basename, $slug, $file);
                rename($file, $slugPath);
                $file = $slugPath;
            }

            $files[] = $file;
        }

        return $files;
    }

    public static function getZipInstance($tmpName)
    {
        if (!extension_loaded('zip')) {
            dl('zip.so');
        }

        //instanciamos el ZIP
        $zip = new ZipArchive();

        $res = $zip->open($tmpName, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);

        //creamos el archivo
        if ($res === true) {
            return $zip;
        }

        die("Zip error $res");
    }

    public static function getFileName($name)
    {
        $name = new ArrayObject(explode('.', basename($name)));

        return reset($name);
    }

    public static function getExtension($name)
    {
        $nameExploded = new ArrayObject(explode('.', $name));
        $ext = end($nameExploded);

        if (strlen($ext) > self::LONGITUD_MAXIMA_EXTENSION) {
            return false;
        }

        return strtolower($ext);
    }

    public static function getRandomName($name, $extra = "")
    {
        $extension = self::getExtension($name);
        $tmpname = md5($name).$extra.".".$extension;
        return $tmpname;
    }

    /** DEVUELVE DADO UN FICHERO Y COMO AYUDA UNA EXTENSION LA EXTENSION REAL **/
    public static function getMimeType($file, $auxtype = false)
    {
        $blacklist = array("application/force-download", "fremap/objeto");
        if ($auxtype && !in_array($auxtype, $blacklist)) {
            return $auxtype;
        }

        $extension = self::getExtension($file);

        if ($formato = formato::getFromExtension($extension)) {
            return $formato->getUserVisibleName();
        }

        return "application/{$extension}";
    }

    public static function cleanFilenameString($name, $cleanFunction = false)
    {
        $find = array("á","à","é","è","í","ì","ó","ò","ù","ú","Á","À","É","È","Í","Ì","Ó","Ò","Ù","Ú","Ñ","ñ",",",";","/","´",":","`");
        $replace = array("a","a","e","e","i","i","o","o","u","u","A","A","E","E","I","I","O","O","U","U","N","n", "_","_"," - ","","","");

        if (!mb_check_encoding($name, "utf8")) {
            $name = utf8_encode($name);
        }

        $string = str_ireplace($find, $replace, $name);
        $string = preg_replace('/\s+/', ' ', $string);

        if (is_callable($cleanFunction)) {
            return $cleanFunction($string);
        }

        return $string;
    }

    public static function leer($path, $localPath = null, $intents = 0)
    {

        // Condicion acceso S3 -> Accedemos a dir_files Y si se puede crear el objeto s3
        if (strstr($path, DIR_FILES) && $amazonS3 = self::getS3()) {
            $path = str_replace(DIR_FILES, '', $path);
            try {
                $opt = [];
                if ($localPath) {
                    $opt['fileDownload'] = $localPath;
                }

                $response = self::getObjectResponse($amazonS3, $path, 'FILES', $opt);

                if ($response->isOK()) {
                    if ($localPath) {
                        return true;
                    }

                    return $response->body;
                } else {
                    if ($localPath && is_file($localPath)) {
                        unlink($localPath);
                    }

                    // it could be normal that in DEV env the files are not located
                    if (CURRENT_ENV !== 'dev') {
                        error_log("leer_S3_error [$path] code: ".$response->status);
                    }

                    return false;
                }

            } catch (cURL_Exception $e) {
                usleep(500000); // half-second
                if ($intents < 3) {
                    return self::leer($path, $localPath, ($intents+1));
                }

                error_log("Error! he intentado $intents veces el metodo ". __FUNCTION__ ." y no ha funcionado");
                return false;
            }
        } else {    // NO Leemos de S3
            if (file_exists($path) && is_readable($path)) {
                $data = file_get_contents($path);

                if ($localPath) {
                    return file_put_contents($localPath, $data);
                }

                return $data;
            }
        }
        return false;
    }

    public static function getS3()
    {
        $awsAccesKey    = @trim(get_cfg_var(self::AWS_KEY_ACCESS));
        $awsSecretKey   = @trim(get_cfg_var(self::AWS_KEY_SECRET));

        // Condicion acceso S3 -> Accedemos a dir_files Y claves de acceso S3 definidas
        if ($awsAccesKey && $awsSecretKey) {
            require_once ('AWSSDKforPHP/sdk.class.php');
            return new AmazonS3(array("key" => $awsAccesKey, "secret"=>$awsSecretKey));
        }

        return false;
    }

    public static function formatBytes($bytes, $precision = 2, $space = ' ')
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . $space . $units[$pow];
    }

    public static function descargar($file, $filename = false, $return = false)
    {
        //First, see if the file exists

        //Gather relevent info about file
        $filename = $filename ? $filename : basename($file);
        $aux = explode(".", $file);

        $fileExtension = end($aux);
        if (strpos($filename, ".") === false) {
            $filename = $filename.".".$fileExtension;
        }

        if ($data = archivo::leer($file)) {
            $len = strlen($data);

            //This will set the Content-Type to the appropriate setting for the file
            switch ($fileExtension) {
                case "pdf":
                    $ctype = "application/pdf";
                    break;
                case "exe":
                    $ctype = "application/octet-stream";
                    break;
                case "zip":
                    $ctype = "application/zip";
                    break;
                case "doc":
                    $ctype = "application/msword";
                    break;
                case "xls":
                    $ctype = "application/vnd.ms-excel";
                    break;
                case "ppt":
                    $ctype = "application/vnd.ms-powerpoint";
                    break;
                case "gif":
                    $ctype = "image/gif";
                    break;
                case "png":
                    $ctype = "image/png";
                    break;
                case "jpeg":
                case "jpg":
                    $ctype = "image/jpg";
                    break;
                case "mp3":
                    $ctype = "audio/mpeg";
                    break;
                case "wav":
                    $ctype = "audio/x-wav";
                    break;
                case "mpeg":
                case "mpg":
                case "mpe":
                    $ctype = "video/mpeg";
                    break;
                case "mov":
                    $ctype = "video/quicktime";
                    break;
                case "avi":
                    $ctype = "video/x-msvideo";
                    break;
                case "txt":
                    $ctype = "text/plain";
                    break;
                //The following are for extensions that shouldn't be downloaded (sensitive stuff, like php files)
                case "php":
                    die("<b>Cannot be used for ". $fileExtension ." files!</b>");
                    break;
                case "htm":
                case "html":
                default:
                    $ctype = "application/force-download";
            }

            //Begin writing headers
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: public");
            header("Content-Description: File Transfer");

            //Use the switch-generated Content-Type
            header("Content-Type: $ctype");

            //Force the download
            $header = "Content-Disposition: attachment; filename=\"". self::cleanFilenameString($filename) ."\";";

            header($header);
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: ".$len);
            print $data;

            exit;
        } else {
            if ($return) {
                return false;
            }

            die("<b>404 File not found!</b>");
        }

        return false;
    }

    public static function dump($data, $filename)
    {
        //First, see if the file exists

        //Gather relevent info about file
        $aux = explode(".", $filename);
        $fileExtension = end($aux);

        if ($data) {
            $len = strlen($data);

            //This will set the Content-Type to the appropriate setting for the file
            switch ($fileExtension) {
                case "pdf":
                    $ctype = "application/pdf";
                    break;
                case "exe":
                    $ctype = "application/octet-stream";
                    break;
                case "zip":
                    $ctype = "application/zip";
                    break;
                case "doc":
                    $ctype = "application/msword";
                    break;
                case "xls":
                    $ctype = "application/vnd.ms-excel";
                    break;
                case "ppt":
                    $ctype = "application/vnd.ms-powerpoint";
                    break;
                case "gif":
                    $ctype = "image/gif";
                    break;
                case "png":
                    $ctype = "image/png";
                    break;
                case "jpeg":
                case "jpg":
                    $ctype = "image/jpg";
                    break;
                case "mp3":
                    $ctype = "audio/mpeg";
                    break;
                case "wav":
                    $ctype = "audio/x-wav";
                    break;
                case "mpeg":
                case "mpg":
                case "mpe":
                    $ctype = "video/mpeg";
                    break;
                case "mov":
                    $ctype = "video/quicktime";
                    break;
                case "avi":
                    $ctype = "video/x-msvideo";
                    break;
                case "txt":
                    $ctype = "text/plain";
                    break;
                //The following are for extensions that shouldn't be downloaded (sensitive stuff, like php files)
                case "php":
                    die("<b>Cannot be used for ". $fileExtension ." files!</b>");
                    break;
                default:
                case "htm":
                case "html":
                    $ctype = "application/force-download";
            }

            //Begin writing headers
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: public");
            header("Content-Description: File Transfer");

            //Use the switch-generated Content-Type
            header("Content-Type: $ctype");

            //Force the download
            $header = "Content-Disposition: attachment; filename=\"". self::cleanFilenameString($filename) ."\";";

            header($header);
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: ".$len);
            print $data;

            exit;
        } else {
            if (isset($return) && $return) {
                return false;
            }

            die("<b>404 File not found!</b>");
        }
        return false;
    }

    public static function file2pdf($file, $ext)
    {
        $inFile     = "/tmp/doc.convert.".md5($file).".".$ext;
        $outFile    = "/tmp/doc.convert.".md5($file).".pdf";

        if (is_readable($outFile)) {
            return $outFile;
        }

        if (!file_put_contents($inFile, archivo::leer($file))) {
            return false;
        }

        $command = "soffice --invisible --convert-to pdf $inFile --outdir /tmp";
        exec($command);

        if (!is_readable($outFile)) {
            return false;
        }

        return $outFile;
    }

    public static function PIDExists($pid)
    {
        exec("ps $pid | wc -l", $out);
        return (trim($out[0])>1) ? true : false;
    }

    public static function php5exec($file, $params = array(), $return = false, $showError = true)
    {
        $showLogs = $showError ? '1>&2' : '2>&1';
        $command = PHP_CLI." $file ". implode(" ", $params) ." >/dev/null $showLogs & echo $!";

        if ($return) {
            return $command;
        }

        $process = exec($command, $out, $return);

        if (isset($out[0]) && $pid = (int) $out[0]) {
            return $pid;
        }

        return $process;
    }

    /**
     * Determine if a file extension is a web image extension
     * @param  string  $extension The file extension
     * @return boolean            Return true if the extension is a web image extension
     */
    public function isWebImage ($extension)
    {
        $whiteList = ['jpg', 'jpeg', 'png', 'gif'];
        return in_array($extension, $whiteList);
    }

    /**
     * Given a file extension return the mime type equivalent
     * @param  string  $extension The file extension
     * @return string             The mime type equivalent
     */
    public static function getMimeEquivalent($extension)
    {
        $mimes = [
            'xl'    =>  'application/excel',
            'hqx'   =>  'application/mac-binhex40',
            'cpt'   =>  'application/mac-compactpro',
            'bin'   =>  'application/macbinary',
            'doc'   =>  'application/msword',
            'docx'  =>  'application/msword',
            'word'  =>  'application/msword',
            'class' =>  'application/octet-stream',
            'dll'   =>  'application/octet-stream',
            'dms'   =>  'application/octet-stream',
            'exe'   =>  'application/octet-stream',
            'lha'   =>  'application/octet-stream',
            'lzh'   =>  'application/octet-stream',
            'psd'   =>  'application/octet-stream',
            'sea'   =>  'application/octet-stream',
            'so'    =>  'application/octet-stream',
            'oda'   =>  'application/oda',
            'pdf'   =>  'application/pdf',
            'ai'    =>  'application/postscript',
            'eps'   =>  'application/postscript',
            'ps'    =>  'application/postscript',
            'smi'   =>  'application/smil',
            'smil'  =>  'application/smil',
            'mif'   =>  'application/vnd.mif',
            'xls'   =>  'application/vnd.ms-excel',
            'xlsx'  =>  'application/vnd.ms-excel',
            'ppt'   =>  'application/vnd.ms-powerpoint',
            'wbxml' =>  'application/vnd.wap.wbxml',
            'wmlc'  =>  'application/vnd.wap.wmlc',
            'dcr'   =>  'application/x-director',
            'dir'   =>  'application/x-director',
            'dxr'   =>  'application/x-director',
            'dvi'   =>  'application/x-dvi',
            'gtar'  =>  'application/x-gtar',
            'php3'  =>  'application/x-httpd-php',
            'php4'  =>  'application/x-httpd-php',
            'php'   =>  'application/x-httpd-php',
            'phtml' =>  'application/x-httpd-php',
            'phps'  =>  'application/x-httpd-php-source',
            'js'    =>  'application/x-javascript',
            'swf'   =>  'application/x-shockwave-flash',
            'sit'   =>  'application/x-stuffit',
            'tar'   =>  'application/x-tar',
            'tgz'   =>  'application/x-tar',
            'xht'   =>  'application/xhtml+xml',
            'xhtml' =>  'application/xhtml+xml',
            'zip'   =>  'application/zip',
            'mid'   =>  'audio/midi',
            'midi'  =>  'audio/midi',
            'mp2'   =>  'audio/mpeg',
            'mp3'   =>  'audio/mpeg',
            'mpga'  =>  'audio/mpeg',
            'aif'   =>  'audio/x-aiff',
            'aifc'  =>  'audio/x-aiff',
            'aiff'  =>  'audio/x-aiff',
            'ram'   =>  'audio/x-pn-realaudio',
            'rm'    =>  'audio/x-pn-realaudio',
            'rpm'   =>  'audio/x-pn-realaudio-plugin',
            'ra'    =>  'audio/x-realaudio',
            'wav'   =>  'audio/x-wav',
            'bmp'   =>  'image/bmp',
            'gif'   =>  'image/gif',
            'jpeg'  =>  'image/jpeg',
            'jpe'   =>  'image/jpeg',
            'jpg'   =>  'image/jpeg',
            'png'   =>  'image/png',
            'tiff'  =>  'image/tiff',
            'tif'   =>  'image/tiff',
            'eml'   =>  'message/rfc822',
            'css'   =>  'text/css',
            'html'  =>  'text/html',
            'htm'   =>  'text/html',
            'shtml' =>  'text/html',
            'log'   =>  'text/plain',
            'text'  =>  'text/plain',
            'txt'   =>  'text/plain',
            'rtx'   =>  'text/richtext',
            'rtf'   =>  'text/rtf',
            'xml'   =>  'text/xml',
            'xsl'   =>  'text/xml',
            'mpeg'  =>  'video/mpeg',
            'mpe'   =>  'video/mpeg',
            'mpg'   =>  'video/mpeg',
            'mov'   =>  'video/quicktime',
            'qt'    =>  'video/quicktime',
            'rv'    =>  'video/vnd.rn-realvideo',
            'avi'   =>  'video/x-msvideo',
            'movie' =>  'video/x-sgi-movie',
            // OpenOffice formats
            'odt'   => 'application/vnd.oasis.opendocument.text',
            'odp'   => 'application/vnd.oasis.opendocument.presentation',
            'ods'   => 'application/vnd.oasis.opendocument.spreadsheet',
            'odg'   => 'application/vnd.oasis.opendocument.graphics',
            'odc'   => 'application/vnd.oasis.opendocument.chart',
            'odb'   => 'application/vnd.oasis.opendocument.database',
            'odf'   => 'application/vnd.oasis.opendocument.formula'
        ];

        return (!isset($mimes[strtolower($extension)])) ? 'application/octet-stream' : $mimes[strtolower($extension)];
    }

    /**
     * @param $amazonS3
     * @param $filename
     * @param $bucketName
     * @param array $options
     * @return ResponseCore
     */
    private static function getObjectResponse($amazonS3, $filename, $bucketName, array $options = []): ResponseCore
    {
        $bucket = self::getBucketName($bucketName);
        if (false === empty($options)) {
            return $amazonS3->get_object($bucket, $filename, $options);
        }

        $curl_handle = $amazonS3->get_object(
            $bucket,
            $filename,
            [
                'returnCurlHandle' => true,
            ]
        );

        return self::objectResponse($curl_handle);
    }

    /**
     * @param $amazonS3
     * @param $filename
     * @param $bucket
     * @param array $options
     * @return ResponseCore
     */
    private static function createObjectResponse($amazonS3, $filename, $bucket, array $options = []): ResponseCore
    {
        $options["returnCurlHandle"] = true;
        $curl_handle = $amazonS3->create_object(
            $bucket,
            $filename,
            $options
        );

        return self::objectResponse($curl_handle);
    }

    /**
     * @param $curl_handle
     * @return ResponseCore
     */
    private static function objectResponse($curl_handle): ResponseCore
    {
        $response = curl_exec($curl_handle);

        $headerSize = curl_getinfo($curl_handle, CURLINFO_HEADER_SIZE);
        $responseCode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        $responseBody = substr($response, $headerSize);

        $reponseHeaders = substr($response, 0, $headerSize);
        $reponseHeaders = explode("\r\n\r\n", trim($reponseHeaders));
        $reponseHeaders = array_pop($reponseHeaders);
        $reponseHeaders = explode("\r\n", $reponseHeaders);
        array_shift($reponseHeaders);

        $headerAssoc = [];
        foreach ($reponseHeaders as $header) {
            $kv = explode(': ', $header);
            $headerAssoc[strtolower($kv[0])] = $kv[1];
        }

        curl_close($curl_handle);

        $response = null;
        $headerSize = null;

        return new ResponseCore($headerAssoc, $responseBody, $responseCode);
    }
}
