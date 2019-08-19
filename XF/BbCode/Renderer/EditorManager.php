<?php

namespace KL\EditorManager\XF\BbCode\Renderer;

use KL\EditorManager\XF\Str\Formatter;

/**
 * Trait EditorManager
 * @package KL\EditorManager\XF\BbCode\Renderer
 *
 * @property Formatter formatter
 */
trait EditorManager
{

    /**
     * Editor Font List
     * @var array
     */
    protected $klFontList;

    /**
     * BB Code configuration Configuration
     * @var array
     */
    protected $klConfig;

    /**
     * @var
     */
    protected $klBbCodes;

    /**
     * @param array $tags
     */
    public function klFilterTags(array $tags = [])
    {
        $config = $this->getKLConfig();

        /* Strip disabled BB codes from renderer */
        foreach ($config['enabled_bbcodes'] as $code => $enabled) {
            if (!$enabled) {
                $this->removeTag($code);
            }
        }

        /** Load aliases */
        /** @noinspection PhpUndefinedMethodInspection */
        foreach (\XF::repository('KL\EditorManager:BbCodes')->getBbCodeSettings() as $bbCode => $config) {
            switch ($bbCode) {
                case 'bold':
                    $bbCode = 'b';
                    break;

                case 'italic':
                    $bbCode = 'i';
                    break;

                case 'underline':
                    $bbCode = 'u';
                    break;

                case 'strike':
                    $bbCode = 's';
                    break;

                case 'image':
                    $bbCode = 'img';
                    break;

                default:
                    break;
            }

            if (isset($this->tags[$bbCode])) {
                foreach ($config->aliases as $alias) {
                    $this->addTag(strtolower($alias), $this->tags[$bbCode]);
                }
            }
        }
    }

    /**
     * @param array $tag
     * @param array $options
     * @return mixed|string
     */
    public function renderTag(array $tag, array $options)
    {
        $tagName = $tag['tag'];

        $bbCodes = $this->getKLBbCodes();

        if (isset($bbCodes[$tagName]) && !empty($bbCodes[$tagName]->user_criteria)) {
            /** @var \KL\EditorManager\Entity\BbCode $bbCode */
            $bbCode = $bbCodes[$tagName];

            $userCriteria = \XF::app()->criteria('XF:User', $bbCode->user_criteria ?: []);
            $userCriteria->setMatchOnEmpty(true);
            $user = false;

            if (!empty($options['entity']['User'])) {
                $user = $options['entity']['User'];
            } else {
                if (!empty($options['user'])) {
                    $user = $options['user'];
                }
            }

            $matched = false;
            if ($user) {
                if ($userCriteria->isMatched($user)) {
                    $matched = true;
                }
            }

            if (!$matched) {
                return $this->renderUnparsedTag($tag, $options);
            }
        }

        return parent::renderTag($tag, $options);
    }

    /**
     * Returns font list from cache.
     * @return array
     */
    private function getKLFontList()
    {
        if (!$this->klFontList) {
            /* Create Repository */
            $app = \XF::app();

            /** @var \KL\EditorManager\Repository\Font $repo */
            $repo = $app->em()->getRepository('KL\EditorManager:Font');

            /* Load fonts */
            $fontList = $repo->getFontsCached();

            foreach ($fontList as $font) {
                $ids = explode(',', $font->family);
                $id = strtolower(str_replace(["'", '"'], '', $ids[0]));
                $stack = explode(',', str_replace(["'", '"'], '', $font->family));
                foreach ($stack as &$stackFont) {
                    $stackFont = "'" . trim($stackFont) . "'";
                }

                $this->klFontList[$id] = join(', ', $stack);
            }
        }


        return $this->klFontList;
    }

    /**
     * @return array
     */
    private function getKLBbCodes() {
        if(!$this->klBbCodes) {
            $bbCodes = \XF::repository('KL\EditorManager:BbCodes')->getBbCodeSettings();

            $codes = [];
            foreach($bbCodes as $tag => $bbCode) {
                $codes[$tag] = $bbCode;

                foreach($bbCode->aliases as $alias) {
                    $codes[$alias] = $bbCode;
                }
            }

            $this->klBbCodes = $codes;

        }

        return $this->klBbCodes;
    }

    /**
     * Returns config from cache.
     * @return array
     */
    private function getKLConfig()
    {
        if (!$this->klConfig) {
            $app = \XF::app();

            $options = $app->options();

            $config = [
                'enabled_bbcodes' => $options->klEMEnabledBBCodes,
                'hide_default' => $options->klEMDefaultHide,
                'font_sizes' => explode(', ', $options['klEMFontSizes']),
            ];

            $this->klConfig = $config;
        }

        return $this->klConfig;
    }

    /**
     * @var array
     */
    protected $fontSizeOptions;

    /**
     * Parse font size options
     *
     * @return array
     */
    protected function getKLFontSizeOptions()
    {
        if (!$this->fontSizeOptions) {
            $this->fontSizeOptions = [
                'sizes' => array_map('trim', explode(',', \XF::options()->klEMFontSizes)),
                'maxManual' => \XF::options()->klEMMaxFontSize ? \XF::options()->klEMMaxFontSize : PHP_INT_MAX,
                'minManual' => \XF::options()->klEMMinFontSize
            ];
        }

        return $this->fontSizeOptions;
    }

    /**
     * Convert old option string to array
     *
     * @param $option
     * @return array|string
     */
    private function klOptionStringToArray($option)
    {
        if (is_string($option)) {
            $rules = array_filter(array_map("trim", explode(';', $option)));

            $option = [];
            foreach ($rules as $rule) {
                $parts = explode(':', $rule);
                if (count($parts) != 2) {
                    continue;
                }
                $option[trim($parts[0])] = trim($parts[1]);
            }
        }

        return $option;
    }

    /**
     * Proxy old arguments to XenForo native
     *
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagImage(array $children, $option, array $tag, array $options)
    {
        return parent::renderTagImage($children, $this->klOptionStringToArray($option), $tag, $options);
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagKLSub(array $children, $option, array $tag, array $options)
    {
        $output = $this->renderSubTree($children, $options);
        return "<sub>{$output}</sub>";
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagKLSup(array $children, $option, array $tag, array $options)
    {
        $output = $this->renderSubTree($children, $options);
        return "<sup>{$output}</sup>";
    }

    /**
     * @param array $children
     * @param $option
     * @param array $tag
     * @param array $options
     * @return string
     */
    public function renderTagKLBGColor(array $children, $option, array $tag, array $options)
    {
        $content = $this->renderSubTree($children, $options);
        return "<span style=\"background-color: {$option};\">{$content}</span>";
    }

    /**
     * Arbitrary text size configuration support
     *
     * @param $inputSize
     * @return null|string
     */
    protected function getTextSize($inputSize)
    {
        $options = $this->getKLFontSizeOptions();

        if (strval(intval($inputSize)) == strval($inputSize)) {
            $inputSize--;

            if ($inputSize < 0) {
                $inputSize = 0;
            } else {
                if ($inputSize >= count($options['sizes'])) {
                    $inputSize = count($options['sizes']) - 1;
                }
            }

            return $options['sizes'][$inputSize] . 'px';
        } else {
            if (!preg_match('/^([0-9]+)px$/i', $inputSize, $match)) {
                return null;
            }

            $size = intval($match[1]);
            $size = max($options['minManual'], min($size, $options['maxManual'] ? $options['maxManual'] : PHP_INT_MAX));

            return $size . 'px';
        }
    }

    /**
     * @param $string
     * @param array $options
     * @return null|string|string[]
     */
    public function filterString($string, array $options)
    {
        if(isset($options['user'])) {
            $this->formatter->setKlEmContextUser($options['user']);
        }

        $filteredString = parent::filterString($string, $options);
        $this->formatter->setKlEmContextUser(null);
        return $filteredString;
    }
}