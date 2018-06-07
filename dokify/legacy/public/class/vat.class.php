<?php

class vat
{
    public static function checkValidVAT(pais $country, $vat)
    {
        // Checking VAT depending of the country selected
        $countryName = ($country->getUID() == pais::SPAIN_CODE) ? "Spain" : $country->getUserVisibleName();
        $funcValidVat = "vat::isValid" .$countryName. "VAT";
        if (is_callable($funcValidVat)) {
            $validVAT = call_user_func($funcValidVat, $vat);
            return $validVAT;
        }
        return true;
    }

    public static function getCIFSum($cif)
    {
        $suma = (int) @$cif[2] + (int) @$cif[4] + (int) @$cif[6];
        for ($i=1; $i<8; $i=$i+2) { // 1,3,5,7
            $tmp = 2 * (int) @$cif[$i];
            //tmpsum = 2*parseInt(cif.charAt(i));
            if (strlen($tmp) == 2) {
                $suma += (($tmp%10)+1);
                //suma += ((tmpsum%10) + 1);
            } else {
                $suma += $tmp;
            }
        }

        return $suma;
    }

    public static function getCIFRegExp()
    {
        return "/^[ABCDEFGHJNPQRSUVW]{1}/";
    }

    public static function isValidItaliaVAT($cif)
    {
        $cif = strtoupper($cif);
        if (!is_string($cif)) {
            return false; //no es una cadena
        }
        $num = $cif;
        $prefix = substr($cif, 0, 2);
        if ($prefix == 'IT') {
            $num = substr($cif, 2);
        }

        if (strlen($num)!=11) {
            return false; //se necesitan 11 digitos
        }
        $X = $Y = $Z = 0;

        //sumar numeros en posiciones impares
        for ($i=0; $i<11; $i+=2) {
            if (!is_numeric($num[$i])) {
                return false;
            }

            $X += intval($num[$i]);
        }

        //sumar numeros en posiciones pares
        for ($i=1; $i<11; $i+=2) {
            $n = intval($num[$i]);
            $Y += $n;
            if ($n>=5) {
                $Z++;
            }
        }
        $Y = $Y*2; //multiplica por 2
        $T = ($X+$Y+$Z)%10; //suma y mod 10

        if ($T===0) {
            return true; //si el resultado es 0 es correcto
        } else {
            return false;
        }
    }

    public static function isValidPortugalVAT($cif)
    {
        $prefix = substr($cif, 0, 2);

        if ($prefix == "PT" && strlen($cif) == 11) {
            return true;
        }
        return false;
    }


    public static function isValidAlemaniaVAT($vat)
    {
        $prefix = substr($vat, 0, 2);
        $num = substr($vat, 2);
        if ($prefix == "DE" && strlen($vat) == 11 && is_numeric($num)) {
            return true;
        }
        return false;
    }

    /**
     * @param $vat
     * @return bool
     */
    public static function isValidFranciaVAT($vat)
    {
        // Validate Siren
        if (true === self::validateNumberWithLuhn($vat, 9)) {
            return true;
        }

        // Validate Siret
        if (true === self::validateNumberWithLuhn($vat, 14)) {
            $sirenInSiret = substr($vat, 0, 9);

            if (true === self::validateNumberWithLuhn($sirenInSiret, 9)) {
                return true;
            }
        }

         // Validate VAT intra community
        if (true === (bool)preg_match('/^FR([0-9A-HJ-NP-Z]){2}([0-9]){9}$/', $vat)) {
            $sirenInVat = substr($vat, 4, 13);

            if (true === self::validateNumberWithLuhn($sirenInVat, 9)) {
                return true;
            }
        }

        return false;
    }

    public static function isValidChileVAT($vat)
    {
        $rut = substr(strtoupper($vat), 0, -1);
        $verification_digit = substr($vat, -1, 1);

        if (stripos($vat, '-') !== false) {
            list($rut,$verification_digit) = explode('-', $vat);
        }

        if (is_bool($verification_digit) && !$verification_digit) {
            return false;
        }

        $m = 0;
        $s = 1;
        $r = $rut;
        for ($m = 0; $r != 0; $r/=10) {
            $s = ($s + $r%10* (9-$m++%6))%11;
        }

        $digito = strtoupper(chr($s?$s+47:75));
        return (bool) $digito == $verification_digit;
    }

    public static function isValidSpainVAT($cif)
    {
        if (strlen($cif) != 9) {
            return false;
        }

        $cif = strtoupper($cif);
        $cifkey = 'JABCDEFGHI';
        $cifsum = (string) self::getCIFSum($cif);
        $cifcheck = (int) (10 - (int) substr($cifsum, -1)) % 10;
        $return = false;

        if (preg_match(self::getCIFRegExp(), $cif, $matches)) {
            if (strlen($cif) < 8) {
                return false;
            }

            if (in_array($cif[0], array('A','B','E','H'))) {
                $return = ( isset($cif[8]) && is_numeric($cif[8]) && (int) $cif[8] == $cifcheck );
            } elseif (in_array($cif[0], array('K','P','Q','S'))) {
                $return = ( isset($cifkey[$cifcheck]) && isset($cif[8]) && (string) $cif[8] === $cifkey[$cifcheck]);
            } elseif (isset($cif[8]) && in_array($cif[8], array('1','2','3','4','5','6','7','8','9','0'))) {
                $return = ( is_numeric($cif[8]) && (int) $cif[8] === $cifcheck);
            } else {
                if (isset($cif[8])) {
                    $return = ( (string) $cif[8] === $cifkey[$cifcheck]);
                }
            }
        }


        if (!$return && !self::isValidSpainId($cif)) {
            return false;
        } else {
            return true;
        }
    }

    public static function getNIFRegExp()
    {
        return "/^[0-9]{8}[A-Z]{1}$/";
    }

    public static function getNIERegExp()
    {
        return "/^[XYZ][0-9]{7,8}[A-Z]{1}$/";
    }

    public static function getNISRegExp()
    {
        return "/^[KLM]{1}/";
    }

    public static function getNIXRegExp()
    {
        return "/^[T]{1}[A-Z0-9]{8}$/";
    }

    public static function extractSpainVats($string)
    {
        $prevent = "[^-.1-9]";
        $nif = '[0-9]{8}[A-Z]{1}';
        $nie = '[XYZ][0-9]{7,8}[A-Z]{1}';
        $nix = '[T]{1}[A-Z0-9]{8}';

        $patterns   = [$nif, $nie, $nix];
        $vats       = [];

        foreach ($patterns as $pattern) {
            $pattern = '/' . $prevent . '(' . $pattern . ')/imus';
            if (preg_match_all($pattern, $string, $matches)) {
                $vats = array_merge($vats, $matches[1]);
            }
        }

        $vats = array_filter(array_unique(array_filter($vats)), 'vat::isValidSpainId');

        return $vats;
    }

    public static function isValidSpainId($nif)
    {
        $nif = strtoupper($nif);
        $nifkey = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $nifsum =self::getCIFSum($nif);

        if (!preg_match_all("/\d/", $nif, $matches)) {
            return false;
        }

        if (preg_match(self::getNIFRegExp(), $nif, $matches)) {
            $dni = substr($nif, 0, strlen($nif)-1);
            return ( $nif[8] == $nifkey[($dni % 23)] );
        }

        if (preg_match(self::getNIERegExp(), $nif, $matches)) {
            $len = strlen($nif) === 9 ? 7 : 8;

            switch (strtoupper($nif[0])) {
                case 'X':
                    $nie = "0" . substr($nif, 1, $len);
                    break;
                case 'Y':
                    $nie = "1" . substr($nif, 1, $len);
                    break;
                case 'Z':
                    $nie = "2" . substr($nif, 1, $len);
                    break;
            }

            return ( $nif[$len+1] == $nifkey[($nie % 23)] );
        }

        // al menos 6 numeros para validar como NIS o NIX
        $chars      = explode($nif, '');
        $numbers    = count(array_filter($chars, 'is_numeric'));
        if ($numbers > 6) {
            if (preg_match(self::getNISRegExp(), $nif, $matches)) {
                return false;
            }

            if (preg_match(self::getNIXRegExp(), $nif, $matches)) {
                return true;
            }
        }

        return false;
    }


    //http://www.cartaodecidadao.pt/images/stories/Algoritmo_Num_Documento_CC.pdf
    //http://ec.europa.eu/taxation_customs/tin/pdf/es/TIN_-_subject_sheet_-_2_structure_and_specificities_es.pdf
    public static function isValidPortugalId($id)
    {
        $id     = trim($id);
        $length = strlen($id);

        if ($length !=12 && $length != 8 && $length !=9 && $length != 7) {
            return false;
        }

        $posicionpar = false;
        $sum = 0;
        $dictionary = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        for ($charnumber = 0; $charnumber < $length; $charnumber++) {
            $valDictionary = $dictionary[$charnumber];
            if ($posicionpar) {
                $valDictionary *= 2;
                if ($valDictionary > 9) {
                    $valDictionary -= 9;
                }
            }

            $sum += $valDictionary;
            $posicionpar = !$posicionpar;
        }

        return ($sum%10) == 0 || $length == 8 || $length == 9 || $length == 7;
    }


    public static function defaultCheck($id)
    {
        if (strlen(trim($id)) < 6) {
            return false;
        }

        // Only allows alphanumeric characters
        if (false === ctype_alnum($id)) {
            return false;
        }

        return true;
    }



    public static function checkIdHuman($id, $country)
    {
        $countries = pais::obtenerTodos();

        if (!$country) {
            $country = new pais(1);
        }

        //Using Spain for España
        $pais = ($country->getUID() == pais::SPAIN_CODE) ? "Spain" : $country->getUserVisibleName();

        if ($countries->toIntList()->contains($country->getUID())) {
            $funcValidVat = "vat::isValid" .$pais. "Id";
            if (is_callable($funcValidVat)) {
                return call_user_func($funcValidVat, $id);
            } else {
                return self::defaultCheck($id);
            }
        }

        return false;
    }

    public static function isInUse($vat = null)
    {
        if (!isset($vat)) {
            return false;
        }

        $SQL = "SELECT count(uid_empresa)
                FROM ". TABLE_EMPRESA ."
                WHERE cif = '". db::scape($vat) ."'";

        return db::get($SQL, 0, 0);
    }

     /**
      * Validate that data is numeric, has the given length and complies with Luhn's algorithm.
      * @param $data
      * @param $length
      * @return bool
      */
    private static function validateNumberWithLuhn($data, $length)
    {
        $numericPattern = '/^([0-9]){' . $length . '}$/';

        if (true === (bool)preg_match($numericPattern, $data)) {
            // Validate Luhn algoritm
            $sum = 0;

            for ($i = 0; $i < $length; ++$i) {
                $index = ($length - $i);
                $tmp = (2 - ($index % 2)) * $data[$i];

                if ($tmp >= 10) {
                    $tmp -= 9;
                }

                $sum += $tmp;
            }

            return ($sum % 10) === 0;
        }

        return false;
    }

    public static function getEquivalentVats($vat)
    {
        $normalizedVat = strtoupper($vat);

        // Although the NIE document must have 9 digits, we, sometimes, accepts NIE documents with 10 digits
        // if they are like X0........ (ex: X02728118L or X2728118L)
        if (false !== strrpos($normalizedVat, 'X', -strlen($normalizedVat))) {
            $nieWithoutX = substr($normalizedVat, 1);
            $normalizedNieWithoutX = ltrim($nieWithoutX, '0');

            $nieWith9Digits = 'X'.str_pad($normalizedNieWithoutX, 8, '0', STR_PAD_LEFT);
            $nieWith10Digits = 'X'.str_pad($normalizedNieWithoutX, 9, '0', STR_PAD_LEFT);

            return [$nieWith9Digits, $nieWith10Digits];
        }

        return [$normalizedVat];
    }
}
