<?php

namespace App\CustomClass;

class ColorName {

    private $color;
    private $rgb;
    private $hsl;
    private $colorDigit;
    private $names;

    /* $names : ['codeHex', 'color', 'colorName'] */
    public function __construct($names, $color) {
        $this->names = $names;
        $this->colorDigit = str_replace('#', '', $color);
        if (strlen($this->colorDigit) === 3) {
            $color = $color . $this->colorDigit;
        }
        $this->color = strtoupper($color);
        $this->rgb = $this->rgb($color);
        $this->hsl = $this->hsl($color);

    }

    public function getName(){
        if (!ctype_xdigit($this->colorDigit) || strlen($this->color) != 7) {
            return [
                "state" => 'Error',
                "message"   => "Invalid Color: " . $this->color
            ];
        }
        
        $key = -1;
        $df = -1;

        foreach ($this->names as $k => $name) {
            if($this->color == "#" . $name[0]){
                return [
                    "state" => 'success',
                    "query" => $this->color,
                    "datas" => [
                        "hex"       => "#" . $name[0], 
                        "color"     => $name[1], 
                        "colorName" => $name[2],
                        "exact"     => true
                    ]
                ];
            }
            $nameRgb = $this->rgb("#" . $name[0]);
            $nameHsl = $this->hsl("#" . $name[0]);

            $ndf1 = 
                pow($this->rgb['r'] - $nameRgb['r'], 2) + 
                pow($this->rgb['g'] - $nameRgb['g'] , 2) + 
                pow($this->rgb['b'] - $nameRgb['b'], 2);
            $ndf2 = 
                pow($this->hsl['h'] - $nameHsl['h'], 2) + 
                pow($this->hsl['s'] - $nameHsl['s'] , 2) + 
                pow($this->hsl['l'] - $nameHsl['l'], 2);

            $ndf = $ndf1 + $ndf2;
            if($df < 0 || $df > $ndf) {
                $df = $ndf;
                $key = $k;
            }
        }

        if ($key < 0) {
            return [
                "state" => 'Error',
                "message"   => "Invalid Color: " . $color
            ];
        } else {
            return [
                "state" => 'success',
                "query" => $this->color,
                "datas" => [
                    "hex"       => "#" . $this->names[$key][0], 
                    "color"     => $this->names[$key][1], 
                    "colorName" => $this->names[$key][2],
                    "exact"     => false
                ]
            ];
        }
    }
    // adopted from: Farbtastic 1.2
    // http://acko.net/dev/farbtastic
    public function hsl($color) {
        $rgb = array(
            intval(hexdec(substr($color, 1, 2))) / 255, 
            intval(hexdec(substr($color, 3, 2))) / 255, 
            intval(hexdec(substr($color, 5, 7))) / 255
        );
        $min = null;
        $max = null;
        $delta = null;
        $h = null;
        $s = null;
        $l = null;
        $r = $rgb[0];
        $g = $rgb[1];
        $b = $rgb[2];
        $min = min($r, min($g, $b));
        $max = max($r, max($g, $b));
        $delta = $max - $min;
        $l = ($min + $max) / 2;
        $s = 0;
        if($l > 0 && $l < 1)
        $s = $delta / ($l < 0.5 ? (2 * $l) : (2 - 2 * $l));
        $h = 0;
        if($delta > 0)
        {
            if ($max == $r && $max != $g) $h += ($g - $b) / $delta;
            if ($max == $g && $max != $b) $h += (2 + ($b - $r) / $delta);
            if ($max == $b && $max != $r) $h += (4 + ($r - $g) / $delta);
            $h /= 6;
        }
        return [
            'h' =>intval($h * 255), 
            's' =>intval($s * 255),
            'l' =>intval($l * 255)
        ];
    }
    // adopted from: Farbtastic 1.2
    // http://acko.net/dev/farbtastic
    public function rgb($color) {
        return [
            'r' => hexdec(substr($color, 1, 2)), 
            'g' => hexdec(substr($color, 3, 2)),  
            'b' => hexdec(substr($color, 5, 7))
        ];
    }
}

?>