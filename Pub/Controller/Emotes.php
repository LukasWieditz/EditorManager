<?php

/*!
 * KL/EditorManager/Pub/Controller/Emotes.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Pub\Controller;

use KL\EditorManager\Entity\CustomEmote;
use KL\EditorManager\Entity\CustomEmotePrefix;
use KL\EditorManager\Service\CustomEmote\Image;
use XF;
use XF\ControllerPlugin\Delete;
use XF\Mvc\Entity\Entity;
use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\Error;
use XF\Mvc\Reply\Exception;
use XF\Mvc\Reply\Redirect;
use XF\Mvc\Reply\View;
use XF\PrintableException;
use XF\Pub\Controller\AbstractController;

/**
 * Class Emotes
 * @package KL\EditorManager\Pub\Controller
 */
class Emotes extends AbstractController
{
//    /**
//     * @return View
//     * @throws PrintableException
//     */
//    public function actionIndex(): AbstractReply
//    {
//        $prefix = $this->getVisitorEmotePrefix();
//
//        $emotes = $this->finder('KL\EditorManager:CustomEmote')
//            ->where('user_id', '=', XF::visitor()->user_id)
//            ->fetch();
//
//        $viewParams = [
//            'emotes' => $emotes,
//            'canCreateEmotes' => true,
//            'prefix' => $prefix
//        ];
//
//        return $this->view('KL\EditorManager:CustomEmote\List', 'kl_em_custom_emote_list', $viewParams);
//    }
//
//    /**
//     * @return Error|Redirect
//     * @throws PrintableException
//     */
//    public function actionChangePrefix(): AbstractReply
//    {
//        $prefix = $this->getVisitorEmotePrefix();
//        $newPrefix = $this->filter('prefix', 'str');
//        $prefix->prefix = $newPrefix;
//
//        if (!$prefix->preSave()) {
//            return $this->error($prefix->getErrors());
//        }
//
//        $prefix->saveIfChanged();
//
//        return $this->redirect($this->getDynamicRedirect($this->buildLink('account/kl-custom-emotes')));
//    }
//
//    /**
//     * @return View
//     */
//    public function actionAdd(): AbstractReply
//    {
//        /** @var CustomEmote $emote */
//        $emote = $this->em()->create('KL\EditorManager:CustomEmote');
//        return $this->emoteAddEdit($emote);
//    }
//
//    /**
//     * @param ParameterBag $params
//     * @return View
//     * @throws Exception
//     */
//    public function actionEdit(ParameterBag $params): AbstractReply
//    {
//        $emote = $this->assertEditableEmote($params['emote_id']);
//        return $this->emoteAddEdit($emote);
//    }
//
//    /**
//     * @param $emote
//     * @return View
//     */
//    protected function emoteAddEdit(CustomEmote $emote): AbstractReply
//    {
//        $viewParams = [
//            'emote' => $emote
//        ];
//
//        return $this->view('KL\EditorManager:CustomEmote\Edit', 'kl_em_custom_emote_edit', $viewParams);
//    }
//
//    /**
//     * @param ParameterBag $params
//     * @return Error|Redirect
//     * @throws Exception
//     * @throws PrintableException
//     */
//    public function actionSave(ParameterBag $params): AbstractReply
//    {
//        if ($params['emote_id']) {
//            $emote = $this->assertEditableEmote($params['emote_id']);
//        } else {
//            /** @var CustomEmote $emote */
//            $emote = $this->em()->create('KL\EditorManager:CustomEmote');
//            $prefix = $this->em()->find('KL\EditorManager:CustomEmotePrefix', ['user_id' => XF::visitor()->user_id]);
//            $emote->prefix_id = $prefix['prefix_id'];
//        }
//
//        $this->emoteSaveProcess($emote)->run();
//
//        $mode = $this->filter('mode', 'str');
//
//        if ($mode != 'upload' && $emote->isInsert()) {
//            return $this->noPermission();
//        }
//
//        /** @var Image $imageService */
//        $imageService = $this->service('KL\EditorManager:CustomEmote\Image', $emote);
//
//        if ($mode == 'upload')
//        {
//            $upload = $this->request->getFile('upload', false, false);
//            if ($upload)
//            {
//                if (!$imageService->setImageFromUpload($upload))
//                {
//                    if($emote->isInsert()) {
//                        $emote->delete();
//                    }
//
//                    return $this->error($imageService->getError());
//                }
//
//                if (!$imageService->updateImage())
//                {
//                    if($emote->isInsert()) {
//                        $emote->delete();
//                    }
//
//                    return $this->error(XF::phrase('kl_em_emote_upload_failed'));
//                }
//            }
//        }
//
//        return $this->redirect($this->buildLink('account/kl-custom-emotes'));
//    }
//
//    /**
//     * @param CustomEmote $emote
//     * @return FormAction
//     */
//    protected function emoteSaveProcess(CustomEmote $emote): FormAction
//    {
//        $form = $this->formAction();
//
//        $input = $this->filter([
//            'title' => 'str'
//        ]);
//
//        $input['replacement'] = utf8_ucfirst(utf8_strtolower($this->filter('replacement', 'str')));
//
//        $form->basicEntitySave($emote, $input);
//
//        return $form;
//    }
//
//    /**
//     * @param ParameterBag $params
//     * @return Error|Redirect|View
//     * @throws Exception
//     */
//    public function actionDelete(ParameterBag $params): AbstractReply
//    {
//        $emote = $this->assertEditableEmote($params['emote_id']);
//
//        /** @var Delete $plugin */
//        $plugin = $this->plugin('XF:Delete');
//        return $plugin->actionDelete(
//            $emote,
//            $this->buildLink('account/kl-custom-emotes/delete', $emote),
//            $this->buildLink('account/kl-custom-emotes/edit', $emote),
//            $this->buildLink('account/kl-custom-emotes'),
//            $emote->title
//        );
//    }
//
//    /**
//     * @return CustomEmotePrefix|Entity
//     * @throws PrintableException
//     */
//    protected function getVisitorEmotePrefix(): CustomEmotePrefix
//    {
//        $visitor = XF::visitor();
//
//        /** @var CustomEmotePrefix $emotePrefix */
//        $emotePrefix = $this->em()->find('KL\EditorManager:CustomEmotePrefix', ['user_id' => $visitor->user_id]);
//
//        if (!$emotePrefix) {
//            $length = 3;
//            $emotePrefix = $this->em()->create('KL\EditorManager:CustomEmotePrefix');
//            $emotePrefix->bulkSet([
//                'user_id' => $visitor->user_id,
//                'prefix' => utf8_strtolower(utf8_substr($visitor->username, 0, $length))
//            ]);
//            while (!$emotePrefix->preSave()) {
//                if (utf8_strlen($visitor->username) >= $length) {
//                    $emotePrefix->set('prefix', utf8_strtolower(utf8_substr($visitor->username, 0, ++$length)),
//                        ['forceSet' => true]);
//                } else {
//                    $emotePrefix->set('prefix', $visitor->username . XF::$time, ['forceSet' => true]);
//                }
//            }
//
//            $emotePrefix->save();
//        }
//
//        return $emotePrefix;
//    }
//
//    /**
//     * @param $id
//     * @param null $with
//     * @param null $phraseKey
//     * @return Entity|CustomEmote
//     * @throws Exception
//     */
//    protected function assertEditableEmote($id, $with = null, $phraseKey = null): CustomEmote
//    {
//        /** @var CustomEmote $emote */
//        $emote = $this->assertRecordExists('KL\EditorManager:CustomEmote', $id, $with, $phraseKey);
//
//        if (!$emote->canEdit()) {
//            throw $this->exception(
//                $this->notFound(XF::phrase($phraseKey))
//            );
//        }
//
//        return $emote;
//    }
}