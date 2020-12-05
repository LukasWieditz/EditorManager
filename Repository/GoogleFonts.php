<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Repository;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use KL\EditorManager\Entity\GoogleFont;
use XF;
use XF\Mvc\Entity\Repository;
use XF\PrintableException;

/**
 * Class GoogleFonts
 * @package KL\EditorManager\Repository
 */
class GoogleFonts extends Repository
{
    /**
     * @throws Exception
     * @throws PrintableException|GuzzleException
     * @throws GuzzleException
     */
    public function updateFontList()
    {
        $options = XF::app()->options();
        $apiKey = $options->klEMGoogleApiKey;

        if ($apiKey) {
            try {
                $client = XF::app()->http()->client();
                $params = http_build_query(['key' => $apiKey]);

                $response = $client->get('https://www.googleapis.com/webfonts/v1/webfonts?' . $params)->json();
                $webfonts = $this->finder('KL\EditorManager:GoogleFont')->fetch();

                foreach ($response['items'] as $font) {
                    if ($webfonts->offsetExists($font['family'])) {
                        /** @var GoogleFont $dbFont */
                        $dbFont = $webfonts[$font['family']];
                    } else {
                        /** @var GoogleFont $dbFont */
                        $dbFont = $this->em->create('KL\EditorManager:GoogleFont');
                        $dbFont->font_id = $font['family'];
                    }


                    $dbFont->category = $font['category'];
                    $dbFont->subsets = $font['subsets'];
                    $dbFont->variants = $font['variants'];

                    $dbFont->save();
                }

                return;
            } catch (RequestException $e) {
                // this is an exception with the underlying request, so let it go through
                XF::logException($e, false, 'Google Fonts API connection error: ');
                return;
            }
        }
    }
}