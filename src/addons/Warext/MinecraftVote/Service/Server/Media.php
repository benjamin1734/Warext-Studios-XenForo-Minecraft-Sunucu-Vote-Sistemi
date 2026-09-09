<?php

namespace Warext\MinecraftVote\Service\Server;

use Warext\MinecraftVote\Entity\Server;
use XF\App;
use XF\Http\Upload;
use XF\PrintableException;
use XF\Service\AbstractService;

class Media extends AbstractService
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function validateUpload(?Upload $upload, string $type): void
    {
        if (!$upload)
        {
            return;
        }

        $config = $this->getConfig($type);
        $upload->requireImage()->setMaxFileSize($config['max_upload']);
        if (!$upload->isValid($errors))
        {
            throw new PrintableException('Yüklenen dosya güvenli ve geçerli bir görsel olmalıdır.');
        }

        $extension = $this->extensionFromImageType((int)$upload->getImageType());
        if (!in_array($extension, $config['extensions'], true))
        {
            throw new PrintableException($config['format_error']);
        }

        $width = (int)$upload->getImageWidth();
        $height = (int)$upload->getImageHeight();
        if ($width < $config['min_width'] || $height < $config['min_height'])
        {
            throw new PrintableException($config['size_error']);
        }

        if ($width > 10000 || $height > 10000 || ($width * $height) > 40000000)
        {
            throw new PrintableException('Görsel çözünürlüğü işlenemeyecek kadar yüksek.');
        }

        if ($type === 'animated_banner' && !class_exists('Imagick'))
        {
            throw new PrintableException('Hareketli GIF otomatik boyutlandırması için sunucuda Imagick PHP eklentisi etkin olmalıdır.');
        }
    }

    public function store(Server $server, Upload $upload, string $type): void
    {
        $this->validateUpload($upload, $type);
        $config = $this->getConfig($type);
        [$processedFile, $extension] = $this->processUpload($upload, $type, $config);
        $field = $config['field'];
        $oldPath = (string)$server->{$field};
        $relativePath = 'warext-minecraft/server/' . $server->server_id . '/' . $type . '.' . $extension;

        try
        {
            \XF\Util\File::copyFileToAbstractedPath($processedFile, 'data://' . $relativePath);
        }
        finally
        {
            @unlink($processedFile);
        }

        if ($oldPath !== '' && $oldPath !== $relativePath)
        {
            \XF\Util\File::deleteFromAbstractedPath('data://' . ltrim($oldPath, '/'));
        }

        $server->{$field} = $relativePath;
        $server->save();
    }

    public function remove(Server $server, string $type): void
    {
        $config = $this->getConfig($type);
        $field = $config['field'];
        $path = (string)$server->{$field};
        if ($path !== '')
        {
            \XF\Util\File::deleteFromAbstractedPath('data://' . ltrim($path, '/'));
            $server->{$field} = '';
            $server->save();
        }
    }

    protected function processUpload(Upload $upload, string $type, array $config): array
    {
        if ($type === 'animated_banner')
        {
            return $this->processAnimatedGif($upload->getTempFile(), $config);
        }

        return $this->processStaticImage(
            $upload->getTempFile(),
            (int)$upload->getImageType(),
            $config
        );
    }

    protected function processStaticImage(string $sourceFile, int $imageType, array $config): array
    {
        if (function_exists('imagecreatetruecolor'))
        {
            $result = $this->processStaticWithGd($sourceFile, $imageType, $config);
            if ($result)
            {
                return $result;
            }
        }

        if (class_exists('Imagick'))
        {
            return $this->processStaticWithImagick($sourceFile, $config);
        }

        throw new PrintableException('Görsel otomatik boyutlandırması için GD veya Imagick PHP eklentisi etkin olmalıdır.');
    }

    protected function processStaticWithGd(string $sourceFile, int $imageType, array $config): ?array
    {
        $source = null;
        if ($imageType === IMAGETYPE_JPEG && function_exists('imagecreatefromjpeg'))
        {
            $source = @imagecreatefromjpeg($sourceFile);
        }
        elseif ($imageType === IMAGETYPE_PNG && function_exists('imagecreatefrompng'))
        {
            $source = @imagecreatefrompng($sourceFile);
        }
        elseif (defined('IMAGETYPE_WEBP') && $imageType === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp'))
        {
            $source = @imagecreatefromwebp($sourceFile);
        }

        if (!$source)
        {
            return null;
        }

        $targetWidth = $config['width'];
        $targetHeight = $config['height'];
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        [$srcX, $srcY, $cropWidth, $cropHeight] = $this->calculateCrop(
            $sourceWidth,
            $sourceHeight,
            $targetWidth,
            $targetHeight
        );

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        $useWebp = function_exists('imagewebp');
        if ($useWebp)
        {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
            imagefill($target, 0, 0, $transparent);
            imagealphablending($target, true);
        }
        else
        {
            $background = imagecolorallocate($target, 255, 255, 255);
            imagefill($target, 0, 0, $background);
        }

        $resampled = imagecopyresampled(
            $target,
            $source,
            0,
            0,
            $srcX,
            $srcY,
            $targetWidth,
            $targetHeight,
            $cropWidth,
            $cropHeight
        );
        imagedestroy($source);

        if (!$resampled)
        {
            imagedestroy($target);
            throw new PrintableException('Görsel yeniden boyutlandırılamadı.');
        }

        $tempFile = $this->createTempFile();
        $extension = $useWebp ? 'webp' : 'jpg';
        $saved = $useWebp
            ? imagewebp($target, $tempFile, 82)
            : imagejpeg($target, $tempFile, 84);
        imagedestroy($target);

        if (!$saved)
        {
            @unlink($tempFile);
            throw new PrintableException('Görsel optimize edilerek kaydedilemedi.');
        }

        $this->assertProcessedFile($tempFile, $config['max_output']);
        return [$tempFile, $extension];
    }

    protected function processStaticWithImagick(string $sourceFile, array $config): array
    {
        try
        {
            $image = new \Imagick($sourceFile);
            $image->setIteratorIndex(0);
            if (method_exists($image, 'autoOrientImage'))
            {
                $image->autoOrientImage();
            }
            $image->cropThumbnailImage($config['width'], $config['height']);
            $image->stripImage();

            $webpSupported = count(\Imagick::queryFormats('WEBP')) > 0;
            $extension = $webpSupported ? 'webp' : 'jpg';
            if (!$webpSupported)
            {
                $image->setImageBackgroundColor('white');
                $image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
            }
            $image->setImageFormat($extension);
            $image->setImageCompressionQuality($webpSupported ? 82 : 84);

            $tempFile = $this->createTempFile();
            if (!$image->writeImage($tempFile))
            {
                @unlink($tempFile);
                throw new PrintableException('Görsel optimize edilerek kaydedilemedi.');
            }
            $image->clear();
        }
        catch (PrintableException $e)
        {
            throw $e;
        }
        catch (\Throwable $e)
        {
            throw new PrintableException('Görsel işlenirken bir hata oluştu.');
        }

        $this->assertProcessedFile($tempFile, $config['max_output']);
        return [$tempFile, $extension];
    }

    protected function processAnimatedGif(string $sourceFile, array $config): array
    {
        try
        {
            $gif = new \Imagick($sourceFile);
            $frameCount = $gif->getNumberImages();
            if ($frameCount < 1 || $frameCount > 240)
            {
                $gif->clear();
                throw new PrintableException('Hareketli banner en fazla 240 kare içerebilir.');
            }

            $iterations = $gif->getImageIterations();
            $gif = $gif->coalesceImages();
            foreach ($gif as $frame)
            {
                $frame->cropThumbnailImage($config['width'], $config['height']);
                $frame->setImagePage($config['width'], $config['height'], 0, 0);
                $frame->stripImage();
                $frame->setImageFormat('gif');
                $frame->setImageCompressionQuality(75);
                $frame->quantizeImage(96, \Imagick::COLORSPACE_RGB, 0, false, false);
            }

            $optimized = $gif->deconstructImages();
            $optimized->setImageIterations($iterations);
            $tempFile = $this->createTempFile();
            if (!$optimized->writeImages($tempFile, true))
            {
                @unlink($tempFile);
                $optimized->clear();
                $gif->clear();
                throw new PrintableException('Hareketli banner optimize edilerek kaydedilemedi.');
            }
            $optimized->clear();
            $gif->clear();
        }
        catch (PrintableException $e)
        {
            throw $e;
        }
        catch (\Throwable $e)
        {
            throw new PrintableException('Hareketli banner işlenirken bir hata oluştu.');
        }

        $this->assertProcessedFile($tempFile, $config['max_output']);
        return [$tempFile, 'gif'];
    }

    protected function calculateCrop(int $sourceWidth, int $sourceHeight, int $targetWidth, int $targetHeight): array
    {
        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = $targetWidth / $targetHeight;

        if ($sourceRatio > $targetRatio)
        {
            $cropHeight = $sourceHeight;
            $cropWidth = (int)round($sourceHeight * $targetRatio);
            $srcX = (int)floor(($sourceWidth - $cropWidth) / 2);
            $srcY = 0;
        }
        else
        {
            $cropWidth = $sourceWidth;
            $cropHeight = (int)round($sourceWidth / $targetRatio);
            $srcX = 0;
            $srcY = (int)floor(($sourceHeight - $cropHeight) / 2);
        }

        return [$srcX, $srcY, $cropWidth, $cropHeight];
    }

    protected function assertProcessedFile(string $path, int $maxBytes): void
    {
        clearstatcache(true, $path);
        $size = @filesize($path);
        if (!$size || $size > $maxBytes)
        {
            @unlink($path);
            throw new PrintableException('Görsel optimize edildikten sonra izin verilen dosya boyutunu aşıyor.');
        }
    }

    protected function createTempFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'warext_mc_');
        if (!$path)
        {
            throw new PrintableException('Görsel işleme için geçici dosya oluşturulamadı.');
        }

        return $path;
    }

    protected function getConfig(string $type): array
    {
        $configs = [
            'banner' => [
                'field' => 'banner_path',
                'max_upload' => 12 * 1024 * 1024,
                'max_output' => 512 * 1024,
                'extensions' => ['jpg', 'png', 'webp'],
                'width' => 300,
                'height' => 100,
                'min_width' => 150,
                'min_height' => 50,
                'format_error' => 'Statik banner JPG, PNG veya WebP olmalıdır.',
                'size_error' => 'Liste bannerı en az 150×50 piksel olmalıdır.'
            ],
            'animated_banner' => [
                'field' => 'animated_banner_path',
                'max_upload' => 20 * 1024 * 1024,
                'max_output' => 6 * 1024 * 1024,
                'extensions' => ['gif'],
                'width' => 300,
                'height' => 100,
                'min_width' => 150,
                'min_height' => 50,
                'format_error' => 'Hareketli banner GIF olmalıdır.',
                'size_error' => 'Hareketli banner en az 150×50 piksel olmalıdır.'
            ],
            'cover' => [
                'field' => 'cover_path',
                'max_upload' => 15 * 1024 * 1024,
                'max_output' => 2 * 1024 * 1024,
                'extensions' => ['jpg', 'png', 'webp'],
                'width' => 1200,
                'height' => 400,
                'min_width' => 600,
                'min_height' => 200,
                'format_error' => 'Kapak görseli JPG, PNG veya WebP olmalıdır.',
                'size_error' => 'Kapak görseli en az 600×200 piksel olmalıdır.'
            ]
        ];

        if (!isset($configs[$type]))
        {
            throw new PrintableException('Geçersiz sunucu medya türü.');
        }

        return $configs[$type];
    }

    protected function extensionFromImageType(int $imageType): string
    {
        if ($imageType === IMAGETYPE_JPEG)
        {
            return 'jpg';
        }
        if ($imageType === IMAGETYPE_PNG)
        {
            return 'png';
        }
        if ($imageType === IMAGETYPE_GIF)
        {
            return 'gif';
        }
        if (defined('IMAGETYPE_WEBP') && $imageType === IMAGETYPE_WEBP)
        {
            return 'webp';
        }

        return '';
    }
}
