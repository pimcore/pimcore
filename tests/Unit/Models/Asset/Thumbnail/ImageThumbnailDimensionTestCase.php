<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Unit\Models\Asset\Thumbnail;

use Doctrine\Persistence\ConnectionRegistry;
use Imagick;
use ImagickException;
use ImagickPixel;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Pimcore as PimcoreRuntime;
use Pimcore\Config as PimcoreConfig;
use Pimcore\Helper\LongRunningHelper;
use Pimcore\Image\Adapter;
use Pimcore\Image\Adapter\GD;
use Pimcore\Image\Adapter\Imagick as ImagickAdapter;
use Pimcore\Image\AdapterInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Model\Asset\Image\Thumbnail\Processor;
use Pimcore\Model\Asset\Thumbnail\ImageThumbnailTrait;
use Pimcore\Tool\Storage;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Regression coverage for dimension estimation before thumbnail file access.
 *
 * The probe deliberately reports that a thumbnail exists.
 * Reliable estimates must therefore avoid both exists() and readDimensionsFromFile(), while configurations that cannot be reproduced exactly must use the file fallback exactly once.
 */
abstract class ImageThumbnailDimensionTestCase extends TestCase
{
    protected ?array $previousSystemConfiguration;

    protected ?KernelInterface $previousKernel;

    /** @var list<string> */
    protected array $temporaryFiles = [];

    private Adapter $configuredImageAdapter;

    private ?FilesystemOperator $assetStorage = null;

    private ?FilesystemOperator $thumbnailStorage = null;

    protected function setUp(): void
    {
        parent::setUp();

        $systemConfiguration = new ReflectionProperty(PimcoreConfig::class, 'systemConfig');
        $this->previousSystemConfiguration = $systemConfiguration->getValue();
        $kernel = new ReflectionProperty(PimcoreRuntime::class, 'kernel');
        $this->previousKernel = $kernel->getValue();

        PimcoreConfig::setSystemConfiguration([
            'assets' => [
                'thumbnails' => [
                    'allowed_formats' => ['png', 'jpeg', 'webp', 'avif', 'tiff', 'print'],
                    'max_scaling_factor' => 10,
                ],
                'image' => [
                    'thumbnails' => [
                        'status_cache' => false,
                        'clip_auto_support' => false,
                        'max_srcset_dpi_factor' => 1,
                        'image_optimizers' => [
                            'enabled' => false,
                        ],
                    ],
                ],
            ],
        ]);
        $this->configuredImageAdapter = new GD();
        $this->installRuntimeServices();
    }

    protected function tearDown(): void
    {
        PimcoreConfig::setSystemConfiguration($this->previousSystemConfiguration);
        $kernel = new ReflectionProperty(PimcoreRuntime::class, 'kernel');
        $kernel->setValue(null, $this->previousKernel);

        foreach ($this->temporaryFiles as $temporaryFile) {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }

        parent::tearDown();
    }

    /**
     * @param class-string<Adapter> $adapterClass
     *
     * @return array{width: int, height: int}
     */
    protected function renderedDimensions(
        string $adapterClass,
        string $source,
        Image $asset,
        Config $config
    ): array {
        $adapter = new $adapterClass();
        if ($adapter->load($source, ['preserveColor' => true]) === false) {
            throw new RuntimeException($adapterClass . ' could not load ' . $source);
        }

        Processor::applyTransformations($adapter, $asset, $config, $config->getItems());

        $output = $this->temporaryPath('.png');
        $adapter->save($output, 'png');
        $imageSize = getimagesize($output);
        self::assertIsArray($imageSize);

        return [
            'width' => $imageSize[0],
            'height' => $imageSize[1],
        ];
    }

    /**
     * @return array<string, array{list<array{method: string, arguments: array<string, mixed>}>, float|null}>
     */
    protected function supportedRasterPipelineCases(): array
    {
        return [
            'scale by width' => [
                [['method' => 'scaleByWidth', 'arguments' => ['width' => 201]]],
                null,
            ],
            'scale by height' => [
                [['method' => 'scaleByHeight', 'arguments' => ['height' => 151]]],
                null,
            ],
            'contain' => [
                [['method' => 'contain', 'arguments' => ['width' => 121, 'height' => 101]]],
                null,
            ],
            'cover' => [
                [['method' => 'cover', 'arguments' => ['width' => 121, 'height' => 101]]],
                null,
            ],
            'crop' => [
                [['method' => 'crop', 'arguments' => ['x' => 17, 'y' => 19, 'width' => 121, 'height' => 101]]],
                null,
            ],
            'crop percent' => [
                [['method' => 'cropPercent', 'arguments' => ['width' => 51, 'height' => 41, 'x' => 7, 'y' => 9]]],
                null,
            ],
            'frame' => [
                [['method' => 'frame', 'arguments' => ['width' => 501, 'height' => 401]]],
                null,
            ],
            'fractional high resolution' => [
                [['method' => 'scaleByWidth', 'arguments' => ['width' => 201]]],
                1.5,
            ],
            'capped high resolution' => [
                [['method' => 'scaleByWidth', 'arguments' => ['width' => 300]]],
                2.0,
            ],
        ];
    }

    protected function requireImagickExtension(): void
    {
        if (!extension_loaded('imagick')) {
            self::markTestSkipped('Imagick is not installed.');
        }
    }

    protected function requireImagickFixtureSupport(string $source): void
    {
        $this->requireImagickExtension();

        $adapter = new ImagickAdapter();

        try {
            $loaded = $adapter->load($source, ['preserveColor' => true]);
        } catch (ImagickException $exception) {
            self::markTestSkipped('Imagick fixture delegate is unavailable: ' . $exception->getMessage());
        }

        if ($loaded === false) {
            self::markTestSkipped('Imagick fixture delegate is unavailable for ' . pathinfo($source, PATHINFO_EXTENSION));
        }
    }

    protected function createRasterFixture(int $width, int $height): string
    {
        $path = $this->temporaryPath('.png');
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 40, 120, 200);
        imagefill($image, 0, 0, $color);
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }

    protected function createWebRootRasterFixture(int $width, int $height): string
    {
        $path = $this->temporaryPath('.png', PIMCORE_WEB_ROOT);
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 220, 90, 40);
        imagefill($image, 0, 0, $color);
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }

    protected function createTiffFixture(int $width, int $height): string
    {
        $this->requireImagickExtension();

        $path = $this->temporaryPath('.tiff');

        try {
            $image = new Imagick();
            $image->newImage($width, $height, new ImagickPixel('#2878c8'));
            $image->setImageFormat('tiff');
            $image->writeImage($path);
            $image->clear();
        } catch (ImagickException $exception) {
            self::markTestSkipped('Imagick TIFF coder is unavailable: ' . $exception->getMessage());
        }

        return $path;
    }

    protected function createExifOrientedJpegFixture(int $width, int $height, int $orientation): string
    {
        $path = $this->temporaryPath('.jpg');
        $image = imagecreatetruecolor($width, $height);
        imagejpeg($image, $path, 90);
        imagedestroy($image);

        $jpeg = file_get_contents($path);
        self::assertIsString($jpeg);
        $tiffHeader = "MM\x00\x2A\x00\x00\x00\x08"
            . "\x00\x01"
            . "\x01\x12\x00\x03\x00\x00\x00\x01"
            . pack('n', $orientation) . "\x00\x00"
            . "\x00\x00\x00\x00";
        $payload = "Exif\x00\x00" . $tiffHeader;
        $segment = "\xFF\xE1" . pack('n', strlen($payload) + 2) . $payload;
        file_put_contents($path, substr($jpeg, 0, 2) . $segment . substr($jpeg, 2));

        return $path;
    }

    protected function createSvgFixture(int $width, int $height, string $extension = '.svg'): string
    {
        $path = $this->temporaryPath($extension);
        file_put_contents($path, sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">'
            . '<rect width="100%%" height="100%%" fill="#2878c8"/></svg>',
            $width,
            $height,
            $width,
            $height
        ));

        return $path;
    }

    protected function temporaryPath(string $extension, ?string $directory = null): string
    {
        $temporaryPath = tempnam($directory ?? sys_get_temp_dir(), 'pimcore-thumbnail-dimensions-');
        self::assertNotFalse($temporaryPath);
        $path = $temporaryPath . $extension;
        rename($temporaryPath, $path);
        $this->temporaryFiles[] = $path;

        return $path;
    }

    protected function installAssetStorage(FilesystemOperator $assetStorage): void
    {
        $this->installStorages($assetStorage, null);
    }

    protected function installStorages(
        ?FilesystemOperator $assetStorage,
        ?FilesystemOperator $thumbnailStorage
    ): void {
        $this->assetStorage = $assetStorage;
        $this->thumbnailStorage = $thumbnailStorage;
        $this->installRuntimeServices();
    }

    protected function installImageAdapter(Adapter $adapter): void
    {
        $this->configuredImageAdapter = $adapter;
        $this->installRuntimeServices();
    }

    private function installRuntimeServices(): void
    {
        $storages = [];
        if ($this->assetStorage !== null) {
            $storages['pimcore.asset.storage'] = $this->assetStorage;
        }
        if ($this->thumbnailStorage !== null) {
            $storages['pimcore.thumbnail.storage'] = $this->thumbnailStorage;
        }

        $locator = $this->createStub(ContainerInterface::class);
        $locator->method('get')
            ->willReturnCallback(static function (string $id) use ($storages): FilesystemOperator {
                if (!isset($storages[$id])) {
                    throw new RuntimeException('Unexpected storage service: ' . $id);
                }

                return $storages[$id];
            });
        $storage = new Storage($locator);
        $imageAdapter = $this->configuredImageAdapter;
        $logger = new NullLogger();
        $requestStack = new RequestStack();
        $longRunningHelper = new LongRunningHelper($this->createStub(ConnectionRegistry::class));

        $container = $this->createStub(\Symfony\Component\DependencyInjection\ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(static fn (string $id) => match ($id) {
                Storage::class => $storage,
                AdapterInterface::class => $imageAdapter,
                LongRunningHelper::class => $longRunningHelper,
                'monolog.logger.pimcore' => $logger,
                'request_stack' => $requestStack,
                default => null,
            });
        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getContainer')->willReturn($container);
        PimcoreRuntime::setKernel($kernel);
    }

    protected function image(?int $width, ?int $height, string $filename = 'source.jpg'): Image
    {
        $asset = new Image();
        $this->configureImage($asset, $width, $height, $filename);

        return $asset;
    }

    protected function countingImage(
        ?int $width,
        ?int $height,
        string $filename,
        ?string $streamPath = null,
        ?array $sourceDimensions = null
    ): CountingImage {
        $asset = new CountingImage($streamPath, $sourceDimensions);
        $this->configureImage($asset, $width, $height, $filename);

        return $asset;
    }

    protected function configureImage(Image $asset, ?int $width, ?int $height, string $filename): void
    {
        $asset->setFilename($filename);

        $customSettings = [];
        if ($width !== null) {
            $customSettings['imageWidth'] = $width;
        }
        if ($height !== null) {
            $customSettings['imageHeight'] = $height;
        }
        $asset->setCustomSettings($customSettings);
    }

    /**
     * @param list<array{method: string, arguments: array<string, mixed>}> $items
     */
    protected function config(array $items, ?float $highResolution = null): Config
    {
        $config = new Config();
        $config->setName('dimension-probe');
        $config->setItems($items);
        $config->setHighResolution($highResolution);

        return $config;
    }

    /**
     * @param array{width?: int, height?: int} $fileDimensions
     */
    protected function probe(Image $asset, Config $config, array $fileDimensions = ['width' => 901, 'height' => 701]): ImageThumbnailTraitProbe
    {
        return new ImageThumbnailTraitProbe($asset, $config, $fileDimensions);
    }

    protected function setStatusCacheEnabled(bool $enabled): void
    {
        $assetsConfig = PimcoreConfig::getSystemConfiguration('assets');
        $assetsConfig['image']['thumbnails']['status_cache'] = $enabled;
        PimcoreConfig::setSystemConfiguration($assetsConfig, 'assets');
    }
}

final class ImageThumbnailTraitProbe
{
    use ImageThumbnailTrait;

    public int $existsCalls = 0;

    public int $readDimensionsCalls = 0;

    /**
     * @param array{width?: int, height?: int} $fileDimensions
     */
    public function __construct(Asset $asset, Config $config, private readonly array $fileDimensions)
    {
        $this->asset = $asset;
        $this->config = $config;
    }

    public function getFilename(): string
    {
        return 'dimension-probe.jpg';
    }

    public function getPath(array $args = []): string
    {
        return 'dimension-probe.jpg';
    }

    public function generate(bool $deferredAllowed = true): void
    {
    }

    public function exists(): bool
    {
        ++$this->existsCalls;

        return true;
    }

    /**
     * @return array{width?: int, height?: int}
     */
    public function readDimensionsFromFile(): array
    {
        ++$this->readDimensionsCalls;

        return $this->fileDimensions;
    }
}

final class RealAssetImageThumbnailTraitProbe
{
    use ImageThumbnailTrait;

    public int $generationCalls = 0;

    public function __construct(Asset $asset, Config $config)
    {
        $this->asset = $asset;
        $this->config = $config;
    }

    public function generate(bool $deferredAllowed = true): void
    {
        ++$this->generationCalls;
        $this->pathReference = [
            'src' => $this->asset->getRealFullPath(),
            'type' => 'asset',
        ];
    }

    public function getPath(array $args = []): string
    {
        return $this->asset->getRealFullPath();
    }
}

final class RealThumbnailImageThumbnailTraitProbe
{
    use ImageThumbnailTrait;

    public int $generationCalls = 0;

    public function __construct(Asset $asset, Config $config, private readonly string $storagePath)
    {
        $this->asset = $asset;
        $this->config = $config;
    }

    public function generate(bool $deferredAllowed = true): void
    {
        ++$this->generationCalls;
        $this->pathReference = [
            'src' => $this->storagePath,
            'type' => 'thumbnail',
            'storagePath' => $this->storagePath,
        ];
    }

    public function getPath(array $args = []): string
    {
        return $this->storagePath;
    }
}

final class CountingImage extends Image
{
    public int $streamCalls = 0;

    public int $dimensionsFromFileCalls = 0;

    /**
     * @param array{width: int, height: int}|null $sourceDimensions
     */
    public function __construct(
        private readonly ?string $streamPath = null,
        private readonly ?array $sourceDimensions = null
    ) {
    }

    /**
     * @return resource|null
     */
    public function getStream()
    {
        ++$this->streamCalls;

        if ($this->streamPath === null) {
            return null;
        }

        $stream = fopen($this->streamPath, 'rb');

        return $stream === false ? null : $stream;
    }

    public function getDimensionsFromFile(string $path): ?array
    {
        ++$this->dimensionsFromFileCalls;

        return $this->sourceDimensions ?? parent::getDimensionsFromFile($path);
    }
}
