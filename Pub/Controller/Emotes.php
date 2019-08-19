<?php

namespace KL\EditorManager\Pub\Controller;

use KL\EditorManager\Entity\CustomEmote;
use KL\EditorManager\Entity\CustomEmotePrefix;
use KL\EditorManager\Service\CustomEmote\Image;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Redirect;
use XF\Pub\Controller\AbstractController;

/**
 * Class Emotes
 * @package KL\EditorManager\Pub\Controller
 */
class Emotes extends AbstractController
{
    /**
     * @return \XF\Mvc\Reply\View
     * @throws \XF\PrintableException
     */
    public function actionIndex()
    {
        $prefix = $this->getVisitorEmotePrefix();

        $emotes = $this->finder('KL\EditorManager:CustomEmote')
            ->where('user_id', '=', \XF::visitor()->user_id)
            ->fetch();

        $viewParams = [
            'emotes' => $emotes,
            'canCreateEmotes' => true,
            'prefix' => $prefix
        ];

        return $this->view('KL\EditorManager:CustomEmote\List', 'kl_em_custom_emote_list', $viewParams);
    }

    /**
     * @return \XF\Mvc\Reply\Error|\XF\Mvc\Reply\Redirect
     * @throws \XF\PrintableException
     */
    public function actionChangePrefix()
    {
        $prefix = $this->getVisitorEmotePrefix();
        $newPrefix = $this->filter('prefix', 'str');
        $prefix->prefix = $newPrefix;

        if (!$prefix->preSave()) {
            return $this->error($prefix->getErrors());
        }

        $prefix->saveIfChanged();

        return $this->redirect($this->getDynamicRedirect($this->buildLink('account/kl-custom-emotes')));
    }

    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionAdd()
    {
        /** @var CustomEmote $emote */
        $emote = $this->em()->create('KL\EditorManager:CustomEmote');
        return $this->emoteAddEdit($emote);
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionEdit(ParameterBag $params)
    {
        $emote = $this->assertEditableEmote($params['emote_id']);
        return $this->emoteAddEdit($emote);
    }

    /**
     * @param $emote
     * @return \XF\Mvc\Reply\View
     */
    protected function emoteAddEdit(CustomEmote $emote)
    {
        $viewParams = [
            'emote' => $emote
        ];

        return $this->view('KL\EditorManager:CustomEmote\Edit', 'kl_em_custom_emote_edit', $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Error|Redirect
     * @throws \XF\Mvc\Reply\Exception
     * @throws \XF\PrintableException
     */
    public function actionSave(ParameterBag $params)
    {
        if ($params['emote_id']) {
            $emote = $this->assertEditableEmote($params['emote_id']);
        } else {
            /** @var CustomEmote $emote */
            $emote = $this->em()->create('KL\EditorManager:CustomEmote');
            $prefix = $this->em()->find('KL\EditorManager:CustomEmotePrefix', ['user_id' => \XF::visitor()->user_id]);
            $emote->prefix_id = $prefix['prefix_id'];
        }

        $this->emoteSaveProcess($emote)->run();

        $mode = $this->filter('mode', 'str');

        if ($mode != 'upload' && $emote->isInsert()) {
            return $this->noPermission();
        }

        /** @var Image $imageService */
        $imageService = $this->service('KL\EditorManager:CustomEmote\Image', $emote);

        if ($mode == 'upload')
        {
            $upload = $this->request->getFile('upload', false, false);
            if ($upload)
            {
                if (!$imageService->setImageFromUpload($upload))
                {
                    if($emote->isInsert()) {
                        $emote->delete();
                    }

                    return $this->error($imageService->getError());
                }

                if (!$imageService->updateImage())
                {
                    if($emote->isInsert()) {
                        $emote->delete();
                    }

                    return $this->error(\XF::phrase('kl_em_emote_upload_failed'));
                }
            }
        }

        return $this->redirect($this->buildLink('account/kl-custom-emotes'));
    }

    protected function emoteSaveProcess(CustomEmote $emote)
    {
        $form = $this->formAction();

        $input = $this->filter([
            'title' => 'str'
        ]);

        $input['replacement'] = utf8_ucfirst(utf8_strtolower($this->filter('replacement', 'str')));

        $form->basicEntitySave($emote, $input);

        return $form;
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Error|Redirect|\XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionDelete(ParameterBag $params)
    {
        $emote = $this->assertEditableEmote($params['emote_id']);

        /** @var \XF\ControllerPlugin\Delete $plugin */
        $plugin = $this->plugin('XF:Delete');
        return $plugin->actionDelete(
            $emote,
            $this->buildLink('account/kl-custom-emotes/delete', $emote),
            $this->buildLink('account/kl-custom-emotes/edit', $emote),
            $this->buildLink('account/kl-custom-emotes'),
            $emote->title
        );
    }

    /**
     * @return CustomEmotePrefix|\XF\Mvc\Entity\Entity
     * @throws \XF\PrintableException
     */
    protected function getVisitorEmotePrefix()
    {
        $visitor = \XF::visitor();

        /** @var CustomEmotePrefix $emotePrefix */
        $emotePrefix = $this->em()->find('KL\EditorManager:CustomEmotePrefix', ['user_id' => $visitor->user_id]);

        if (!$emotePrefix) {
            $length = 3;
            $emotePrefix = $this->em()->create('KL\EditorManager:CustomEmotePrefix');
            $emotePrefix->bulkSet([
                'user_id' => $visitor->user_id,
                'prefix' => utf8_strtolower(utf8_substr($visitor->username, 0, $length))
            ]);
            while (!$emotePrefix->preSave()) {
                if (utf8_strlen($visitor->username) >= $length) {
                    $emotePrefix->set('prefix', utf8_strtolower(utf8_substr($visitor->username, 0, ++$length)),
                        ['forceSet' => true]);
                } else {
                    $emotePrefix->set('prefix', $visitor->username . \XF::$time, ['forceSet' => true]);
                }
            }

            $emotePrefix->save();
        }

        return $emotePrefix;
    }

    /**
     * @param $id
     * @param null $with
     * @param null $phraseKey
     * @return \XF\Mvc\Entity\Entity|CustomEmote
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertEditableEmote($id, $with = null, $phraseKey = null)
    {
        /** @var CustomEmote $emote */
        $emote = $this->assertRecordExists('KL\EditorManager:CustomEmote', $id, $with, $phraseKey);

        if (!$emote->canEdit()) {
            throw $this->exception(
                $this->notFound(\XF::phrase($phraseKey))
            );
        }

        return $emote;
    }
}