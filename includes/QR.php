<?php
namespace RRZE\ShortURL;

class QR {
    public static function generateQRCode($url, $size = 150, $margin = 4) {
        try {
            // Include the phpqrcode library
            require_once 'phpqrcode/qrlib.php';

            // Check if the URL is provided
            if (empty($url)) {
                throw new \Exception('URL is empty.');
            }

            // Generate a temporary file name for the QR code image
            $temp_file = tempnam(sys_get_temp_dir(), 'qr');

            // Check if the temporary file name is generated successfully
            if (!$temp_file) {
                throw new \Exception('Failed to create temporary file.');
            }

            // Generate the QR code
            $result = \QRcode::png($url, $temp_file, QR_ECLEVEL_L, $size, $margin);

            // Check if QR code generation was successful
            if (!$result) {
                throw new \Exception('Failed to generate QR code.');
            }

            // Read the generated image and convert it to base64
            $image_data = file_get_contents($temp_file);

            // Check if image data was read successfully
            if (!$image_data) {
                throw new \Exception('Failed to read image data.');
            }

            // Convert image data to base64
            $base64_image = 'data:image/png;base64,' . base64_encode($image_data);

            // Delete the temporary file
            unlink($temp_file);

            // Return the base64-encoded image data
            return $base64_image;
        } catch (\Exception $e) {
            // Log the error
            error_log('QR code generation error: ' . $e->getMessage());

            // Return false to indicate an error occurred
            return false;
        }
    }
}

// Example usage:
$url = 'https://example.com';
$qr_image = QR::generateQRCode($url);
if ($qr_image) {
    echo '<img src="' . $qr_image . '" alt="QR Code">';
} else {
    echo 'Error generating QR code.';
}
?>
