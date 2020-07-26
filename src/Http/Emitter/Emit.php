<?php

namespace Stillat\Meerkat\Http\Emitter;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Statamic\Statamic;
use Stillat\Meerkat\Addon;
use Stillat\Meerkat\Http\RequestHelpers;

class Emit
{

    public static function cpCss($dynamicCssName, $callback)
    {
        if (RequestHelpers::isControlPanelRequestFromHeaders(request())) {
            Emit::css($dynamicCssName, $callback);
        }
    }

    public static function css($dynamicCssName, $callback)
    {
        $assetNameForStatamic = './../' . Addon::CODE_ADDON_NAME;
        $fileName = basename($dynamicCssName);

        Statamic::style($assetNameForStatamic, $fileName);


        Route::get('/' . Addon::CODE_ADDON_NAME . '/css/' . $fileName . '.css', function () use ($callback) {
            $content = $callback();

            return response($content)->header('Content-Type', 'text/css');
        });
    }

    public static function cpJs($dynamicJsName, $callback)
    {
        if (RequestHelpers::isControlPanelRequestFromHeaders(request())) {
            Emit::js($dynamicJsName, $callback);
        }
    }

    public static function js($dynamicJsName, $callback)
    {
        $assetNameForStatamic = './../' . Addon::CODE_ADDON_NAME;
        $fileName = basename($dynamicJsName);

        Statamic::script($assetNameForStatamic, $fileName);

        Route::get('/' . Addon::CODE_ADDON_NAME . '/js/' . $fileName . '.js', function () use ($callback) {
            $content = $callback();

            return response($content)->header('Content-Type', 'application/javascript');
        });
    }

}