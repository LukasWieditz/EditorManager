<?php

/*!
 * KL/EditorManager/Repository/GoogleFont.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Repository;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use KL\EditorManager\Entity\GoogleFont as GoogleFontEntity;
use KL\EditorManager\Finder\GoogleFont as GoogleFontFinder;
use XF;
use XF\Mvc\Entity\Repository;
use XF\PrintableException;

/**
 * Class GoogleFonts
 * @package KL\EditorManager\Repository
 */
class GoogleFont extends Repository
{
    /**
     * @return XF\Mvc\Entity\Finder|GoogleFontFinder
     */
    public function findGoogleFonts(): GoogleFontFinder
    {
        return $this->finder('KL\EditorManager:GoogleFont')
            ->setDefaultOrder('font_id', 'ASC');
    }

    /**
     * @throws Exception
     * @throws PrintableException|GuzzleException
     * @throws GuzzleException
     */
    public function updateFontList(): void
    {
        $apiKey = XF::options()->klEMGoogleApiKey;

        if ($apiKey) {
            try {
                $client = XF::app()->http()->client();
                $params = http_build_query(['key' => $apiKey]);

                $response = $client->get('https://www.googleapis.com/webfonts/v1/webfonts?' . $params);
                $response = json_decode($response->getBody()->getContents(), true);

                $webfonts = $this->findGoogleFonts()
                    ->fetch();

                foreach ($response['items'] as $font) {
                    if ($webfonts->offsetExists($font['family'])) {
                        /** @var GoogleFont $dbFont */
                        $dbFont = $webfonts[$font['family']];
                    } else {
                        /** @var GoogleFontEntity $dbFont */
                        $dbFont = $this->em->create('KL\EditorManager:GoogleFont');
                        $dbFont->font_id = $font['family'];
                    }

                    $dbFont->category = $font['category'];
                    $dbFont->subsets = $font['subsets'];
                    $dbFont->variants = $font['variants'];

                    $dbFont->save();
                }
            } catch (RequestException $e) {
                // this is an exception with the underlying request, so let it go through
                XF::logException($e, false, 'Google Fonts API connection error: ');
            }
        }
    }
}