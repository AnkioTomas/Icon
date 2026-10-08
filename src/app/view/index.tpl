<title id="title">{$title} - 一键生成全平台应用图标</title>
<style id="style">
    .workshop {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1rem;
        padding: 5rem 0 2rem;
    }
    @media (min-width: 840px) {
        .workshop { grid-template-columns: minmax(0, 3fr) minmax(0, 2fr); }
    }
    .workshop mdui-card { width: 100%; padding: 1.25rem; }
    .hero h1 { font-size: 1.75rem; margin-bottom: .25rem; }
    .hero p { opacity: .7; }
    .drop-zone {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        min-height: 220px;
        border: 2px dashed rgb(var(--mdui-color-outline));
        border-radius: var(--mdui-shape-corner-large);
        cursor: pointer;
        transition: background-color .2s;
    }
    .drop-zone.dragging,
    .drop-zone:hover { background: rgb(var(--mdui-color-primary-container)); }
    .drop-zone img { max-width: 180px; max-height: 180px; }
    /* 不用 display:none：部分 WebView 拒绝激活不可见的 file input */
    .file-input { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
    .hint { opacity: .7; font-size: .8rem; }
    .preview-row { display: flex; flex-wrap: wrap; gap: 1.5rem; justify-content: space-around; align-items: flex-end; }
    .preview-item { display: flex; flex-direction: column; align-items: center; gap: .5rem; font-size: .75rem; opacity: .85; }
    .preview-item .icon { background-size: cover; background-position: center; }
    .section-title { font-weight: 600; margin: 1rem 0 .5rem; }
    .section-title:first-child { margin-top: 0; }
    .chips { display: flex; flex-wrap: wrap; gap: .5rem; }
    .color-row { display: flex; align-items: center; gap: .75rem; }
    .color-row input[type=color] { width: 48px; height: 40px; border: none; padding: 0; background: none; cursor: pointer; }
    .footer { text-align: center; opacity: .6; font-size: .8rem; padding-bottom: 2rem; }
</style>

<div id="container" class="container">
    <div class="workshop">
        <div class="d-flex" style="flex-direction: column; gap: 1rem;">
            <mdui-card class="hero">
                <h1>一键生成全平台应用图标</h1>
                <p>上传一张 1024×1024 的方形图片，自动生成 iOS、Android、macOS、watchOS 与 Web 所需的全部尺寸，打包下载后可直接导入工程。</p>
            </mdui-card>

            <mdui-card>
                <input type="file" id="imageInput" class="file-input" accept="image/png,image/jpeg,image/webp,image/gif,image/bmp">
                <div class="drop-zone" id="dropZone" role="button" tabindex="0">
                    <img id="sourcePreview" alt="" hidden>
                    <mdui-icon name="cloud_upload" id="uploadIcon" style="font-size: 48px"></mdui-icon>
                    <span id="uploadText">点击或拖拽图片到这里</span>
                    <span class="hint">PNG / JPG / WebP，≤ 5MB，非正方形会居中裁剪</span>
                </div>
            </mdui-card>

            <mdui-card>
                <div class="section-title">效果预览</div>
                <div class="preview-row" id="previewRow">
                    <div class="preview-item"><div class="icon" data-kind="opaque" data-size="76" data-radius="22.5"></div>iOS</div>
                    <div class="preview-item"><div class="icon" data-kind="rounded" data-size="72"></div>Android</div>
                    <div class="preview-item"><div class="icon" data-kind="plain" data-size="64" data-radius="0"></div>macOS</div>
                    <div class="preview-item"><div class="icon" data-kind="opaque" data-size="56" data-radius="50"></div>watchOS</div>
                    <div class="preview-item"><div class="icon" data-kind="rounded" data-size="32"></div>Favicon</div>
                </div>
            </mdui-card>
        </div>

        <mdui-card>
            <div class="section-title">目标平台</div>
            <div class="chips" id="platformChips">
                {foreach $platforms as $key => $platform}
                    <mdui-chip variant="filter" selectable selected value="{$key}">{$platform.label}</mdui-chip>
                {/foreach}
            </div>

            <div class="section-title">背景色</div>
            <div class="color-row">
                <input type="color" id="bgColor" value="#ffffff">
                <span class="hint">iOS / watchOS 不允许透明，透明区域会填充此颜色</span>
            </div>

            <div class="section-title">圆角 <span id="radiusValue">0</span>%</div>
            <mdui-slider id="radius" min="0" max="50" value="0" labeled></mdui-slider>
            <div class="hint">作用于 Android 与 Web 图标；Apple 平台由系统自动裁剪圆角</div>

            <div class="section-title">自定义尺寸</div>
            <mdui-text-field id="customSizes" label="例如：64, 100, 300" helper="以逗号分隔，单位 px，最大 1024，最多 20 个" clearable></mdui-text-field>

            <mdui-button id="generateBtn" icon="download" full-width class="mt-3">生成并下载</mdui-button>
        </mdui-card>
    </div>

    <div class="footer">
        仿照 <a href="https://github.com/zhanghuanchong/icon-workshop" target="_blank" rel="noopener">icon-workshop</a> 的功能，使用 MDUI 重新实现
    </div>

    <nova-captcha id="captcha"></nova-captcha>
</div>
<script id="script" src="/static/js/index.js?v={$__v}"></script>
