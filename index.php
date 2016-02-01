<?php 

include 'CImage.php';
$file_name = "image/LENA.jpg";
//$file_name = "image/number2.jpg";
// Load
$c_image = new CImage();
$c_image->setHeader($file_name);

$params = array('type'=>'', 'ext'=>array());
// $params['ext']['red'] = 200;
// $params['ext']['green'] = 100;
// $params['ext']['blue'] = 100;
//水平翻转
// $params['type'] = 'flipHorizontal';

//垂直翻转
// $params['type'] = 'flipVertical';

//对称翻转
// $params['type'] = 'flipSymmetric';

//灰色图片
// $params['type'] = 'greyScale';

//平移图片
// $params['type'] = 'moveImage';
// $params['ext']['offset_x'] = 20;
// $params['ext']['offset_y'] = 100; 
// $params['ext']['red'] = 100;

//旋转图片
//degree 0~90
//  $params['type'] = 'retateImage';
//  $params['ext']['degree'] = 90;

//二值化
// $params['type'] = 'binaryzation';
// $params['ext']['threshold'] = 100;

//线性变换
// $params['type'] = 'linearTransformation';
// $params['ext']['slope'] = -1;
// $params['ext']['intercept'] = 255;

//边缘锐化
// $params['type'] = 'differentiatingEdgeSharpening';

//曝光
//$params['type'] = 'exposure';

//扩散
//$params['type'] = 'spreed';

//guasslaplacian 边缘检测算子
$params['type'] = 'guasslaplacian';

$out_image = $c_image->transImage($file_name, $params);
?>