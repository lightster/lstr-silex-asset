<?php
/*
 * Lstr/Silex source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

namespace Lstr\Silex\Asset;

use ArrayObject;

use Assetrinc\AssetService;
use Assetrinc\ResponseAdapter\Symfony as SymfonyResponseAdapter;

use Silex\Application;
use Silex\ServiceProviderInterface;

class AssetServiceProvider implements ServiceProviderInterface
{
    private $defaults;

    public function __construct(array $defaults = array())
    {
        $this->defaults = array_replace(
            array(
                'path'       => new ArrayObject(),
                'assetrinc'  => array(),
                'url_prefix' => null,
            ),
            $defaults
        );
    }

    public function register(Application $app)
    {
        $app['lstr.asset.path']       = $this->defaults['path'];
        $app['lstr.asset.assetrinc']  = $this->defaults['assetrinc'];
        $app['lstr.asset.url_prefix'] = $this->defaults['url_prefix'];

        $app['lstr.asset.configurer'] = $app->protect(function (Application $app) {
            if (!empty($app['config']['lstr.asset.path'])) {
                foreach ($app['config']['lstr.asset.path'] as $namespace => $path) {
                    if (!empty($path)) {
                        $app['lstr.asset.path'][$namespace] = $path;
                    }
                }
                $app['lstr.asset.path'] = $app['config']['lstr.asset.path'];
            }
            if (empty($app['lstr.asset.assetrinc'])
                && !empty($app['config']['lstr.asset.assetrinc'])
            ) {
                $app['lstr.asset.assetrinc'] = $app['config']['lstr.asset.assetrinc'];
            }
            if (empty($app['lstr.asset.url_prefix'])
                && !empty($app['config']['lstr.asset.url_prefix'])
            ) {
                $app['lstr.asset.url_prefix'] = $app['config']['lstr.asset.url_prefix'];
            }

            $app['lstr.asset.assetrinc'] = array_replace(
                array(
                    'debug' => !empty($app['debug']),
                ),
                $app['lstr.asset.assetrinc']
            );
        });

        $app['lstr.asset'] = $app->share(function (Application $app) {
            $configurer = $app['lstr.asset.configurer'];
            $configurer($app);

            return new AssetService(
                $app['lstr.asset.path'],
                $app['lstr.asset.url_prefix'],
                $app['lstr.asset.assetrinc']
            );
        });
        $app['lstr.asset.responder'] = $app->share(function ($app) {
            return new SymfonyResponseAdapter($app['lstr.asset']);
        });
    }

    public function boot(Application $app)
    {
    }
}
