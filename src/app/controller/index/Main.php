<?php

declare(strict_types=1);

namespace app\controller\index;

use app\IconPack;
use InvalidArgumentException;
use nova\framework\http\Response;
use nova\framework\route\Controller;
use nova\plugin\captcha\Captcha;
use nova\plugin\security\IpAddress;
use nova\plugin\tpl\ViewResponse;

class Main extends Controller
{
    private const int MAX_UPLOAD_BYTES = 5 * 1024 * 1024;
    private const int MAX_CUSTOM_SIZES = 20;
    private const int ZIP_TTL = 3600;
    private const string ZIP_DIR = ROOT_PATH . '/runtime/icon';

    public function index(): Response
    {
        $view = new ViewResponse();
        $view->init($this->request->isPjax() ? '' : 'layout', [
            'title' => '图标工场',
            'platforms' => IconPack::PLATFORMS,
        ]);
        return $view->asTpl('index');
    }

    public function captcha(): Response
    {
        return (new Captcha())->create();
    }

    public function generate(): Response
    {
        $code = $this->request->post('captcha', 0);
        // 插件在 session 无验证码时以 0 比较，而它生成的答案永远不为 0，必须拒绝 0 防止绕过
        if (!Captcha::verify($code) || $code === 0) {
            IpAddress::load($this->request->getClientIP())->registerFailure(time());
            return $this->fail('验证码错误，请重试');
        }

        $platforms = array_values(array_intersect(
            array_keys(IconPack::PLATFORMS),
            explode(',', $this->request->post('platforms', ''))
        ));
        $sizes = array_slice(array_values(array_unique(array_filter(
            array_map('intval', preg_split('/[\s,，]+/', $this->request->post('sizes', ''))),
            fn (int $px) => $px >= 1 && $px <= IconPack::MASTER
        ))), 0, self::MAX_CUSTOM_SIZES);
        if ($platforms === [] && $sizes === []) {
            return $this->fail('请至少选择一个平台或填写自定义尺寸');
        }

        $bg = $this->request->post('bg', '#ffffff');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $bg)) {
            return $this->fail('背景色格式错误');
        }
        $radius = max(0, min(50, $this->request->post('radius', 0)));

        $file = $this->request->file('image');
        if ($file === null || $file->error !== UPLOAD_ERR_OK) {
            return $this->fail('请上传图片');
        }
        if ($file->size > self::MAX_UPLOAD_BYTES) {
            return $this->fail('图片不能超过 5MB');
        }

        try {
            $master = IconPack::load($file->tmp_name);
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage());
        }

        if (!is_dir(self::ZIP_DIR)) {
            mkdir(self::ZIP_DIR, 0777, true);
        }
        foreach (glob(self::ZIP_DIR . '/*.zip') as $old) {
            if (filemtime($old) < time() - self::ZIP_TTL) {
                unlink($old);
            }
        }

        $id = bin2hex(random_bytes(16));
        IconPack::zip(IconPack::build($master, $platforms, $bg, $radius, $sizes), self::ZIP_DIR . "/$id.zip");
        return Response::asJson(['code' => 200, 'msg' => '生成成功', 'data' => ['url' => "/download/$id"]]);
    }

    public function download(string $id): Response
    {
        if (!preg_match('/^[0-9a-f]{32}$/', $id)) {
            return Response::asText('Not Found', [], 404);
        }
        return Response::asFile(self::ZIP_DIR . "/$id.zip", 'icons.zip');
    }

    private function fail(string $msg): Response
    {
        return Response::asJson(['code' => 400, 'msg' => $msg]);
    }
}
