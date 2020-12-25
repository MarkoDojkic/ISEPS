<?php
    //Downloaded from https://code.tutsplus.com/tutorials/build-your-own-captcha-and-contact-form-in-php--net-5362
    session_start();
    require "../../constants.php";
    
    $permitted_chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    
    function generate_string($input, $strength = 10) {
        $input_length = strlen($input);
        $random_string = '';
        for($i = 0; $i < $strength; $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }
    
        return $random_string;
    }
    
    $image = imagecreatetruecolor(200, 50);
    
    imageantialias($image, true);
    
    $colors = [];
    
    $red = rand(125, 175);
    $green = rand(125, 175);
    $blue = rand(125, 175);
    
    for($i = 0; $i < 5; $i++) {
        $colors[] = imagecolorallocate($image, $red - 20*$i, $green - 20*$i, $blue - 20*$i);
    }
    
    imagefill($image, 0, 0, $colors[0]);
    
    for($i = 0; $i < 10; $i++) {
        imagesetthickness($image, rand(2, 10));
        $line_color = $colors[rand(1, 4)];
        imagerectangle($image, rand(-10, 190), rand(-10, 10), rand(-10, 190), rand(40, 60), $line_color);
    }
    
    $black = imagecolorallocate($image, 0, 0, 0);
    $white = imagecolorallocate($image, 255, 255, 255);
    $textcolors = [$black, $white];
    
    $fontsDir = DIR_ROOT . DIR_MISCELLANEOUS . "/captchaFonts/";

    $fonts = ["$fontsDir/font0.ttf","$fontsDir/font1.ttf","$fontsDir/font2.ttf","$fontsDir/font3.ttf","$fontsDir/font4.ttf","$fontsDir/font5.ttf","$fontsDir/font6.ttf","$fontsDir/font7.ttf","$fontsDir/font8.ttf","$fontsDir/font9.ttf","$fontsDir/font10.ttf","$fontsDir/font11.ttf","$fontsDir/font12.ttf","$fontsDir/font13.ttf","$fontsDir/font14.ttf","$fontsDir/font15.ttf","$fontsDir/font16.ttf","$fontsDir/font17.ttf","$fontsDir/font18.ttf","$fontsDir/font19.ttf","$fontsDir/font20.ttf","$fontsDir/font21.ttf","$fontsDir/font22.ttf","$fontsDir/font23.ttf","$fontsDir/font24.ttf","$fontsDir/font25.ttf","$fontsDir/font26.ttf","$fontsDir/font27.ttf","$fontsDir/font28.ttf","$fontsDir/font29.ttf","$fontsDir/font30.ttf","$fontsDir/font31.ttf","$fontsDir/font32.ttf","$fontsDir/font33.ttf","$fontsDir/font34.ttf",];
    
    $string_length = 6;
    $captcha_string = generate_string($permitted_chars, $string_length);
    
    $_SESSION['captcha_text'] = $captcha_string;
    
    for($i = 0; $i < $string_length; $i++) {
        $letter_space = 170/$string_length;
        $initial = 15;
        
        imagettftext($image, 24, rand(-15, 15), $initial + $i*$letter_space, rand(25, 45), $textcolors[rand(0, 1)], $fonts[array_rand($fonts)], $captcha_string[$i]);
    }
    
    header('Content-type: image/png');
    imagepng($image);
    imagedestroy($image);
?>