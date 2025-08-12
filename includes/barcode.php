<?php
class BarcodeGenerator {
    
    public static function generateEAN13() {
        // Générer 12 chiffres aléaoires pour EAN-13
        $code = '';
        for ($i = 0; $i < 12; $i++) {
            $code .= rand(0, 9);
        }
        
        // Calculer la clé de contrôle
        $checksum = self::calculateEAN13Checksum($code);
        
        return $code . $checksum;
    }
    
    private static function calculateEAN13Checksum($code) {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = intval($code[$i]);
            if ($i % 2 == 0) {
                $sum += $digit;
            } else {
                $sum += $digit * 3;
            }
        }
        
        $checksum = (10 - ($sum % 10)) % 10;
        return $checksum;
    }
    
    public static function generateBarcodeImage($code, $width = 300, $height = 80) {
        // Créer une image pour le code-barres EAN-13
        $image = imagecreate($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        imagefill($image, 0, 0, $white);
        
        // Patterns pour EAN-13 (version simplifiée)
        $patterns = [
            '0' => '0001101', '1' => '0011001', '2' => '0010011', '3' => '0111101',
            '4' => '0100011', '5' => '0110001', '6' => '0101111', '7' => '0111011',
            '8' => '0110111', '9' => '0001011'
        ];
        
        $bar_width = 2;
        $x = 10;
        
        // Dessiner les barres de début
        for ($i = 0; $i < 3; $i++) {
            imagefilledrectangle($image, $x, 10, $x + $bar_width - 1, $height - 25, $black);
            $x += $bar_width * 2;
        }
        
        // Dessiner les barres pour chaque chiffre
        for ($i = 0; $i < strlen($code); $i++) {
            $digit = $code[$i];
            $pattern = $patterns[$digit];
            
            for ($j = 0; $j < strlen($pattern); $j++) {
                if ($pattern[$j] == '1') {
                    imagefilledrectangle($image, $x, 10, $x + $bar_width - 1, $height - 25, $black);
                }
                $x += $bar_width;
            }
            
            // Séparateur au milieu
            if ($i == 5) {
                $x += $bar_width;
                for ($k = 0; $k < 5; $k++) {
                    imagefilledrectangle($image, $x, 10, $x + $bar_width - 1, $height - 25, $black);
                    $x += $bar_width * 2;
                }
            }
        }
        
        // Barres de fin
        for ($i = 0; $i < 3; $i++) {
            imagefilledrectangle($image, $x, 10, $x + $bar_width - 1, $height - 25, $black);
            $x += $bar_width * 2;
        }
        
        // Ajouter le texte du code avec espacement
        $font_size = 3;
        $text_y = $height - 15;
        
        // Premier chiffre
        imagestring($image, $font_size, 5, $text_y, $code[0], $black);
        
        // Premiers 6 chiffres
        $text_x = 25;
        for ($i = 1; $i <= 6; $i++) {
            imagestring($image, $font_size, $text_x, $text_y, $code[$i], $black);
            $text_x += 12;
        }
        
        // Derniers 6 chiffres
        $text_x += 15;
        for ($i = 7; $i < 13; $i++) {
            imagestring($image, $font_size, $text_x, $text_y, $code[$i], $black);
            $text_x += 12;
        }
        
        return $image;
    }
    
    public static function saveBarcodeImage($code, $filename) {
        $image = self::generateBarcodeImage($code);
        imagepng($image, $filename);
        imagedestroy($image);
        return $filename;
    }
}
?>
