<?php

class Degriz_Crop_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function crop($imageUrl, $width, $height, $forceExactSize = false)
    {
        // Generate a unique filename based on the desired width and height
        $filename = md5($imageUrl . $width . $height) . '.jpg';

        // Destination path for the modified image
        $destinationPath = $this->getDestinationPath($filename);

        // Check if the image already exists
        if (file_exists($destinationPath)) {
            // Return the URL of the existing image
            return Mage::getBaseUrl('media') . 'catalog/crop/' . $filename;
        }

        // Download the image from the URL and save it locally
        $tmpImagePath = $this->downloadImage($imageUrl);

        if (!$tmpImagePath) {
            // Unable to download the image
            return null;
        }

        try {
            // Create a Varien_Image instance from the downloaded image
            $image = new Varien_Image($tmpImagePath);

            // Get the original image dimensions
            $originalWidth = $image->getOriginalWidth();
            $originalHeight = $image->getOriginalHeight();

            // Calculate the target dimensions based on the desired width and height
            $targetWidth = $width;
            $targetHeight = $height;

            if ($forceExactSize) {
                // Force exact size by resizing the image without maintaining aspect ratio
                $image->resize($targetWidth, $targetHeight);
            } else {
                // Calculate the aspect ratio of the original image
                $aspectRatio = $originalWidth / $originalHeight;

                // Calculate the target dimensions while maintaining the aspect ratio
                if ($originalWidth > $originalHeight) {
                    $targetHeight = round($targetWidth / $aspectRatio);
                } else {
                    $targetWidth = round($targetHeight * $aspectRatio);
                }

                // Resize the image while maintaining aspect ratio
                $image->resize($targetWidth, $targetHeight);
            }

            // Save the modified image to the desired destination
            $image->save($destinationPath);

            // Return the URL of the modified image
            return Mage::getBaseUrl('media') . 'catalog/crop/' . $filename;
        } catch (Exception $e) {
            // Error occurred during image manipulation
            Mage::logException($e);
        } finally {
            // Clean up the temporary downloaded image file
            unlink($tmpImagePath);
        }

        return null;
    }

    private function downloadImage($imageUrl)
    {
        // Generate a unique filename for the temporary downloaded image
        $tmpFilename = md5($imageUrl);
        
        // Determine the file extension based on the image URL
        $extension = pathinfo($imageUrl, PATHINFO_EXTENSION);
        if ($extension === 'jpeg' || $extension === 'jpg') {
            $tmpFilename .= '.jpg';
        } elseif ($extension === 'png') {
            $tmpFilename .= '.png';
        } else {
            // Unsupported file extension
            return null;
        }

        // Temporary path for downloaded image
        $tmpImagePath = Mage::getBaseDir('tmp') . DS . $tmpFilename;

        // Download the image and save it locally
        $success = @file_put_contents($tmpImagePath, file_get_contents($imageUrl));

        if ($success) {
            return $tmpImagePath;
        }

        return null;
    }

    private function getDestinationPath($filename)
    {
        // Destination path for the modified image
        return Mage::getBaseDir('media') . DS . 'catalog' . DS . 'crop' . DS . $filename;
    }
}