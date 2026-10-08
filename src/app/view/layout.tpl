<!doctype html>
<html lang="zh-CN" class="mdui-theme-auto scroll-line">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <meta name="renderer" content="webkit"/>
    <meta name="description" content="上传一张图片，一键生成 iOS、Android、macOS、watchOS、Web 全平台应用图标"/>
    <title id="title">{$title}</title>
    {* 不 include 框架的 publicHeader.tpl：它引用的 favicon 文件不存在，404 会被 WAF 计为失败而封 IP *}
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%236750a4'/%3E%3Crect x='16' y='16' width='14' height='14' rx='3' fill='%23fff'/%3E%3Crect x='34' y='16' width='14' height='14' rx='3' fill='%23fff' opacity='.7'/%3E%3Crect x='16' y='34' width='14' height='14' rx='3' fill='%23fff' opacity='.7'/%3E%3Crect x='34' y='34' width='14' height='14' rx='3' fill='%23fff' opacity='.45'/%3E%3C/svg%3E"/>
    <link rel="stylesheet" href="/static/bundle?file=framework/icons/fonts.css,framework/libs/mdui.css,framework/base.css,framework/utils/Loading.css,framework/pjax/nprogress.css,framework/layout.css&type=css&v={$__v}">
    <script src="/static/bundle?file=framework/libs/vhcheck.min.js,framework/libs/mdui.global.min.js&type=js&v={$__v}"></script>
    <style>
        /* github.svg 自带黑色填充，用 mask 让它跟随主题文字色 */
        .github-icon {
            display: inline-block;
            width: 22px;
            height: 22px;
            background-color: currentColor;
            -webkit-mask: url(/static/images/github.svg) center / contain no-repeat;
            mask: url(/static/images/github.svg) center / contain no-repeat;
        }
        .copyright { text-align: center; font-size: .8rem; opacity: .6; padding: 0 1rem 2rem; }
        .copyright a { color: inherit; }
    </style>
    <style id="style"></style>
</head>

<body class="bg">

<mdui-layout>
    <mdui-top-app-bar scroll-behavior="elevate" scroll-target=".layout-main" class="position-fixed">
        <mdui-icon name="apps" class="ml-2"></mdui-icon>
        <mdui-top-app-bar-title>{$title}</mdui-top-app-bar-title>
        <div style="flex-grow: 1"></div>
        <theme-switcher iconBtn="true"></theme-switcher>
        <lang-switcher iconBtn="true"></lang-switcher>
        <mdui-tooltip content="GitHub">
            <mdui-button-icon href="https://github.com/AnkioTomas/Icon" target="_blank" rel="noopener">
                <span class="github-icon"></span>
            </mdui-button-icon>
        </mdui-tooltip>
    </mdui-top-app-bar>

    <mdui-layout-main class="layout-main">
        <div id="bodyContainer">
            <div id="container" class="container"></div>
        </div>
        <footer class="copyright no-translate">
            © {date('Y')} <a href="https://ankio.net" target="_blank" rel="noopener">Ankio</a>. All rights reserved.
        </footer>
    </mdui-layout-main>
</mdui-layout>
{if isset($__template_file) && $__template_file}
    <article id="page" hidden>
        {include file=$__template_file}
    </article>
{/if}
<script id="script"></script>
{include file="../static/framework/publicScript.tpl"}
</body>
</html>
