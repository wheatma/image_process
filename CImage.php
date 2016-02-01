<?php 
/**
 * @author wheat
 * @version 1.0
 * 2013-12-30
 */
class CImage {
    
    public function __construct(){
        
    }
    
    public function __call($name, $args){
    }
    
    /**
     * BMP 创建函数
     * @author simon
     * @param string $filename path of bmp file
     * @example who use,who knows
     * @return resource of GD
     */
    public function imagecreatefrombmp( $filename ){
        if ( !$f1 = fopen( $filename, "rb" ) )
            return FALSE;
        $FILE = unpack( "vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread( $f1, 14 ) );
        if ( $FILE['file_type'] != 19778 )
            return FALSE;
        $BMP = unpack( 'Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel' . '/Vcompression/Vsize_bitmap/Vhoriz_resolution' . '/Vvert_resolution/Vcolors_used/Vcolors_important', fread( $f1, 40 ) );
        $BMP['colors'] = pow( 2, $BMP['bits_per_pixel'] );
        if ( $BMP['size_bitmap'] == 0 )
            $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
        $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel'] / 8;
        $BMP['bytes_per_pixel2'] = ceil( $BMP['bytes_per_pixel'] );
        $BMP['decal'] = ($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] -= floor( $BMP['width'] * $BMP['bytes_per_pixel'] / 4 );
        $BMP['decal'] = 4 - (4 * $BMP['decal']);
        if ( $BMP['decal'] == 4 )
            $BMP['decal'] = 0;
        $PALETTE = array();
        if ( $BMP['colors'] < 16777216 ){
            $PALETTE = unpack( 'V' . $BMP['colors'], fread( $f1, $BMP['colors'] * 4 ) );
        }
        $IMG = fread( $f1, $BMP['size_bitmap'] );
        $VIDE = chr(0);
        $res = imagecreatetruecolor( $BMP['width'], $BMP['height'] );
        $P = 0;
        $Y = $BMP['height'] - 1;
        while( $Y >= 0 ){
            $X = 0;
            while( $X < $BMP['width'] ){
                if ( $BMP['bits_per_pixel'] == 32 ){
                    $COLOR = unpack( "V", substr( $IMG, $P, 3 ) );
                    $B = ord(substr($IMG, $P,1));
                    $G = ord(substr($IMG, $P+1,1));
                    $R = ord(substr($IMG, $P+2,1));
                    $color = imagecolorexact( $res, $R, $G, $B );
                    if ( $color == -1 )
                        $color = imagecolorallocate( $res, $R, $G, $B );
                    $COLOR[0] = $R*256*256+$G*256+$B;
                    $COLOR[1] = $color;
                }elseif ( $BMP['bits_per_pixel'] == 24 )
                    $COLOR = unpack( "V", substr( $IMG, $P, 3 ) . $VIDE );
                elseif ( $BMP['bits_per_pixel'] == 16 ){
                    $COLOR = unpack( "n", substr( $IMG, $P, 2 ) );
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                }elseif ( $BMP['bits_per_pixel'] == 8 ){
                    $COLOR = unpack( "n", $VIDE . substr( $IMG, $P, 1 ) );
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                }elseif ( $BMP['bits_per_pixel'] == 4 ){
                    $COLOR = unpack( "n", $VIDE . substr( $IMG, floor( $P ), 1 ) );
                    if ( ($P * 2) % 2 == 0 )
                        $COLOR[1] = ($COLOR[1] >> 4);
                    else
                        $COLOR[1] = ($COLOR[1] & 0x0F);
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                }elseif ( $BMP['bits_per_pixel'] == 1 ){
                    $COLOR = unpack( "n", $VIDE . substr( $IMG, floor( $P ), 1 ) );
                    if ( ($P * 8) % 8 == 0 )
                        $COLOR[1] = $COLOR[1] >> 7;
                    elseif ( ($P * 8) % 8 == 1 )
                    $COLOR[1] = ($COLOR[1] & 0x40) >> 6;
                    elseif ( ($P * 8) % 8 == 2 )
                    $COLOR[1] = ($COLOR[1] & 0x20) >> 5;
                    elseif ( ($P * 8) % 8 == 3 )
                    $COLOR[1] = ($COLOR[1] & 0x10) >> 4;
                    elseif ( ($P * 8) % 8 == 4 )
                    $COLOR[1] = ($COLOR[1] & 0x8) >> 3;
                    elseif ( ($P * 8) % 8 == 5 )
                    $COLOR[1] = ($COLOR[1] & 0x4) >> 2;
                    elseif ( ($P * 8) % 8 == 6 )
                    $COLOR[1] = ($COLOR[1] & 0x2) >> 1;
                    elseif ( ($P * 8) % 8 == 7 )
                    $COLOR[1] = ($COLOR[1] & 0x1);
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                }else
                    return FALSE;
                imagesetpixel( $res, $X, $Y, $COLOR[1] );
                $X++;
                $P += $BMP['bytes_per_pixel'];
            }
            $Y--;
            $P += $BMP['decal'];
        }
        fclose( $f1 );
        return $res;
    }
    
    public function loadImage($file_name){
        $type = $this->getImageType($file_name);
        switch ($type){
            case 1:
                $im = @imagecreatefromgif($file_name);
                break;
            case 2:
                $im = @imagecreatefromjpeg($file_name);
                break;
            case 3:
                $im = @imagecreatefrompng($file_name);
                break;
            case 6:
                $im = @$this->imagecreatefrombmp($file_name);
                break;
            default:
                $im = false;
                break;
        }
        return $im;
    }
    
    public function getImageType($file_name){
        $size = getimagesize($file_name);
        return $size ? $size[2] : 0;
    }
    
    public function setHeader($file_name){
        $size = getimagesize($file_name);
        $fp = fopen($file_name, "rb");
        if ($size && $fp) {
            header("Content-type: {$size['mime']}");
        }
    }
    

    /**
     * string $file_name 图片文件路径名称
     * array $params 参数
     */
    public function transImage($file_name, $params){
        $type = $params['type'];
        $ext = $params['ext'];
        $im = $this->loadImage($file_name);
        if(!$im){
            return false;
        }
        $new_image_size = $this->getNewImageSize($im, $type, $ext);
        $new_image_width = $new_image_size['width'];
        $new_image_height = $new_image_size['height'];
        $new_im = @imagecreatetruecolor($new_image_width, $new_image_height);
        
        //设置背景色
        $background_red = isset($ext['red']) && is_numeric($ext['red']) ? $ext['red'] : 0 ;
        $background_green = isset($ext['green']) && is_numeric($ext['green']) ? $ext['green'] : 0 ;
        $background_blue = isset($ext['blue']) && is_numeric($ext['blue']) ? $ext['blue'] : 0 ;
        $background = imagecolorallocate($new_im, $background_red, $background_green, $background_blue);
        imagefill($new_im, 0, 0, $background);
        
        $image_width = imagesx($im);
        $image_height = imagesy($im);
        for($i=0;$i<$image_width;$i++){
            for($j=0;$j<$image_height;$j++){
                //在目标位置画点
                $new_position = $this->getPixelPosition($type, $im, $image_width, $image_height, $i, $j, $ext);
                $new_x = $new_position['x'];
                $new_y = $new_position['y'];
                if($new_x < 0 || $new_y < 0 || $new_x > $new_image_width || $new_y > $new_image_height)
                    continue;
                //确定颜色值
                $pixel_color = $this->getPixelRGB($type, $im, $image_width, $image_height, $i, $j, $ext);
                //生成颜色
                $new_pixel_color = $this->colorAllocate($im, $pixel_color['red'], $pixel_color['green'], $pixel_color['blue']);
                imageSetPixel($new_im, $new_x, $new_y, $new_pixel_color);
            }
        }
        $this->setOut($new_im);
    }
    
    public function setOut($im){
        imagejpeg($im);
    }
    
    /**
     * 生成新的图像大小
     */
    public function getNewImageSize($im, $type, $ext){
        $width = imagesx($im);
        $height = imagesy($im);
        switch ($type) {
            case 'retateImage':
                $degree = isset($ext['degree']) && is_numeric($ext['degree']) ? $ext['degree'] : 0;
                $rad = $degree * M_PI / 180;
                $new_width_right_top = abs($width * cos($rad));
                $new_width_left_bottom = abs($height * sin($rad));
                $new_height_right_bottom = round(abs(($width * sin($rad) + $height * cos($rad))));
                $width = round(($new_width_right_top + $new_width_left_bottom));
                $height = $new_height_right_bottom;
                break;
            default:
                break;
        }
        return array('width'=>$width, 'height'=>$height);
    }
    
    public function getPixelRGB($type, $im, $width, $height, $x, $y, $ext){
        switch ($type){
            case 'bianyuanjiance':
                break;
            case 'greyScale':
                /**
                 * 转灰色图片
                 * Y=R*0.299+G*0.587+B*0.114
                 */
                $grey = $this->getGreyValue($im, $x, $y);
                $color_trans = array('red'=>$grey, 'green'=>$grey, 'blue'=>$grey);
                break;
            case 'binaryzation':
                $color_trans = $this->getBinaryzationPixelRGB($im, $x, $y, $ext);
                break;
            case 'linearTransformation':
                $color_trans = $this->getLinearTransformationPixelRGB($im, $x, $y, $ext);
                break;
            case 'differentiatingEdgeSharpening':
                $color_trans = $this->getDifferentiatingEdgeSharpeningPixelRGB($im, $x, $y);
                break;
            case 'exposure':
                $color_trans = $this->exposurePixelRGB($im, $x, $y);
                break;
            case 'spreed':
                $color_trans = $this->spreedPixelRGB($im, $width, $height, $x, $y);
                break;
            case 'guasslaplacian':
                $color_trans = $this->guasslaplacianPixelRGB($im, $width, $height, $x, $y);
                break;
            default:
                $color_index = ImageColorAt($im, $x, $y);
                $color_trans = imagecolorsforindex($im, $color_index);
                break;
        }
        return $color_trans;
    }
    
    public function getPixelPosition($type, $im, $width, $height, $x, $y, $ext){
        switch ($type) {
            case 'flipHorizontal':
            case 'flipVertical':
            case 'flipSymmetric':
            case 'moveImage':
            case 'retateImage':
                return $this->$type($im, $width, $height, $x, $y, $ext);
                break;
            default : 
                return array('x'=>$x, 'y'=>$y);
                break;
        }
    }
    
    public function getGreyValue($im, $x, $y){
        $color_index = ImageColorAt($im, $x, $y);
        $color = imagecolorsforindex($im, $color_index);
        $grey = round(0.299 * $color['red'] + 0.587 * $color['green'] + 0.114 * $color['blue']);
        return $grey;
    }
    
    public function colorAllocate($im, $red, $green, $blue){
        return imagecolorallocate($im, $red, $green, $blue);
    } 
    
    public function setPixelRGB(&$im, $x, $y, $rgb){
        $color_index = ImageColorAt($im, $x, $y);
        imagecolorset($im, $color_index, (int)$rgb['red'], (int)$rgb['green'], (int)$rgb['blue']);
        return $im;
    }
    
    public function getImageWidth($file_name){
        $size = getimagesize($file_name);
        return $size ? $size[0] : 0;
    }
    
    public function getImageHeight($file_name){
        $size = getimagesize($file_name);
        return $size ? $size[1] : 0;
    }
    
    public function getImageSize($file_name){
        $size = getimagesize($file_name);
        $width = $height = 0;
        if ($size) {
            $width = $size[0];
            $height = $size[1];
        }
        return array(
                'width' => $width,
                'height' => $height,
                );
    }
    
    public function drewPixel(&$im, $x, $y, $color){
        imageSetPixel($im, $x, $y, $color);
    }
    
    /**
     * 水平翻转
     * x1 = width - x0
     * y1 = y0
     */
    public function flipHorizontal($im, $width, $height, $x, $y){
        $_x = $width -1 - $x;
        $_y = $y;
        return array('x'=>$_x, 'y'=>$_y);
    }
    
    /**
     * 垂直翻转
     * x1 = x0
     * y1 = height - y0
     */
    public function flipVertical($im, $width, $height, $x, $y){
        $_x = $x;
        $_y = $height - 1 - $y;
        return array('x'=>$_x, 'y'=>$_y);
    }
    
    /**
     * 对称翻转
     * x1 = width - x0
     * y1 = height - y0
     */
    public function flipSymmetric($im, $width, $height, $x, $y){
        $_x = $width -1 - $x;
        $_y = $height - 1 - $y;
        return array('x'=>$_x, 'y'=>$_y);
    }
    
    /**
     * 平移
     * x1 = x0 + Δx
     * y1 = y0 + Δy
     */
    public function moveImage($im, $width, $height, $x, $y, $ext){
        $offset_x = isset($ext['offset_x']) && is_numeric($ext['offset_x']) ? $ext['offset_x'] : 0;
        $offset_y = isset($ext['offset_y']) && is_numeric($ext['offset_y']) ? $ext['offset_y'] : 0;
        $_x = $x + $ext['offset_x'];
        $_y = $y + $ext['offset_y'];
        return array('x'=>$_x, 'y'=>$_y);
    }
    
    /**
     * 旋转图片
     * x1 = x0 * cosθ - y0 * sinθ
     * y1 = x0 * sinθ + y0 * cosθ
     */
    public function retateImage($im, $width, $height, $x, $y, $ext){
        $degree = isset($ext['degree']) && is_numeric($ext['degree']) ? $ext['degree'] : 0;
        $rad = $degree * M_PI / 180;
        $_x = round(($x * cos($rad) - $y * sin($rad)));
        $new_width_left_bottom = round($height * sin($rad));
        $_x += $new_width_left_bottom;
        $_y = round(($x * sin($rad) + $y * cos($rad)));
        return array('x'=>$_x, 'y'=>$_y);
    }
    
    
    public function getBinaryzationPixelRGB($im, $x, $y, $ext){
        $grey = $this->getGreyValue($im, $x, $y);
        $threshold = isset($ext['threshold']) && is_numeric($ext['threshold']) ? $ext['threshold'] : 0;
        if($threshold < 0)
            $threshold = 0;
        if($threshold > 255)
            $threshold = 255;
        if($grey <= $threshold){
            $color_trans = array('red'=>0, 'green'=>0, 'blue'=>0);
        } else {
            $color_trans = array('red'=>255, 'green'=>255, 'blue'=>255);
        }
        return $color_trans;
    }
    
    public function getLinearTransformationPixelRGB($im, $x, $y, $ext){
        $slope = isset($ext['slope']) && is_numeric($ext['slope']) ? $ext['slope'] : 1;
        $intercept = isset($ext['intercept']) && is_numeric($ext['intercept']) ? $ext['intercept'] : 0;
        $color_index = ImageColorAt($im, $x, $y);
        $color = imagecolorsforindex($im, $color_index);
        foreach($color as $key => $value){
            $color[$key] = round($slope * $value + $intercept);
            if($color[$key] < 0){
                $color[$key] = 0;
            }
            if($color[$key] > 255){
                $color[$key] = 255;
            }
        }
        
        $color_trans = array('red'=>$color['red'], 'green'=>$color['green'], 'blue'=>$color['blue']);
        return $color_trans;
    }
    
    /**
     * 微分法边缘检测
     */
    public function getDifferentiatingEdgeSharpeningPixelRGB($im, $x, $y){
        $grey = $this->getGreyValue($im, $x, $y);
        if(!$x){
            $color_trans = array('red'=>0, 'green'=>0, 'blue'=>0);
        } else {
            $grey_left = $this->getGreyValue($im, $x-1, $y);
            $grey_edge = abs($grey - $grey_left);
            if($grey_edge > 255)
                $grey_edge = 255;
            $color_trans = array('red'=>$grey_edge, 'green'=>$grey_edge, 'blue'=>$grey_edge);
        }
        return $color_trans;
    }
    
    /**
     * 曝光
     */
    public function exposurePixelRGB($im, $x, $y){
        $color_index = ImageColorAt($im, $x, $y);
        $color = imagecolorsforindex($im, $color_index);
        $red = $color['red']>128 ? $color['red'] : 255-$color['red'];
        $green = $color['green']>128 ? $color['green'] : 255-$color['green'];
        $blue = $color['blue']>128 ? $color['blue'] : 255-$color['blue'];
        return array('red'=>$red, 'green'=>$green, 'blue'=>$blue);
    }
    
    /**
     * 扩散
     * 周围5x5矩阵，随机一点
     */
    public function spreedPixelRGB($im, $width, $height, $x, $y){
        $left = $x-2<0 ? 0 : $x-2;
        $right = $x+2<$width-1 ? $x+2 : $width-1;
        $top = $y-2<0 ? 0 : $y-2;
        $bottom = $y+2<$height-1 ? $y+2 : $height-1;
        $_x = rand($left, $right);
        $_y = rand($top, $bottom);
        $color_index = ImageColorAt($im, $_x, $_y);
        $color = imagecolorsforindex($im, $color_index);
        return $color;
    }
    
    //guasslaplacian 边缘检测算子
    // -2 -4 -4 -4 -2
    // -4  0  8  0 -4
    // -4  8 24  8 -4
    // -4  0  8  0 -4
    // -2 -4 -4 -4 -2
    public function guasslaplacianPixelRGB($im, $width, $height, $x, $y){
        if($x < 2 || $x > $width-3 || $y < 2 || $y > $height -3){
            $grey = $this->getGreyValue($im, $x, $y);
            $color_trans = array('red'=>$grey, 'green'=>$grey, 'blue'=>$grey);
        } else {
            $guasslaplacian = array(
                    array(-2, -4, -4, -4, -2),
                    array(-4, 0, 8, 0, -4),
                    array(-4, 8, 24, 8, -4),
                    array(-4, 0, 8, 0, -4),
                    array(-2, -4, -4, -4, -2),
                    );
            $color_metrix = array();
            for($i=0; $i<5; $i++){
                for($j=0; $j<5; $j++){
                    $color_metrix[$i][$j] = $this->getGreyValue($im, $x-2+$i, $y-2+$j);
                }
            }
            $grey = round($this->convolution($color_metrix, $guasslaplacian));
            if($grey > 255)
                $grey = 255;
            $color_trans = array('red'=>$grey, 'green'=>$grey, 'blue'=>$grey);
        }
        return $color_trans;
    }
    
    public function convolution($arr1, $arr2){
        $m = count($arr1[0]);
        $n = count($arr1);
        $value = 0;
        for($i=0;$i<$m;$i++){
            for($j=0;$j<$n;$j++){
                $value1 = isset($arr1[$i][$j]) ? $arr1[$i][$j] : 0;
                $value2 = isset($arr2[$i][$j]) ? $arr2[$i][$j] : 0;
                $value += $value1 * $value2;
            }
        }
        return $value;
    }
    
    public function __destroy(){
        
    }
}