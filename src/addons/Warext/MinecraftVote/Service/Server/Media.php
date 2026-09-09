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
        $upload->requireImage()->setMaxFileSize($config['max']);
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
        if ($type === 'banner' || $type === 'animated_banner')
        {
            if ($width !== 468 || $height !== 60)
            {
                throw new PrintableException('Liste bannerı tam 468×60 piksel olmalıdır.');
            }
        }
        elseif ($width < 800 || $height < 300)
        {
            throw new PrintableException('Kapak görseli en az 800×300 piksel olmalıdır.');
        }
    }

    public function store(Server $server, Upload $upload, string $type): void
    {
        $this->validateUpload($upload, $type);
        $config = $this->getConfig($type);
        $extension = $this->extensionFromImageType((int)$upload->getImageType());
        $field = $config['field'];
        $oldPath = (string)$server->{$field};
        $relativePath = 'warext-minecraft/server/' . $server->server_id . '/' . $type . '.' . $extension;

        \XF\Util\File::copyFileToAbstractedPath(
            $upload->getTempFile(),
            'data://' . $relativePath
        );

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

    protected function getConfig(string $type): array
    {
        $configs = [
            'banner' => [
                'field' => 'banner_path',
                'max' => 10 * 1024 * 1024,
                'extensions' => ['jpg', 'png', 'webp'],
                'format_error' => 'Statik banner JPG, PNG veya WebP olmalıdır.'
            ],
            'animated_banner' => [
                'field' => 'animated_banner_path',
                'max' => 15 * 1024 * 1024,
                'extensions' => ['gif'],
                'format_error' => 'Hareketli banner GIF olmalıdır.'
            ],
            'cover' => [
                'field' => 'cover_path',
                'max' => 12 * 1024 * 1024,
                'extensions' => ['jpg', 'png', 'webp'],
                'format_error' => 'Kapak görseli JPG, PNG veya WebP olmalıdır.'
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
