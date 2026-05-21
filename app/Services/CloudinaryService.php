<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    public function upload(UploadedFile $file, $folder = 'products')
    {
        $result = cloudinary()->upload($file->getRealPath(), [
            'folder' => $folder
        ]);

        return $result->getSecurePath();
    }

    public function delete($publicId)
    {
        try {
            return cloudinary()->destroy($publicId);
        } catch (\Exception $e) {
            \Log::warning("Failed to delete image from Cloudinary for publicId {$publicId}: " . $e->getMessage());
            return false;
        }
    }

    public function extractPublicId($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $parts = explode('/upload/', $path);

        if (count($parts) < 2) return null;

        $afterUpload = $parts[1];
        
        $afterUpload = preg_replace('/^v\d+\//', '', $afterUpload);

        $pos = strrpos($afterUpload, '.');
        if ($pos !== false) {
            $afterUpload = substr($afterUpload, 0, $pos);
        }

        return $afterUpload;
    }
}
