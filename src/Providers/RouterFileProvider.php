<?php
/**
 * User: macro chen <chen_macro@163.com>
 * Date: 16-8-26
 * Time: 下午4:28
 */
namespace Polymer\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class RouterFileProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $pimple A container instance
     */
    public function register(Container $pimple)
    {
        $pimple['routerFile'] = function (Container $container) {
            if (!file_exists(APP_PATH . '/Routers/router.lock') || $container['application']->config('app.generate_router', false)) {
                if (file_exists($container['application']->config('app.router_cache_file', $container['application']->config('slim.settings.routerCacheFile')))) {
                    @unlink($container['application']->config('app.router_cache_file', $container['application']->config('slim.settings.routerCacheFile')));
                }
                $router_file_contents = '<?php ' . "\n" . '$app = $container[\'application\']->component(\'app\');';
                if (class_exists('\RunTracy\Middlewares\TracyMiddleware')) {
                    $router_file_contents .= "\n" . '$app->add(new \RunTracy\Middlewares\TracyMiddleware($app));';
                }
                if ($container['application']->config('middleware')) {
                    foreach ($container['application']->config('middleware') as $key => $middleware) {
                        if (function_exists($middleware) && is_callable($middleware)) {
                            $router_file_contents .= "\n" . '$app->add("' . $middleware . '");';
                        } elseif ($container['application']->component($middleware)) {
                            $router_file_contents .= "\n" . '$app->add($container[\'application\']->component("' . $middleware . '"));';
                        } elseif ($container['application']->component($key)) {
                            $router_file_contents .= "\n" . '$app->add($container[\'application\']->component("' . $key . '"));';
                        } elseif (class_exists($middleware)) {
                            $router_file_contents .= "\n" . '$app->add("' . $middleware . '");';
                        }
                    }
                }
                $router_file_contents .= "\n";
                foreach (glob(APP_PATH . 'Routers/*_router.php') as $key => $file_name) {
                    $contents = file_get_contents($file_name);
                    preg_match_all('/app->[\s\S]*/', $contents, $matches);
                    foreach ($matches[0] as $kk => $vv) {
                        $router_file_contents .= '$' . $vv . "\n";
                    }
                }
                file_put_contents(APP_PATH . 'Routers/router.php', $router_file_contents);
                $container['router']->setCacheFile($container['application']->config('app.router_cache_file', $container['application']->config('slim.settings.routerCacheFile')));
                touch(APP_PATH . '/Routers/router.lock');
            }
            require_once APP_PATH . 'Routers/router.php';
        };
    }
}
