<?php

declare(strict_types=1);

/*
 * Copyright (c) 2025. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

namespace app;

use nova\framework\App;
use nova\framework\route\Route;
use nova\plugin\security\NovaWaf;

use function nova\framework\route;

class Application extends App
{

    public function onFrameworkStart(): void
    {
        // captcha 前端组件写死了 /login/captcha
        Route::getInstance()
            ->get('/', route('index', 'main', 'index'))
            ->get('/login/captcha', route('index', 'main', 'captcha'))
            ->post('/generate', route('index', 'main', 'generate'))
            ->get('/download/{id}', route('index', 'main', 'download'));

        NovaWaf::instance()
            ->limit('/generate', 10)
            ->limit('/login/captcha', 30);
    }

    public const string SYSTEM_NAME =  "图标工场";

}
