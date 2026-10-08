<?php

declare(strict_types=1);

namespace app;

use GdImage;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

class IconPack
{
    public const int MASTER = 1024;
    public const int MAX_SOURCE_SIDE = 4096;

    private const array SOURCE_TYPES = [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP, IMAGETYPE_GIF, IMAGETYPE_BMP];

    /**
     * variant：plain 保留透明，opaque 铺满背景色且不带 alpha 通道（App Store 要求），rounded 按圆角裁透明。
     * files 行：[路径, 像素, 可选 variant]；apple 行：[pt, scale, idiom, Contents.json 附加字段]。
     */
    public const array PLATFORMS = [
        'ios' => [
            'label' => 'iOS',
            'variant' => 'opaque',
            'files' => [],
            'apple' => [
                [20, 2, 'iphone', []], [20, 3, 'iphone', []],
                [29, 2, 'iphone', []], [29, 3, 'iphone', []],
                [40, 2, 'iphone', []], [40, 3, 'iphone', []],
                [60, 2, 'iphone', []], [60, 3, 'iphone', []],
                [20, 1, 'ipad', []], [20, 2, 'ipad', []],
                [29, 1, 'ipad', []], [29, 2, 'ipad', []],
                [40, 1, 'ipad', []], [40, 2, 'ipad', []],
                [76, 1, 'ipad', []], [76, 2, 'ipad', []],
                [83.5, 2, 'ipad', []],
                [1024, 1, 'ios-marketing', []],
            ],
        ],
        'android' => [
            'label' => 'Android',
            'variant' => 'rounded',
            'files' => [
                ['mipmap-mdpi/ic_launcher.png', 48],
                ['mipmap-hdpi/ic_launcher.png', 72],
                ['mipmap-xhdpi/ic_launcher.png', 96],
                ['mipmap-xxhdpi/ic_launcher.png', 144],
                ['mipmap-xxxhdpi/ic_launcher.png', 192],
                ['playstore-icon.png', 512],
            ],
            'apple' => [],
        ],
        'macos' => [
            'label' => 'macOS',
            'variant' => 'plain',
            'files' => [],
            'apple' => [
                [16, 1, 'mac', []], [16, 2, 'mac', []],
                [32, 1, 'mac', []], [32, 2, 'mac', []],
                [128, 1, 'mac', []], [128, 2, 'mac', []],
                [256, 1, 'mac', []], [256, 2, 'mac', []],
                [512, 1, 'mac', []], [512, 2, 'mac', []],
            ],
        ],
        'watchos' => [
            'label' => 'watchOS',
            'variant' => 'opaque',
            'files' => [],
            'apple' => [
                [24, 2, 'watch', ['role' => 'notificationCenter', 'subtype' => '38mm']],
                [27.5, 2, 'watch', ['role' => 'notificationCenter', 'subtype' => '42mm']],
                [29, 2, 'watch', ['role' => 'companionSettings']],
                [29, 3, 'watch', ['role' => 'companionSettings']],
                [40, 2, 'watch', ['role' => 'appLauncher', 'subtype' => '38mm']],
                [44, 2, 'watch', ['role' => 'appLauncher', 'subtype' => '40mm']],
                [50, 2, 'watch', ['role' => 'appLauncher', 'subtype' => '44mm']],
                [86, 2, 'watch', ['role' => 'quickLook', 'subtype' => '38mm']],
                [98, 2, 'watch', ['role' => 'quickLook', 'subtype' => '42mm']],
                [108, 2, 'watch', ['role' => 'quickLook', 'subtype' => '44mm']],
                [1024, 1, 'watch-marketing', []],
            ],
        ],
        'web' => [
            'label' => 'Web / PWA',
            'variant' => 'rounded',
            'files' => [
                ['favicon-16x16.png', 16],
                ['favicon-32x32.png', 32],
                ['apple-touch-icon.png', 180, 'opaque'],
                ['android-chrome-192x192.png', 192],
                ['android-chrome-512x512.png', 512],
            ],
            'apple' => [],
        ],
    ];

    /**
     * 读取上传图片，居中裁成正方形并缩放到 MASTER 尺寸。
     * @throws InvalidArgumentException 图片不可用时，消息可直接展示给用户
     */
    public static function load(string $path): GdImage
    {
        $info = getimagesize($path);
        if ($info === false || !in_array($info[2], self::SOURCE_TYPES, true)) {
            throw new InvalidArgumentException('仅支持 PNG / JPG / WebP / GIF / BMP 图片');
        }
        [$w, $h] = $info;
        if ($w > self::MAX_SOURCE_SIDE || $h > self::MAX_SOURCE_SIDE) {
            throw new InvalidArgumentException('图片边长不能超过 ' . self::MAX_SOURCE_SIDE . 'px');
        }
        $src = imagecreatefromstring(file_get_contents($path));
        if ($src === false) {
            throw new InvalidArgumentException('图片已损坏，无法解析');
        }

        $side = min($w, $h);
        $master = self::canvas(self::MASTER);
        imagecopyresampled($master, $src, 0, 0, intdiv($w - $side, 2), intdiv($h - $side, 2), self::MASTER, self::MASTER, $side, $side);
        return $master;
    }

    /**
     * @param  list<string> $platforms PLATFORMS 的 key
     * @param  list<int>    $sizes     自定义尺寸
     * @return array<string, string>   zip 内路径 => 文件内容
     */
    public static function build(GdImage $master, array $platforms, string $bg, int $radiusPercent, array $sizes): array
    {
        $masters = [
            'plain' => $master,
            'opaque' => self::flatten($master, $bg),
            'rounded' => self::round($master, intdiv($radiusPercent * self::MASTER, 100)),
        ];
        $png = fn (int $px, string $variant): string => self::png($masters[$variant], $px, $variant !== 'opaque');

        $files = [];
        foreach ($platforms as $key) {
            $spec = self::PLATFORMS[$key];
            foreach ($spec['files'] as $row) {
                $files["$key/$row[0]"] = $png($row[1], $row[2] ?? $spec['variant']);
            }
            $files += self::apple("$key/AppIcon.appiconset", $spec['apple'], fn (int $px) => $png($px, $spec['variant']));
        }

        if (in_array('web', $platforms, true)) {
            $files['web/favicon.ico'] = self::ico([16 => $png(16, 'rounded'), 32 => $png(32, 'rounded'), 48 => $png(48, 'rounded')]);
            $files['web/site.webmanifest'] = json_encode([
                'icons' => [
                    ['src' => 'android-chrome-192x192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                    ['src' => 'android-chrome-512x512.png', 'sizes' => '512x512', 'type' => 'image/png'],
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        foreach ($sizes as $px) {
            $files["custom/{$px}x$px.png"] = $png($px, 'plain');
        }
        return $files;
    }

    /** @param array<string, string> $files */
    public static function zip(array $files, string $path): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("无法创建压缩包：$path");
        }
        foreach ($files as $name => $data) {
            $zip->addFromString($name, $data);
        }
        $zip->close();
    }

    private static function canvas(int $side): GdImage
    {
        $img = imagecreatetruecolor($side, $side);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
        return $img;
    }

    private static function png(GdImage $master, int $px, bool $alpha): string
    {
        $img = self::canvas($px);
        imagesavealpha($img, $alpha);
        imagecopyresampled($img, $master, 0, 0, 0, 0, $px, $px, self::MASTER, self::MASTER);
        ob_start();
        imagepng($img);
        return ob_get_clean();
    }

    private static function flatten(GdImage $src, string $hex): GdImage
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
        $img = imagecreatetruecolor(self::MASTER, self::MASTER);
        imagefill($img, 0, 0, imagecolorallocate($img, $r, $g, $b));
        imagealphablending($img, true);
        imagecopy($img, $src, 0, 0, 0, 0, self::MASTER, self::MASTER);
        return $img;
    }

    /**
     * 圆角遮罩：每行只处理角上的透明区和抗锯齿边缘像素，复杂度 O(r)。
     */
    private static function round(GdImage $src, int $r): GdImage
    {
        $n = self::MASTER;
        $img = self::canvas($n);
        imagecopy($img, $src, 0, 0, 0, 0, $n, $n);
        $clear = imagecolorallocatealpha($img, 0, 0, 0, 127);

        for ($y = 0; $y < $r; $y++) {
            $dy = $r - $y - 0.5;
            $outside = (int) floor($r - 0.5 - sqrt(($r + 0.5) ** 2 - $dy * $dy));
            // GD 会交换反向端点，-1 时不能画，否则误清第 0 列
            if ($outside >= 0) {
                foreach ([$y, $n - 1 - $y] as $row) {
                    imageline($img, 0, $row, $outside, $row, $clear);
                    imageline($img, $n - 1 - $outside, $row, $n - 1, $row, $clear);
                }
            }
            for ($x = max(0, $outside + 1); $x < $r; $x++) {
                $dx = $r - $x - 0.5;
                $coverage = $r + 0.5 - sqrt($dx * $dx + $dy * $dy);
                if ($coverage >= 1) {
                    break;
                }
                foreach ([[$x, $y], [$n - 1 - $x, $y], [$x, $n - 1 - $y], [$n - 1 - $x, $n - 1 - $y]] as [$px, $py]) {
                    $c = imagecolorat($img, $px, $py);
                    $a = 127 - (int) round((127 - (($c >> 24) & 0x7F)) * $coverage);
                    imagesetpixel($img, $px, $py, ($c & 0xFFFFFF) | ($a << 24));
                }
            }
        }
        return $img;
    }

    /**
     * @param  list<array{0: int|float, 1: int, 2: string, 3: array<string, string>}> $images
     * @param  callable(int): string $png
     * @return array<string, string>
     */
    private static function apple(string $dir, array $images, callable $png): array
    {
        if ($images === []) {
            return [];
        }
        $files = [];
        $entries = [];
        foreach ($images as [$pt, $scale, $idiom, $extra]) {
            $name = "icon-$idiom-{$pt}@{$scale}x.png";
            $files["$dir/$name"] = $png((int) round($pt * $scale));
            $entries[] = ['size' => "{$pt}x$pt", 'idiom' => $idiom, 'filename' => $name, 'scale' => "{$scale}x"] + $extra;
        }
        $files["$dir/Contents.json"] = json_encode(
            ['images' => $entries, 'info' => ['version' => 1, 'author' => 'xcode']],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
        return $files;
    }

    /** @param array<int, string> $pngs 像素 => PNG 数据；ICO 允许直接内嵌 PNG */
    private static function ico(array $pngs): string
    {
        $head = pack('vvv', 0, 1, count($pngs));
        $body = '';
        $offset = 6 + 16 * count($pngs);
        foreach ($pngs as $px => $data) {
            $head .= pack('CCCCvvVV', $px % 256, $px % 256, 0, 0, 1, 32, strlen($data), $offset + strlen($body));
            $body .= $data;
        }
        return $head . $body;
    }
}
