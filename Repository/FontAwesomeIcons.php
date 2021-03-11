<?php


namespace KL\EditorManager\Repository;


use XF;
use XF\Mvc\Entity\Manager;
use XF\Mvc\Entity\Repository;

/**
 * Class FontAwesomeIcons
 * @package KL\EditorManager\Repository
 */
class FontAwesomeIcons extends Repository
{
    /**
     * @var array
     */
    protected $categories;

    /**
     * @var array
     */
    protected $icons;

    /**
     * FontAwesomeIcons constructor.
     * @param Manager $em
     * @param $identifier
     */
    public function __construct(Manager $em, $identifier)
    {
        parent::__construct($em, $identifier);
        $baseFile = XF::getAddOnDirectory() . '/KL/EditorManager/fontAwesome.icons.json';
        $data = json_decode(file_get_contents($baseFile), true);
        $this->categories = $data['categories'];
        $this->icons = $data['icons'];
    }

    /**
     * @return array
     */
    public function getCategoriesForList(): array
    {
        return $this->categories;
    }

    /**
     * @return array
     */
    public function getIconsForList(): array
    {
        return $this->icons;
    }

    /**
     * @param $string
     * @param array $options
     * @return array
     */
    public function getMatchingIconsByString($string, array $options = []): array
    {
        $options = array_replace([
            'includeEmoji' => true,
            'includeSmilies' => true,
            'limit' => 5
        ], $options);

        $icons = $this->getIconsForList();

        $results = [];

        foreach ($icons as $id => $icon) {
            if (stripos($id, $string) !== false) {
                $results[$id] = $icon;
            }
        }

        ksort($results);
        return array_slice($results, 0, $options['limit'], true);
    }
}