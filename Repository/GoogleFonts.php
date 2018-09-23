<?php

namespace KL\EditorManager\Repository;

use GuzzleHttp\Exception\RequestException;
use XF\Mvc\Entity\Repository;

/**
 * Class GoogleFonts
 * @package KL\EditorManager\Repository
 */
class GoogleFonts extends Repository {
    /**
     * @throws \Exception
     * @throws \XF\PrintableException
     */
    public function updateFontList() {
        $options = \XF::app()->options();
        $apiKey = $options->klEMGoogleApiKey;

        if($apiKey) {
            try {
                $client = \XF::app()->http()->client();
                $params = http_build_query(['key' => $apiKey]);

                $response = $client->get('https://www.googleapis.com/webfonts/v1/webfonts?' . $params)->json();
                $webfonts = $this->finder('KL\EditorManager:GoogleFont')->fetch();

                foreach ($response['items'] as $font) {
                    if ($webfonts->offsetExists($font['family'])) {
                        /** @var \KL\EditorManager\Entity\GoogleFont $dbFont */
                        $dbFont = $webfonts[$font['family']];
                    } else {
                        /** @var \KL\EditorManager\Entity\GoogleFont $dbFont */
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
                \XF::logException($e, false, 'Google Fonts API connection error: ');
                return;
            }
        }
    }
}