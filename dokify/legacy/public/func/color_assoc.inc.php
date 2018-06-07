<?php
function color_assoc($string)
{
    if (!isset($GLOBALS["color_assoc"])) {
        $GLOBALS["color_assoc"] = array();
    }

    if (isset($GLOBALS["color_assoc"][$string])) {
        return $GLOBALS["color_assoc"][$string];
    }

    $colors = [
        "3366FF",
        "FF5A00",
        "000000",
        "FFCC33",
        "FFF300",
        "33FF66",
        "28908D",
        "FF33CC",
        "33CCFF",
        "FF0011",
        "CC33FF",
        "B88A00",
        "FF6633",
        "33FFCC",
        "FF3366",
        "002EB8",
        "EBDCEF",
        "5C9028",
        "B889E1",
        "737373"
    ];

    $i = count($GLOBALS["color_assoc"]);

    if ($i >= sizeof($colors)) {
        $color = str_pad(dechex(($i*167772)), 6, 0, STR_PAD_LEFT);
        return $GLOBALS["color_assoc"][$string] = $color;
    }

    return $GLOBALS["color_assoc"][$string] = $colors[$i];
}
?>
