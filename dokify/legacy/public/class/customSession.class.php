<?php

// Incluimos la clase log para usar algunos métodos
include_once 'log.class.php';

class customSession implements SessionHandlerInterface
{
    private $base64 = null;

    // session used for presence?
    public $presence = true;

    // db.class instance
    private $db;

    // string | cadena a la que asociaremos el bloqueo
    private $lock;

    // session expiration time in seconds
    private $timeout = 60;

    // readonly var
    private $readOnly;

    // app redis handler
    private $handler;

    // session table
    const TABLE = "agd_core.sessions";

    // secret key
    const SECRET = "34s99df338sunHFSDhs2f-lss2S";

    // shared cookie name
    const SHARED = "sessionid";

    // debug mode?
    const DEBUG = false;

    // the constructor
    public function __construct()
    {
        $this->db = db::singleton();

        $this->readOnly = db::isReadOnly();

        $app = \Dokify\Application::getInstance();
        $this->handler = $app['redis.session.storage'];
        session_set_save_handler($this->handler);

        session_start();
        // $sessid = session_id();
        // $newsessid = $sessid . "." . self::hash($sessid);
        // setcookie(self::SHARED, $newsessid, time()+60*60*24*30, '/', $_SERVER["HTTP_HOST"]);
    }

    public static function set($key, $val)
    {
        $_SESSION[$key] = $val;

        // force session write
        session_write_close();

        // prevent session start warnings when headers are already sent
        if (headers_sent()) {
            @session_start();
            return;
        }

        session_start();
    }

    public static function get($key)
    {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        return null;
    }

    public static function hash($id)
    {
        $hash = base64_encode(hash_hmac('sha256', $id, self::SECRET, true));

        return str_replace("=", "", $hash);
    }

    public function open($savePath, $sessionName)
    {
        return true;
    }

    public function read($sid)
    {
        // try redis first
        if ($data = $this->handler->read($sid)) {
            return $data;
        }

        // get the lock name, associated with the current session
        $this->lock = 'session_' . db::scape($sid);

        //$SQL = 'SELECT "' . $this->lock ."'";
        //if( !$this->db->query($SQL) ) return false;

        $SQL = 'SELECT data FROM ' . self::TABLE . ' WHERE 1
            AND sid = "' . $sid . '"
            AND expires > "' . time() . '"
        LIMIT 1';

        $data = $this->db->query($SQL, 0, 0);

        $this->base64 = base64_encode($data);

        $data = (array) json_decode($data, true);
        $data = self::serialize($data);

        return $data;
    }

    public function write($id, $data)
    {
        $this->handler->write($id, $data);

        $array = self::unserialize($data);
        $json = json_encode($array);
        $data = db::scape($json);

        $time = time() + ini_get('session.gc_maxlifetime');

        if ($this->base64 === base64_encode($json)) {
            if ($this->presence) {
                $SQL = 'UPDATE '. self::TABLE . ' SET expires = ' . $time . ', updatedAt = NOW() WHERE sid = "' . db::scape($id) . '"';

                return $this->db->query($SQL);
            }

            return true;

        } else {
            $env = CURRENT_ENV;
            $ip = log::getIPAddress();

            $usuario = isset($array[SESSION_USUARIO]) ? $array[SESSION_USUARIO] : '';
            if (isset($array[SESSION_USUARIO_SIMULADOR])) {
                $usuario = $array[SESSION_USUARIO_SIMULADOR];
            }

            $type = isset($array[SESSION_TYPE]) ? $array[SESSION_TYPE] : '';

            $SQL = '
                INSERT INTO ' . self::TABLE . ' (sid, type, uid_usuario, data, expires, ip, env, createdAt)
                VALUES ("' . db::scape($id) . '", "'.$type.'", "'.$usuario.'", "' .$data . '", "' . $time . '", "'. $ip .'", "'. $env .'", NOW() )
                ON DUPLICATE KEY UPDATE data = "' . $data . '", type = "'. $type .'", uid_usuario = "'. $usuario .'", expires = "' . $time . '", updatedAt = NOW(), ip = "' . $ip . '", env = "' . $env . '"
            ';

            if (self::DEBUG) {
                error_log(trim($SQL));
            }

            if ($this->readOnly) {
                return true;
            }

            return $this->db->query($SQL);
        }
    }

    public function close()
    {
        // release the lock associated with the current session
        //$this->db->query('SELECT RELEASE_LOCK("' . $this->lock . '")');
        return true;
    }

    public function destroy($sid)
    {
        $this->handler->destroy($sid);

        // deletes the current session id from the database
        $SQL = 'DELETE FROM ' . self::TABLE . ' WHERE sid = "' . db::scape($sid) . '"';
        if (self::DEBUG) {
            error_log($SQL);
        }

        if ($this->readOnly) {
            return true;
        }

        // if anything happened
        if ($this->db->query($SQL)) {
            return true;
        }

        // if something went wrong, return false
        return false;
    }

    public function gc($maxlifetime)
    {
        $time   = time() - $maxlifetime;
        $table  = self::TABLE;

        // it deletes expired sessions from database
        $SQL = "DELETE FROM {$table} WHERE expires < {$time}";
        if (self::DEBUG) {
            error_log($SQL);
        }

        if ($this->readOnly) {
            return true;
        }

        return $this->db->query($SQL);
    }

    public static function unserialize($data)
    {
        if (strlen($data) == 0) {
            return array();
        }

        // match all the session keys and offsets
        preg_match_all('/(^|;|\})([a-zA-Z0-9_]+)\|/i', $data, $matchesarray, PREG_OFFSET_CAPTURE);
        $returnArray = array();

        $lastOffset = null;
        $currentKey = '';
        foreach ($matchesarray[2] as $value) {
            $offset = $value[1];
            if (!is_null($lastOffset)) {
                $valueText = substr($data, $lastOffset, $offset - $lastOffset);
                $returnArray[$currentKey] = unserialize($valueText);
            }
            $currentKey = $value[0];

            $lastOffset = $offset + strlen($currentKey) + 1;
        }

        $valueText = substr($data, $lastOffset);
        $returnArray[$currentKey] = @unserialize($valueText);

        return $returnArray;
    }

    public static function serialize($array, $safe = true)
    {
        // the session is passed as refernece, even if you dont want it to
        if ($safe) {
            $array = unserialize(serialize($array));
        }

        $raw = '' ;
        $line = 0 ;
        $keys = array_keys($array) ;
        foreach ($keys as $key) {
            $value = $array[ $key ] ;
            $line ++ ;

            $raw .= $key .'|' ;

            if (is_array($value) && isset($value['huge_recursion_blocker_we_hope'])) {
                $raw .= 'R:'. $value['huge_recursion_blocker_we_hope'] . ';' ;
            } else {
                $raw .= serialize($value) ;
            }
            $array[$key] = array('huge_recursion_blocker_we_hope' => $line) ;
        }

        return $raw ;

    }
}
