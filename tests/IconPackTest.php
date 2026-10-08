<?php

namespace tests;

use app\IconPack;
use InvalidArgumentException;
use nova\commands\test\TestCase;

class IconPackTest extends TestCase
{
    public function test()
    {
        // 非正方形 + 透明左半边，覆盖裁剪、铺底色、透明保留三种路径
        $src = imagecreatetruecolor(1200, 800);
        imagealphablending($src, false);
        imagesavealpha($src, true);
        imagefill($src, 0, 0, imagecolorallocatealpha($src, 255, 0, 0, 0));
        imagefilledrectangle($src, 0, 0, 599, 799, imagecolorallocatealpha($src, 0, 0, 0, 127));
        $path = tempnam(sys_get_temp_dir(), 'icon') . '.png';
        imagepng($src, $path);

        $master = IconPack::load($path);
        $this->checkInt(imagesx($master), IconPack::MASTER);
        $this->checkInt(imagesy($master), IconPack::MASTER);

        $files = IconPack::build($master, array_keys(IconPack::PLATFORMS), '#00ff00', 50, [64, 300]);

        $ios = json_decode($files['ios/AppIcon.appiconset/Contents.json'], true);
        $this->checkInt(count($ios['images']), 18);
        foreach ($ios['images'] as $entry) {
            $this->checkBool(isset($files['ios/AppIcon.appiconset/' . $entry['filename']]), true);
        }
        $this->checkPng($files['ios/AppIcon.appiconset/icon-ipad-83.5@2x.png'], 167, false);
        // App Store 拒收带 alpha 的 1024 图标
        $this->checkPng($files['ios/AppIcon.appiconset/icon-ios-marketing-1024@1x.png'], 1024, false);
        $this->checkPng($files['macos/AppIcon.appiconset/icon-mac-512@2x.png'], 1024, true);
        $this->checkPng($files['android/mipmap-xxxhdpi/ic_launcher.png'], 192, true);
        $this->checkPng($files['web/apple-touch-icon.png'], 180, false);
        $this->checkPng($files['custom/300x300.png'], 300, true);

        $watch = json_decode($files['watchos/AppIcon.appiconset/Contents.json'], true);
        $this->checkString($watch['images'][1]['size'], '27.5x27.5');
        $this->checkString($watch['images'][1]['subtype'], '42mm');

        // 透明区在 iOS 图标上被背景色填充
        $iosImg = imagecreatefromstring($files['ios/AppIcon.appiconset/icon-ios-marketing-1024@1x.png']);
        $this->checkInt(imagecolorat($iosImg, 10, 512) & 0xFFFFFF, 0x00FF00);

        // 50% 圆角 = 圆：角落全透明、中心不透明、边缘为半透明过渡
        $android = imagecreatefromstring($files['android/playstore-icon.png']);
        $this->checkInt((imagecolorat($android, 511, 0) >> 24) & 0x7F, 127);
        $this->checkInt((imagecolorat($android, 400, 256) >> 24) & 0x7F, 0);
        $alphas = [];
        for ($x = 300; $x < 420; $x++) {
            $alphas[] = (imagecolorat($android, $x, 20) >> 24) & 0x7F;
        }
        $this->checkBool(count(array_filter($alphas, fn ($a) => $a > 0 && $a < 127)) > 0, true);

        $ico = $files['web/favicon.ico'];
        $head = unpack('vreserved/vtype/vcount', $ico);
        $this->checkInt($head['type'], 1);
        $this->checkInt($head['count'], 3);
        $first = unpack('Cw/Ch/Cc/Cr/vp/vb/Vlen/Voffset', $ico, 6);
        $this->checkString(substr($ico, $first['offset'], 8), "\x89PNG\r\n\x1a\n");

        $zipPath = sys_get_temp_dir() . '/icon-test.zip';
        IconPack::zip($files, $zipPath);
        $zip = new \ZipArchive();
        $zip->open($zipPath);
        $this->checkInt($zip->numFiles, count($files));
        $zip->close();

        file_put_contents($path, 'not an image');
        try {
            IconPack::load($path);
            $this->checkBool(false, true);
        } catch (InvalidArgumentException $e) {
            $this->checkString($e->getMessage(), '仅支持 PNG / JPG / WebP / GIF / BMP 图片');
        }

        unlink($path);
        unlink($zipPath);
    }

    private function checkPng(string $data, int $side, bool $alpha): void
    {
        $info = getimagesizefromstring($data);
        $this->checkInt($info[0], $side);
        $this->checkInt($info[1], $side);
        // PNG IHDR color type：6 = RGBA，2 = RGB
        $this->checkInt(ord($data[25]), $alpha ? 6 : 2);
    }
}
