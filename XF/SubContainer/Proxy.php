<?php

namespace KL\EditorManager\XF\SubContainer;

use XF\Proxy\Linker;

class Proxy extends XFCP_Proxy
{
    protected $linkerTypes = [];

    public function initialize()
    {
        parent::initialize();

        $options = $this->app->options();
        $this->linkerTypes['image'] = !empty($options->imageLinkProxy['images']);
        $this->linkerTypes['link'] = !empty($options->imageLinkProxy['links']);
        $this->linkerTypes['video'] = !empty($options->klEMProxy['videos']);
        $this->linkerTypes['audio'] = !empty($options->klEMProxy['audios']);

        $container = $this->container;
        $container['controller'] = function ($c) {
            $class = $this->extendClass('\XF\Proxy\Controller');
            return new $class($this->app, $c['linker'], $this->app->request());
        };

        $parent = $container['linker'];
        $container['linker'] = function ($c) use ($parent) {
            $options = $this->app->options();
            $secret = $this->app->config('globalSalt') . $options->imageLinkProxyKey;

            return new Linker(
                $c['linker.format'],
                $this->linkerTypes,
                $secret,
                $this->app['request.pather']
            );
        };

        $container['klEMLinker'] = function ($c) {
            $options = $this->app->options();
            $secret = $this->app->config('globalSalt') . $options->imageLinkProxyKey;

            return new Linker(
                'klEMProxy.php?{type}={url}&hash={hash}',
                $this->linkerTypes,
                $secret,
                $this->app['request.pather']
            );
        };
    }

    public function generate($type, $url)
    {
        if ($type == 'video' || $type == 'audio') {
            return $this->container['klEMLinker']->generate($type, $url);
        }

        return parent::generate($type, $url);
    }
}