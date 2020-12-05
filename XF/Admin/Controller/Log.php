<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Admin\Controller;

use KL\EditorManager\Entity\AudioProxy;
use KL\EditorManager\Entity\VideoProxy;
use XF\Mvc\Entity\Finder;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Exception;
use XF\Mvc\Reply\Reroute;
use XF\Mvc\Reply\View;
use XF\PrintableException;

/**
 * Class Log
 * @package KL\EditorManager\XF\Admin\Controller
 */
class Log extends XFCP_Log
{
    /**
     * @param ParameterBag $params
     * @return Reroute|View
     */
    public function actionAudioProxy(ParameterBag $params)
    {
        if ($params['audio_id']) {
            return $this->rerouteController(__CLASS__, 'audioProxyView', $params);
        }

        $page = $this->filterPage();
        $perPage = 20;

        /** @var \KL\EditorManager\Repository\AudioProxy $proxyRepo */
        $proxyRepo = $this->repository('KL\EditorManager:AudioProxy');

        $logFinder = $proxyRepo->findProxyLogsForList()
            ->limitByPage($page, $perPage);

        $this->applyAudioProxyFilters($logFinder, $filters);

        $viewParams = [
            'entries' => $logFinder->fetch(),

            'page' => $page,
            'perPage' => $perPage,
            'total' => $logFinder->total(),

            'filters' => $filters
        ];
        return $this->view('KL\EditorManager:Log\AudioProxy\Listing', 'kl_em_log_audio_proxy_list', $viewParams);
    }

    /**
     * @param Finder $finder
     * @param $filters
     */
    protected function applyAudioProxyFilters(Finder $finder, &$filters)
    {
        $filters = [];

        $url = $this->filter('url', 'str');
        $order = $this->filter('order', 'str');

        if ($url !== '') {
            $finder->where('url', 'like', $finder->escapeLike($url, '%?%'));
            $filters['url'] = $url;
        }

        switch ($order) {
            case 'first_request_date':
            case 'views':
            case 'file_size':
                $finder->order($order, 'DESC');
                $filters['order'] = $order;
        }
    }

    /**
     * @param ParameterBag $params
     * @return View
     * @throws Exception
     * @throws PrintableException
     */
    public function actionAudioProxyAudio(ParameterBag $params)
    {
        $audio = $this->assertAudioProxyExists($params['audio_id']);

        if (!$audio->isValid() || $audio->isRefreshRequired()) {
            /** @var \KL\EditorManager\Service\AudioProxy $proxyService */
            $proxyService = $this->service('KL\EditorManager:AudioProxy');
            $audio = $proxyService->refetchAudio($audio);
        }

        if (!$audio->isValid()) {
            /** @var \KL\EditorManager\Repository\AudioProxy $proxyRepo */
            $proxyRepo = $this->app->repository('KL\EditorManager:AudioProxy');
            $audio = $proxyRepo->getPlaceholder();
        }

        $this->setResponseType('raw');

        $viewParams = [
            'audio' => $audio
        ];
        return $this->view('KL\EditorManager:Log\AudioProxy\Audio', '', $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return View
     * @throws Exception
     */
    public function actionAudioProxyView(ParameterBag $params)
    {
        $audio = $this->assertAudioProxyExists($params['audio_id']);

        $viewParams = [
            'audio' => $audio
        ];
        return $this->view('KL\EditorManager:Log\AudioProxy\View', 'kl_em_log_audio_proxy_view', $viewParams);
    }

    /**
     * @param $id
     * @param null $with
     * @param null $phraseKey
     * @return AudioProxy
     * @throws Exception
     */
    protected function assertAudioProxyExists($id, $with = null, $phraseKey = null)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->assertRecordExists('KL\EditorManager:AudioProxy', $id, $with, $phraseKey);
    }

    /**
     * @param ParameterBag $params
     * @return Reroute|View
     */
    public function actionVideoProxy(ParameterBag $params)
    {
        if ($params['video_id']) {
            return $this->rerouteController(__CLASS__, 'videoProxyView', $params);
        }

        $page = $this->filterPage();
        $perPage = 20;

        /** @var \KL\EditorManager\Repository\VideoProxy $proxyRepo */
        $proxyRepo = $this->repository('KL\EditorManager:VideoProxy');

        $logFinder = $proxyRepo->findProxyLogsForList()
            ->limitByPage($page, $perPage);

        $this->applyVideoProxyFilters($logFinder, $filters);

        $viewParams = [
            'entries' => $logFinder->fetch(),

            'page' => $page,
            'perPage' => $perPage,
            'total' => $logFinder->total(),

            'filters' => $filters
        ];
        return $this->view('KL\EditorManager:Log\VideoProxy\Listing', 'kl_em_log_video_proxy_list', $viewParams);
    }

    /**
     * @param Finder $finder
     * @param $filters
     */
    protected function applyVideoProxyFilters(Finder $finder, &$filters)
    {
        $filters = [];

        $url = $this->filter('url', 'str');
        $order = $this->filter('order', 'str');

        if ($url !== '') {
            $finder->where('url', 'like', $finder->escapeLike($url, '%?%'));
            $filters['url'] = $url;
        }

        switch ($order) {
            case 'first_request_date':
            case 'views':
            case 'file_size':
                $finder->order($order, 'DESC');
                $filters['order'] = $order;
        }
    }

    /**
     * @param ParameterBag $params
     * @return View
     * @throws Exception
     * @throws PrintableException
     */
    public function actionVideoProxyVideo(ParameterBag $params)
    {
        $video = $this->assertVideoProxyExists($params['video_id']);

        if (!$video->isValid() || $video->isRefreshRequired()) {
            /** @var \KL\EditorManager\Service\VideoProxy $proxyService */
            $proxyService = $this->service('KL\EditorManager:VideoProxy');
            $video = $proxyService->refetchVideo($video);
        }

        if (!$video->isValid()) {
            /** @var \KL\EditorManager\Repository\VideoProxy $proxyRepo */
            $proxyRepo = $this->app->repository('KL\EditorManager:VideoProxy');
            $video = $proxyRepo->getPlaceholder();
        }

        $this->setResponseType('raw');

        $viewParams = [
            'video' => $video
        ];
        return $this->view('KL\EditorManager:Log\VideoProxy\Video', '', $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return View
     * @throws Exception
     */
    public function actionVideoProxyView(ParameterBag $params)
    {
        $video = $this->assertVideoProxyExists($params['video_id']);

        $viewParams = [
            'video' => $video
        ];
        return $this->view('KL\EditorManager:Log\VideoProxy\View', 'kl_em_log_video_proxy_view', $viewParams);
    }

    /**
     * @param $id
     * @param null $with
     * @param null $phraseKey
     * @return VideoProxy
     * @throws Exception
     */
    protected function assertVideoProxyExists($id, $with = null, $phraseKey = null)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->assertRecordExists('KL\EditorManager:VideoProxy', $id, $with, $phraseKey);
    }
}