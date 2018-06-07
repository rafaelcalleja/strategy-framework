<?php

//Cortar una cadena
function string_truncate($string, $length, $end = "...")
{
    //dump(var_dump( mb_detect_encoding($string) ));
    if (strlen($string) <= $length) {
        return $string;
    } else {
        return mb_substr($string, 0, $length, "utf8").$end;
    }
}

function get_concat_char($string)
{
    return (strpos($string, "?")) ? "&" : "?";
}

//DEVOLVER UN ARRAY de array( nombre, apellidos) A array( nombre like '%$str%', apellidos like '%$str%' )
function prepareLike($arr, $str, $wrap = "%")
{
    $arrResult = array();
    foreach ($arr as $val) {
        if (strpos($val, " as ") !== false) {
            $val = substr($val, 0, strpos($val, " as "));
        }
        $arrResult[] = $val . " LIKE '".$wrap.db::scape($str).$wrap."' ";
    }
    return $arrResult;
}

function mb_ucfirst($string, $encoding = 'UTF-8')
{
    $strlen = mb_strlen($string, $encoding);
    $firstChar = mb_substr($string, 0, 1, $encoding);
    $then = mb_substr($string, 1, $strlen - 1, $encoding);
    return mb_strtoupper($firstChar, $encoding) . $then;
}

function toCamelCase($string)
{
    $arrayString = explode(' ', $string);
    $arrayResult = array();
    foreach ($arrayString as $word) {
        $arrayResult[] = mb_ucfirst(mb_strtolower($word, 'UTF-8'));
    }
    return implode(' ', $arrayResult);
}
