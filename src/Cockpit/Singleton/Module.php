<?php

namespace Cockpit\Singleton;

use Cockpit\Framework\PathResolver;
use Cockpit\Singleton\Controller\Admin;
use League\Plates\Engine;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Cockpit\Module as ModuleInterface;

final class Module implements ModuleInterface
{
    public function registerRoutes(App $app)
    {
        $app->group(
            '/singletons',
            function (RouteCollectorProxy $group) {
                $group->map(
                    ['GET'],
                    '',
                    Admin::class.':index'
                )->setName('singletons');

                $group->get(
                    '/form/{name:[a-z0-9]+}',
                    Admin::class.':form'
                )->setName('singleton');

                $group->get(
                    '/{name:[a-z0-9]+}',
                    Admin::class.':singleton'
                )->setName('singleton-structure');

                $group->get(
                    '/revisions/{name:[a-z0-9]+}/{id:[a-z0-9]+}',
                    Admin::class.':revisions'
                )->setName('singleton-revisions');

            }
        );
    }

    public function registerPaths(PathResolver $pathResolver, Engine $engine): void
    {
        $pathResolver->setPath('singletons', __DIR__);
        $engine->addFolder('singletons', __DIR__);
    }
}
