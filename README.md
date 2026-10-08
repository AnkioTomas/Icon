# Icon 图标工场

> **上传一张图，一键生成全平台应用图标**

上传一张方形图片，自动生成 iOS、Android、macOS、watchOS、Web/PWA 所需的全部尺寸，打包成 zip 下载后可直接导入工程。基于 Nova 框架 + MDUI 2 构建，内置图形验证码与 WAF 限流防滥用。

---

## 功能概览

- **全平台输出**：iOS / Android / macOS / watchOS / Web(PWA)，可任选组合
- **Xcode 直接可用**：Apple 平台生成 `AppIcon.appiconset` 与 `Contents.json`，拖进 Assets 即可
- **符合商店规范**：iOS / watchOS 图标铺满背景色、不带 alpha 通道（App Store 拒收带透明的 1024 图标）
- **圆角裁剪**：Android 与 Web 图标可设 0~50% 圆角（50% 即圆形），边缘抗锯齿
- **Web 全家桶**：`favicon.ico`（内含 16/32/48）、`apple-touch-icon`、`android-chrome-*`、`site.webmanifest`
- **自定义尺寸**：额外输出任意 1~1024px 方形图标，最多 20 个
- **本地实时预览**：调整背景色、圆角时纯前端预览，不上传、不消耗服务端资源
- **防滥用**：数学验证码 + IP 限流 + 失败过多自动封禁

---

## 输出结构

```text
icons.zip
├── ios/AppIcon.appiconset/        # icon-{pt}[@{scale}x].png，iPhone / iPad / App Store + Contents.json
├── mac/AppIcon.appiconset/        # icon-16.png ~ icon-512@2x.png + Contents.json
├── iwatch/AppIcon.appiconset/     # 各表盘尺寸 + Marketing + Contents.json
├── android/
│   ├── mipmap-{mdpi..xxxhdpi}/ic_launcher.png
│   └── playstore-icon.png         # 512×512
├── webapp/
│   ├── favicon.ico / favicon-16x16.png / favicon-32x32.png
│   ├── apple-touch-icon-180x180.png  # 不透明
│   ├── android-chrome-192x192.png / android-chrome-512x512.png
│   └── site.webmanifest
└── custom/                        # 自定义尺寸
```

---

## 系统要求

| 组件 | 版本 / 说明 |
| --- | --- |
| PHP | >= 8.3，需要 `gd`（含 PNG / JPEG / WebP 支持）、`zip`、`fileinfo` |
| Web 服务器 | Nginx / Apache（需 URL 重写），或直接使用内置 Workerman |

无需数据库。生成的压缩包存放在 `src/runtime/icon/`，1 小时后自动清理。

---

## 安装部署

### 1. 获取代码

```bash
git clone https://github.com/AnkioTomas/Icon.git
cd Icon
git submodule update --init --recursive
```

### 2. 运行

**方式一：Workerman（推荐，开箱即用）**

```bash
php nova.phar serve start -d   # 后台启动
php nova.phar serve stop       # 停止
```

默认监听 `127.0.0.1:10211`，可在 `src/config.php` 中修改 `ip` / `port`。

**方式二：Nginx + PHP-FPM**

站点根目录指向 `src/public`，并配置重写：

```nginx
location / {
    try_files $uri $uri/ /index.php$is_args$args;
}
```

确保 `src/runtime/` 对 PHP 进程可写。

### 3. 上线前

将 `src/config.php` 中的 `debug` 改为 `false`。

---

## 防滥用

| 机制 | 说明 |
| --- | --- |
| 图形验证码 | 每次生成前必须完成，一次一码，答错计入失败次数 |
| 接口限流 | 同一 IP 每 60 秒：`/generate` 最多 10 次，`/login/captcha` 最多 30 次 |
| 失败封禁 | 60 秒内失败（验证码错误、404）超过 10 次即封禁，时长随累犯递增（10 分钟起） |
| 上传限制 | 仅 PNG / JPG / WebP / GIF / BMP，≤ 5MB，边长 ≤ 4096px |

限流规则在 `src/app/Application.php` 中配置，封禁参数在 `src/config.php` 的 `waf` 节点中调整。

> 封禁按 `REMOTE_ADDR` 识别客户端。若在 Workerman 前加反向代理，所有请求会被识别为代理 IP，请自行处理真实 IP 透传。

---

## 开发

```bash
php nova.phar test all       # 运行测试
php nova.phar plugin list    # 查看可用插件
```

项目使用的 Nova 插件：`tpl`、`captcha`、`cookie-session`、`security`；前端组件：`captcha`。

更多命令见 [`Nova-Usage.md`](./Nova-Usage.md)。

---

## 致谢

功能设计参考了 [icon-workshop](https://github.com/zhanghuanchong/icon-workshop)，本项目为独立实现，未使用其代码。

## License

MIT

© Ankio
