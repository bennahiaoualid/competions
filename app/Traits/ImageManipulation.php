<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

trait ImageManipulation
{
    
    /**
     * Get image manager instance with driver detection
     */
    private function getImageManager(): ImageManager
    {
        // Prefer Imagick if available, fallback to GD
        if (extension_loaded('imagick')) {
            return new ImageManager(new ImagickDriver());
        }
        
        return new ImageManager(new Driver());
    }

    /**
     * Get default configuration
     */
    private function getDefaultConfig(): array
    {
        return [
            'max_size' => config('image.max_size', 10240), // 10MB
            'allowed_mimes' => config('image.allowed_mimes', ['jpeg', 'jpg', 'png', 'gif', 'webp']),
            'min_width' => config('image.min_width', 100),
            'max_width' => config('image.max_width', 5000),
            'min_height' => config('image.min_height', 100),
            'max_height' => config('image.max_height', 5000),
            'disk' => config('image.default_disk', 'public'),
            'quality' => config('image.quality', 80),
            'format' => config('image.format', 'jpeg'),
        ];
    }

    /**
     * Check memory requirements for image processing
     */
    private function checkMemoryRequirements(int $width, int $height): bool
    {
        // Rough calculation: width × height × 4 bytes (RGBA) × safety factor
        $requiredBytes = $width * $height * 4 * 1.5;
        $memoryLimit = ini_get('memory_limit');
        
        if ($memoryLimit === '-1') {
            return true; // No memory limit
        }
        
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        $currentUsage = memory_get_usage(true);
        
        return ($currentUsage + $requiredBytes) < ($memoryLimitBytes * 0.8); // 80% safety margin
    }

    /**
     * Convert memory limit string to bytes
     */
    private function convertToBytes(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $number = (int) $memoryLimit;

        return match($last) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    /**
     * Translate image validation errors
     */
    public function translateImageErrors(array $errors): array
    {
        $translatedErrors = [];
        
        foreach ($errors as $error) {
            if (is_array($error) && isset($error['key'])) {
                $translatedErrors[] = __('messages.validation.images.' . $error['key'], $error['params'] ?? []);
            } else {
                $translatedErrors[] = $error;
            }
        }
        
        return $translatedErrors;
    }

    /**
     * Enhanced image validation with memory check
     */
    public function validateImage(UploadedFile $file, array $options = []): array
    {
        $config = array_merge($this->getDefaultConfig(), $options);
        $errors = [];

        // Check if file is actually an image
        if (!$file->isValid()) {
            $errors[] = [
                'key' => 'invalid_file',
                'params' => []
            ];
            return $errors;
        }

        // Check file size
        if ($file->getSize() > ($config['max_size'] * 1024)) {
            $errors[] = [
                'key' => 'file_size_exceeded',
                'params' => ['max_size' => ($config['max_size'] / 1024)]
            ];
        }

        // Check MIME type
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $config['allowed_mimes'])) {
            $errors[] = [
                'key' => 'file_type_not_allowed',
                'params' => ['allowed_types' => implode(', ', $config['allowed_mimes'])]
            ];
        }

        // Validate actual image file and dimensions
        try {
            $manager = $this->getImageManager();
            $image = $manager->read($file);
            $width = $image->width();
            $height = $image->height();

            // Check memory requirements
            if (!$this->checkMemoryRequirements($width, $height)) {
                $errors[] = [
                    'key' => 'image_too_large_for_processing',
                    'params' => ['width' => $width, 'height' => $height]
                ];
            }

            // Dimension validation
            if ($width < $config['min_width'] || $width > $config['max_width']) {
                $errors[] = [
                    'key' => 'width_out_of_range',
                    'params' => [
                        'width' => $width,
                        'min_width' => $config['min_width'],
                        'max_width' => $config['max_width']
                    ]
                ];
            }

            if ($height < $config['min_height'] || $height > $config['max_height']) {
                $errors[] = [
                    'key' => 'height_out_of_range',
                    'params' => [
                        'height' => $height,
                        'min_height' => $config['min_height'],
                        'max_height' => $config['max_height']
                    ]
                ];
            }
        } catch (Exception $e) {
            throw $e;
        }

        return $errors;
    }

    /**
     * Enhanced save method with better error handling
     */
    public function saveImage(UploadedFile $file, string $path, array $options = []): array
    {
        $config = array_merge($this->getDefaultConfig(), $options);

        try {
            // Generate unique filename
            $filename = $this->generateUniqueFilename($file, $config['format']);
            $fullPath = $path . '/' . $filename;

            // Load and process image
            $manager = $this->getImageManager();
            $image = $manager->read($file);

            // Store original dimensions
            $originalWidth = $image->width();
            $originalHeight = $image->height();

            // Resize if needed
            $image = $this->resizeImage($image, $config['max_width'], $config['max_height']);

            // Convert format and encode
            $imageData = $this->encodeImage($image, $config['format'], $config['quality']);

            // Save main image
            $saved = Storage::disk($config['disk'])->put($fullPath, $imageData);

            if (!$saved) {
                throw new Exception('Failed to save image to storage');
            }

            $result = [
                'success' => true,
                'filename' => $filename,
                'path' => $fullPath,
                'url' => Storage::disk($config['disk'])->url($fullPath),
                'size' => Storage::disk($config['disk'])->size($fullPath),
                'width' => $image->width(),
                'height' => $image->height(),
                'original_width' => $originalWidth,
                'original_height' => $originalHeight,
                'format' => $config['format'],
                'thumbnails' => [],
            ];

            // Generate thumbnails if requested
            if ($config['generate_thumbnails'] ?? false) {
                $result['thumbnails'] = $this->generateThumbnails(
                    $file, // Pass original file instead of processed image
                    $path,
                    $filename,
                    $config['thumbnail_sizes'] ?? [],
                    $config['disk']
                );
            }

            // Free memory
            unset($image);

            return $result;

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Resize image maintaining aspect ratio
     */
    private function resizeImage($image, int $maxWidth, int $maxHeight)
    {
        $width = $image->width();
        $height = $image->height();

        // Only resize if image is larger than max dimensions
        if ($width > $maxWidth || $height > $maxHeight) {
            $image->resize($maxWidth, $maxHeight);
        }

        return $image;
    }

    /**
     * Encode image to specific format
     */
    private function encodeImage($image, string $format, int $quality): string
    {
        return match($format) {
            'jpeg', 'jpg' => $image->toJpeg($quality),
            'png' => $image->toPng(),
            'gif' => $image->toGif(),
            'webp' => $image->toWebp($quality),
            default => $image->toJpeg($quality),
        };
    }

    /**
     * Enhanced thumbnail generation with better memory handling
     */
    private function generateThumbnails(UploadedFile $originalFile, string $path, string $filename, array $sizes, string $disk): array
    {
        $thumbnails = [];
        $manager = $this->getImageManager();

        foreach ($sizes as $size => $dimensions) {
            try {
                // Re-read from original file to avoid memory issues
                $thumbnailImage = $manager->read($originalFile);
                $thumbnailImage->cover($dimensions[0], $dimensions[1]);

                $thumbnailData = $this->encodeImage($thumbnailImage, 'jpeg', 85);
                
                $thumbnailFilename = $this->generateThumbnailFilename($filename, $size);
                $thumbnailPath = $path . '/' . $thumbnailFilename;

                $saved = Storage::disk($disk)->put($thumbnailPath, $thumbnailData);

                if ($saved) {
                    $thumbnails[$size] = [
                        'filename' => $thumbnailFilename,
                        'path' => $thumbnailPath,
                        'url' => Storage::disk($disk)->url($thumbnailPath),
                        'width' => $dimensions[0],
                        'height' => $dimensions[1],
                        'size' => Storage::disk($disk)->size($thumbnailPath),
                    ];
                }

                // Free memory
                unset($thumbnailImage);
                
            } catch (Exception $e) {
                throw $e;
            }
        }

        return $thumbnails;
    }

    /**
     * Generate unique filename with collision detection
     */
    private function generateUniqueFilename(UploadedFile $file, string $format, string $disk = 'public', string $path = ''): string
    {
        $extension = $format === 'original' ? $file->getClientOriginalExtension() : $format;
        
        do {
            $name = Str::random(40);
            $filename = $name . '.' . $extension;
            $fullPath = $path ? $path . '/' . $filename : $filename;
        } while (Storage::disk($disk)->exists($fullPath));
        
        return $filename;
    }

    /**
     * Generate thumbnail filename
     */
    private function generateThumbnailFilename(string $filename, string $size): string
    {
        $pathInfo = pathinfo($filename);
        return $pathInfo['filename'] . '_' . $size . '.' . $pathInfo['extension'];
    }

    /**
     * Batch delete with better error handling
     */
    public function deleteImage(string $path, string $disk = 'public', array $thumbnails = []): array
    {
        $results = [
            'main_image' => false,
            'thumbnails' => [],
            'errors' => []
        ];

        try {
            // Delete main image
            if (Storage::disk($disk)->exists($path)) {
                $results['main_image'] = Storage::disk($disk)->delete($path);
            } else {
                $results['errors'][] = 'Main image file not found';
            }

            // Delete thumbnails
            foreach ($thumbnails as $size => $thumbnail) {
                if (isset($thumbnail['path'])) {
                    if (Storage::disk($disk)->exists($thumbnail['path'])) {
                        $results['thumbnails'][$size] = Storage::disk($disk)->delete($thumbnail['path']);
                    } else {
                        $results['errors'][] = "Thumbnail '{$size}' not found";
                    }
                }
            }

            return [
                'success' => empty($results['errors']),
                'details' => $results
            ];

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get image information
     */
    public function getImageInfo(string $path, string $disk = 'public'): array
    {
        try {
            if (!Storage::disk($disk)->exists($path)) {
                return ['success' => false, 'error' => 'Image not found'];
            }

            $manager = $this->getImageManager();
            $image = $manager->read(Storage::disk($disk)->path($path));

            return [
                'success' => true,
                'width' => $image->width(),
                'height' => $image->height(),
                'size' => Storage::disk($disk)->size($path),
                'mime' => mime_content_type(Storage::disk($disk)->path($path)),
                'url' => Storage::disk($disk)->url($path),
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Enhanced crop method with validation
     */
    public function cropImage(string $path, int $x, int $y, int $width, int $height, string $disk = 'public'): array
    {
        try {
            if (!Storage::disk($disk)->exists($path)) {
                return ['success' => false, 'error' => 'Image file not found'];
            }

            $manager = $this->getImageManager();
            $image = $manager->read(Storage::disk($disk)->path($path));

            // Validate crop dimensions
            if ($x < 0 || $y < 0 || $width <= 0 || $height <= 0) {
                return ['success' => false, 'error' => 'Invalid crop dimensions'];
            }

            if (($x + $width) > $image->width() || ($y + $height) > $image->height()) {
                return ['success' => false, 'error' => 'Crop area exceeds image boundaries'];
            }

            $image->crop($width, $height, $x, $y);
            $imageData = $image->toJpeg(85);

            // Generate new filename
            $pathInfo = pathinfo($path);
            $newFilename = $pathInfo['filename'] . '_cropped_' . time() . '.jpg';
            $newPath = $pathInfo['dirname'] . '/' . $newFilename;

            Storage::disk($disk)->put($newPath, $imageData);

            return [
                'success' => true,
                'path' => $newPath,
                'url' => Storage::disk($disk)->url($newPath),
                'width' => $width,
                'height' => $height,
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Add watermark to image
     */
    public function addWatermark(string $path, string $watermarkPath, string $position = 'bottom-right', string $disk = 'public'): array
    {
        try {
            $manager = $this->getImageManager();
            $image = $manager->read(Storage::disk($disk)->path($path));
            $watermark = $manager->read(Storage::disk($disk)->path($watermarkPath));

            // Resize watermark to 20% of original image
            $watermark->resize($image->width() * 0.2, $image->height() * 0.2);

            // Place watermark
            $image->place($watermark, $position);

            $imageData = $image->toJpeg(85);

            $filename = $this->generateUniqueFilename(new \Illuminate\Http\UploadedFile(
                Storage::disk($disk)->path($path),
                basename($path)
            ), 'jpeg');

            $newPath = dirname($path) . '/' . $filename;
            Storage::disk($disk)->put($newPath, $imageData);

            return [
                'success' => true,
                'path' => $newPath,
                'url' => Storage::disk($disk)->url($newPath),
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }
} 