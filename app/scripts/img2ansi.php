<?php
/**
 * User: Alex Phillips
 * Date: 3/11/14
 * Time: 5:59 PM
 */

$converter = new img2ansi();
$converter->run();

/**
 * Class img2ansi
 *
 * This class converts any image into ASCII art with ANSI coloring
 * for use in the terminal.
 *
 * Many thanks go to the following:
 *      Original idea and logic: jdiaz5513 (https://gist.github.com/jdiaz5513/9218791)
 *      PHP code for Delta E CMC formula: Xong (http://www.php.net/manual/en/function.imagecolorclosesthwb.php)
 *      PHP code for Delta E CIE2000 formula: renasboy (https://github.com/renasboy/php-color-difference)
 *      Image background trim with GD: zavaboy (http://zavaboy.com/2007/10/06/trim_an_image_using_php_and_gd)
 *      Another background trim with GD: Stephan Salat
 *              (http://codereview.stackexchange.com/questions/21228/performance-improvements-php-gd-resize-and-trim-image-background-keeping-the-cor)
 */
class img2ansi
{
    private $_args;
    private $_image;
    private $_colors_array = array(
        "black"          => array(0, 0, 0),
        "red"            => array(205, 0, 0),
        "green"          => array(0, 205, 0),
        "yellow"         => array(205, 205, 0),
        "blue"           => array(0, 0, 238),
        "magenta"        => array(205, 0, 205),
        "cyan"           => array(0, 205, 205),
        "gray"           => array(229, 229, 229),
        "dark gray"      => array(127, 127, 127),
        "bright red"     => array(255, 0, 0),
        "bright green"   => array(0, 255, 0),
        "bright yellow"  => array(255, 255, 0),
        "bright blue"    => array(92, 92, 255),
        "bright magenta" => array(255, 0, 255),
        "bright cyan"    => array(0, 255, 255),
        "white"          => array(255, 255, 255),
    );
    private $ansi_array = array(
        "reset"          => '0',     # reset
        "black"          => '00;30', # black
        "red"            => '00;31', # red
        "green"          => '00;32', # green
        "yellow"         => '00;33', # yellow
        "blue"           => '00;34', # blue
        "magenta"        => '00;35', # magenta
        "cyan"           => '00;36', # cyan
        "gray"           => '00;37', # gray
        "dark gray"      => '01;30', # dark gray
        "bright red"     => '01;31', # bright red
        "bright green"   => '01;32', # bright green
        "bright yellow"  => '01;33', # bright yellow
        "bright blue"    => '01;34', # bright blue
        "bright magenta" => '01;35', # bright magenta
        "bright cyan"    => '01;36', # bright cyan
        "white"          => '01;37', # white
    );

    /**
     * Set default values from given options
     */
    public function __construct()
    {
        $this->_args = getopt('i:f:o:');
        if (isset($this->_args['i'])) {
            // Set image path
            $this->_image = $this->_args['i'];

            // Set padding
            if (!isset($this->_args['p'])) {
                $this->_args['p'] = 0;
            }

            // Set deltaE conversion formula
            if (!isset($this->_args['f'])) {
                $this->_args['f'] = 'cmc';
            }
            else if (strtolower($this->_args['f']) !== 'cmc' && strtolower($this->_args['f']) !== 'cie2000') {
                $this->_printUsage();
            }
        }
        else {
            $this->_printUsage();
        }
    }

    private function _printUsage()
    {
        print <<<__TEXT__
Usage:
  -i    input file      path to file for conversion
  [-f]  color formula   choose between the CMC and CIE2000 deltaE RGB to ANSI formula (CMC by default)
  [-p]  padding         pass in a padding in pixels to pad the trimmed image with
  [-o]  output file     save the contents of the script to a file

__TEXT__;
        exit;
    }

    public function run()
    {
        $this->_image = file_get_contents($this->_image);
        $img = imagecreatefromstring($this->_image);

        $this->_trimBackgroundWithPadding($img, imagecolorat($img, 0, 0), $this->_args['p']);

        $width = imagesx($img);
        $height = imagesy($img);

        $fill_char = "##";
        $output = '';
        $last = '';

        for($h = 0; $h < $height; $h++){
            for($w = 0; $w < $width; $w++){
                $rgb = imagecolorat($img, $w, $h);
                $vals = imagecolorsforindex($img, $rgb);
                $r = $vals['red'];
                $g = $vals['green'];
                $b = $vals['blue'];
                $a = $vals['alpha'];

                $color = $this->findClosestColor($r, $g, $b);

                if ($a === 0 && $r === 0 && $g === 0 && $b === 0) {
                    $output .= "  ";
                }
                else {
                    $output .= $this->getColoredString($fill_char, $color);
                }

                if($w == $width - 1){
                    $output .= "\n";
                }
            }
        }
        $output .= $this->getColoredString('', 'reset');

        if (isset($this->_args['o'])) {
            file_put_contents($this->_args['o'], print_r($output, true));
        }
        else {
            echo $output;
        }
    }

    /**
     * Return given string with ANSI color from color table
     *
     * @param $string
     * @param $color
     *
     * @return string
     */
    private function getColoredString($string, $color)
    {
        $colored_string = "";
        if (isset($this->ansi_array[$color])) {
            $colored_string .= "\033[" . $this->ansi_array[$color] . "m";
        }
        // Add string and end coloring
        $colored_string .=  $string . "\033[0m";

        return $colored_string;
    }

    /**
     * Return the color from the color table closest to the passed in color
     *
     * @param $r
     * @param $g
     * @param $b
     *
     * @return int|null|string
     */
    private function findClosestColor($r, $g, $b)
    {
        // @TODO: add option for cmc or cie2000
        if (!isset($this->_args['f'])) {
            $this->_args['f'] = 'cmc';
        }
        switch ($this->_args['f']) {
            case 'cie2000':
                $diff = new deltaECIE2000();
                $names = array();
                $diffs = array();
                foreach ($this->_colors_array as $name => $vals) {
                    $names[] = $name;
                    $diffs[] = $diff->run(array($r, $g, $b), $vals);
                }
                $smallest = min($diffs);
                $key = array_search($smallest, $diffs);
                return $names[$key];
                break;
            case 'other':
                $diffs = array();
                $names = array();
                foreach ($this->_colors_array as $name => $vals) {
                    $diffs[] = pow(($r - $vals[0]) * 0.30, 2) + pow(($g - $vals[1]) * 0.49, 2) + pow(($b - $vals[2]) * 0.21, 2);
                    $names[] = $name;
                }
                $smallest = min($diffs);
                $key = array_search($smallest, $diffs);
                return $names[$key];
                break;
            case 'cmc':
            default:
                return $this->deltaECMC(array($r, $g, $b), $this->_colors_array);
                break;
        }
    }

    /*
     * Returns the index of the palette-color which is most similar
     * to $givenColor.
     *
     * $givenColor and the colors in $palette should be either
     * formatted as (#)rrggbb
     * (e. g. "ff0000", "4da4f3" or "#b5d7f3")
     * or arrays with values for red, green and blue
     * (e. g. $givenColor = array( 0xff, 0x00, 0x00 ) )
     *
     * References:
     * function rgb2lab
     *   - http://www.f4.fhtw-berlin.de/~barthel/ImageJ/ImageJ.htm
     *
     * function rgb2lab & function deltaE
     *   - http://www.brucelindbloom.com
     */
    private function deltaECMC(
        $givenColor,
        $palette = array( 'blue' => '43aafd','red' => 'fe6256','green' => '64b949','yellow' => 'fcf357',
                          'black' => '656565','white' => 'fdfdfd','orange' => 'fea800','purple' => '9773fe')
    ) {
        if(!function_exists('rgb2lab')) {
            function rgb2lab($rgb) {
                $eps = 216/24389; $k = 24389/27;
                // reference white D50
                $xr = 0.964221; $yr = 1.0; $zr = 0.825211;

                $rgb[0] = $rgb[0]/255; //R 0..1
                $rgb[1] = $rgb[1]/255; //G 0..1
                $rgb[2] = $rgb[2]/255; //B 0..1

                // assuming sRGB (D65)
                $rgb[0] = ($rgb[0] <= 0.04045)?($rgb[0]/12.92):pow(($rgb[0]+0.055)/1.055,2.4);
                $rgb[1] = ($rgb[1] <= 0.04045)?($rgb[1]/12.92):pow(($rgb[1]+0.055)/1.055,2.4);
                $rgb[2] = ($rgb[2] <= 0.04045)?($rgb[2]/12.92):pow(($rgb[2]+0.055)/1.055,2.4);

                // sRGB D50
                $x =  0.4360747*$rgb[0] + 0.3850649*$rgb[1] + 0.1430804 *$rgb[2];
                $y =  0.2225045*$rgb[0] + 0.7168786*$rgb[1] + 0.0606169 *$rgb[2];
                $z =  0.0139322*$rgb[0] + 0.0971045*$rgb[1] + 0.7141733 *$rgb[2];

                $xr = $x/$xr; $yr = $y/$yr; $zr = $z/$zr;

                $fx = ($xr > $eps)?pow($xr, 1/3):($fx = ($k * $xr + 16) / 116);
                $fy = ($yr > $eps)?pow($yr, 1/3):($fy = ($k * $yr + 16) / 116);
                $fz = ($zr > $eps)?pow($zr, 1/3):($fz = ($k * $zr + 16) / 116);

                $lab = array();
                $lab[] = round(( 116 * $fy ) - 16);
                $lab[] = round(500*($fx-$fy));
                $lab[] = round(200*($fy-$fz));
                return $lab;
            }
        }

        if(!function_exists('deltaE')) {
            function deltaE($lab1, $lab2) {
                // CMC 1:1
                $l = 1; $c = 1;

                $c1 = sqrt($lab1[1]*$lab1[1]+$lab1[2]*$lab1[2]);
                $c2 = sqrt($lab2[1]*$lab2[1]+$lab2[2]*$lab2[2]);

                $h1 = (((180000000/M_PI) * atan2($lab1[1],$lab1[2]) + 360000000) % 360000000)/1000000;

                $t = (164 <= $h1 AND $h1 <= 345)?(0.56 + abs(0.2 * cos($h1+168))):(0.36 + abs(0.4 * cos($h1+35)));
                $f = sqrt(pow($c1,4)/(pow($c1,4) + 1900));

                $sl = ($lab1[0] < 16)?(0.511):((0.040975*$lab1[0])/(1 + 0.01765*$lab1[0]));
                $sc = (0.0638 * $c1)/(1 + 0.0131 * $c1) + 0.638;
                $sh = $sc * ($f * $t + 1 -$f);

                return sqrt(
                    pow(($lab1[0]-$lab2[0])/($l * $sl),2) +
                    pow(($c1-$c2)/($c * $sc),2) +
                    pow(sqrt(
                            ($lab1[1]-$lab2[1])*($lab1[1]-$lab2[1]) +
                            ($lab1[2]-$lab2[2])*($lab1[2]-$lab2[2]) +
                            ($c1-$c2)*($c1-$c2)
                        )/$sh,2)
                );
            }
        }

        if(!function_exists('str2rgb')) {
            function str2rgb($str)
            {
                $str = preg_replace('~[^0-9a-f]~','',$str);
                $rgb = str_split($str,2);
                for($i=0;$i<3;$i++)
                    $rgb[$i] = intval($rgb[$i],16);

                return $rgb;
            }
        }

        $givenColorRGB = is_array($givenColor)?$givenColor:str2rgb($givenColor);

        $min = 0xffff;
        $return = NULL;

        foreach($palette as $key => $color) {
            $color = is_array($color)?$color:str2rgb($color);
            if($min >= ($deltaE = deltaE(rgb2lab($color),rgb2lab($givenColorRGB))))
            {
                $min = $deltaE;
                $return = $key;
            }
        }

        return $return;
    }

    /**
     * Trim image background
     *
     * @param $gdImage image resource
     *
     * @return array|bool coordinats to trim
     *
     * Ex:
        if($box = $this->_trimBackground($img)) {
           $gdTrimmed = imagecreatetruecolor($box['w'], $box['h']);
           imagecopy($gdTrimmed, $img, 0, 0, $box['l'], $box['t'], $box['w'], $box['h']);

           $imageWidth = $box['w'];
           $imageHeight = $box['h'];
           $img = $gdTrimmed;

           unset($gdTrimmed);
        }
     */
    private function _trimBackground($gdImage){

        $hex = imagecolorat($gdImage, 0,0);

        $width = imagesx($gdImage);
        $height = imagesy($gdImage);

        $bTop = 0;
        $bLft = 0;
        $bBtm = $height - 1;
        $bRt = $width - 1;

        for(; $bTop < $height; ++$bTop) {
            for($x = 0; $x < $width; ++$x) {
                if(imagecolorat($gdImage, $x, $bTop) != $hex) {
                    break 2;
                }
            }
        }

        if($bTop == $height) {
            return false;
        }

        for(; $bBtm >= 0; --$bBtm) {
            for($x = 0; $x < $width; ++$x) {
                if(imagecolorat($gdImage, $x, $bBtm) != $hex) {
                    break 2;
                }
            }
        }

        for(; $bLft < $width; ++$bLft) {
            for($y = $bTop; $y <= $bBtm; ++$y) {
                if(imagecolorat($gdImage, $bLft, $y) != $hex) {
                    break 2;
                }
            }
        }

        for(; $bRt >= 0; --$bRt) {
            for($y = $bTop; $y <= $bBtm; ++$y) {
                if(imagecolorat($gdImage, $bRt, $y) != $hex) {
                    break 2;
                }
            }
        }

        $bBtm++;
        $bRt++;

        return array('l' => $bLft, 't' => $bTop, 'r' => $bRt, 'b' => $bBtm, 'w' => $bRt - $bLft, 'h' => $bBtm - $bTop);

    }

    /**
     * Trims an image then optionally adds padding around it.
     * $im  = Image link resource
     * $bg  = The background color to trim from the image
     * $pad = Amount of padding to add to the trimmed image
     *        (acts simlar to the "padding" CSS property: "top [right [bottom [left]]]")
     *
     * @param $im gd image resource
     * @param $bg gd color (ex: imagecolorat($img, 0, 0)
     * @param null $pad optional padding parameter
     */
    private function _trimBackgroundWithPadding(&$im, $bg, $pad=null){

        // Calculate padding for each side.
        if (isset($pad)){
            $pp = explode(' ', $pad);
            if (isset($pp[3])){
                $p = array((int) $pp[0], (int) $pp[1], (int) $pp[2], (int) $pp[3]);
            }else if (isset($pp[2])){
                $p = array((int) $pp[0], (int) $pp[1], (int) $pp[2], (int) $pp[1]);
            }else if (isset($pp[1])){
                $p = array((int) $pp[0], (int) $pp[1], (int) $pp[0], (int) $pp[1]);
            }else{
                $p = array_fill(0, 4, (int) $pp[0]);
            }
        }else{
            $p = array_fill(0, 4, 0);
        }

        // Get the image width and height.
        $imw = imagesx($im);
        $imh = imagesy($im);

        // Set the X variables.
        $xmin = $imw;
        $xmax = 0;

        // Start scanning for the edges.
        for ($iy=0; $iy<$imh; $iy++){
            $first = true;
            for ($ix=0; $ix<$imw; $ix++){
                $ndx = imagecolorat($im, $ix, $iy);
                if ($ndx != $bg){
                    if ($xmin > $ix){ $xmin = $ix; }
                    if ($xmax < $ix){ $xmax = $ix; }
                    if (!isset($ymin)){ $ymin = $iy; }
                    $ymax = $iy;
                    if ($first){ $ix = $xmax; $first = false; }
                }
            }
        }

        // The new width and height of the image. (not including padding)
        $imw = 1+$xmax-$xmin; // Image width in pixels
        $imh = 1+$ymax-$ymin; // Image height in pixels

        // Make another image to place the trimmed version in.
        $im2 = imagecreatetruecolor($imw+$p[1]+$p[3], $imh+$p[0]+$p[2]);

        // Make the background of the new image the same as the background of the old one.
        $bg2 = imagecolorallocate($im2, ($bg >> 16) & 0xFF, ($bg >> 8) & 0xFF, $bg & 0xFF);
        imagefill($im2, 0, 0, $bg2);

        // Copy it over to the new image.
        imagecopy($im2, $im, $p[3], $p[0], $xmin, $ymin, $imw, $imh);

        // To finish up, we replace the old image which is referenced.
        $im = $im2;
    }
}

class deltaECIE2000
{
    public function run ($rgb1, $rgb2)
    {
        list($l1, $a1, $b1) = $this->_rgb2lab($rgb1);
        list($l2, $a2, $b2) = $this->_rgb2lab($rgb2);

        $avg_lp     = ($l1 + $l2) / 2;
        $c1         = sqrt(pow($a1, 2) + pow($b1, 2));
        $c2         = sqrt(pow($a2, 2) + pow($b2, 2));
        $avg_c      = ($c1 + $c2) / 2;
        $g          = (1 - sqrt(pow($avg_c , 7) / (pow($avg_c, 7) + pow(25, 7)))) / 2;
        $a1p        = $a1 * (1 + $g);
        $a2p        = $a2 * (1 + $g);
        $c1p        = sqrt(pow($a1p, 2) + pow($b1, 2));
        $c2p        = sqrt(pow($a2p, 2) + pow($b2, 2));
        $avg_cp     = ($c1p + $c2p) / 2;
        $h1p        = rad2deg(atan2($b1, $a1p));
        if ($h1p < 0) {
            $h1p    += 360;
        }
        $h2p        = rad2deg(atan2($b2, $a2p));
        if ($h2p < 0) {
            $h2p    += 360;
        }
        $avg_hp     = abs($h1p - $h2p) > 180 ? ($h1p + $h2p + 360) / 2 : ($h1p + $h2p) / 2;
        $t          = 1 - 0.17 * cos(deg2rad($avg_hp - 30)) + 0.24 * cos(deg2rad(2 * $avg_hp)) + 0.32 * cos(deg2rad(3 * $avg_hp + 6)) - 0.2 * cos(deg2rad(4 * $avg_hp - 63));
        $delta_hp   = $h2p - $h1p;
        if (abs($delta_hp) > 180) {
            if ($h2p <= $h1p) {
                $delta_hp += 360;
            }
            else {
                $delta_hp -= 360;
            }
        }
        $delta_lp   = $l2 - $l1;
        $delta_cp   = $c2p - $c1p;
        $delta_hp   = 2 * sqrt($c1p * $c2p) * sin(deg2rad($delta_hp) / 2);

        $s_l        = 1 + ((0.015 * pow($avg_lp - 50, 2)) / sqrt(20 + pow($avg_lp - 50, 2)));
        $s_c        = 1 + 0.045 * $avg_cp;
        $s_h        = 1 + 0.015 * $avg_cp * $t;

        $delta_ro   = 30 * exp(-(pow(($avg_hp - 275) / 25, 2)));
        $r_c        = 2 * sqrt(pow($avg_cp, 7) / (pow($avg_cp, 7) + pow(25, 7)));
        $r_t        = -$r_c * sin(2 * deg2rad($delta_ro));

        $kl = $kc = $kh = 1;

        $delta_e    = sqrt(pow($delta_lp / ($s_l * $kl), 2) + pow($delta_cp / ($s_c * $kc), 2) + pow($delta_hp / ($s_h * $kh), 2) + $r_t * ($delta_cp / ($s_c * $kc)) * ($delta_hp / ($s_h * $kh)));
        return $delta_e;
    }

    private function _rgb2lab ($rgb)
    {
        return $this->_xyz2lab($this->_rgb2xyz($rgb));
    }

    private function _rgb2xyz ($rgb)
    {
        list($r, $g, $b) = $rgb;

        $r = $r <= 0.04045 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.04045 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.04045 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        $r *= 100;
        $g *= 100;
        $b *= 100;

        $x = $r * 0.412453 + $g * 0.357580 + $b * 0.180423;
        $y = $r * 0.212671 + $g * 0.715160 + $b * 0.072169;
        $z = $r * 0.019334 + $g * 0.119193 + $b * 0.950227;

        return array($x, $y, $z);
    }

    private function _xyz2lab ($xyz)
    {
        list ($x, $y, $z) = $xyz;

        $x /= 95.047;
        $y /= 100;
        $z /= 108.883;

        $x = $x > 0.008856 ? pow($x, 1 / 3) : $x * 7.787 + 16 / 116;
        $y = $y > 0.008856 ? pow($y, 1 / 3) : $y * 7.787 + 16 / 116;
        $z = $z > 0.008856 ? pow($z, 1 / 3) : $z * 7.787 + 16 / 116;

        $l = $y * 116 - 16;
        $a = ($x - $y) * 500;
        $b = ($y - $z) * 200;

        return array($l, $a, $b);
    }
}