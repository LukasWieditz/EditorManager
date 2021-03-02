<?php

// OPEN FONT AWESOME PRO ICONS https://fontawesome.com/icons
// FILTER TO SPECIFIC CATEGORY AND SAVE PAGE
// REPEAT FOR EACH CATEGORY
// PUT FILES INTO /font-awesome-files
// RUN SCRIPT THROUGH CLI
// SAVE OUTPUT

require('vendor/autoload.php');

$basePath = __DIR__ . '/font-awesome-files';
$files = scandir($basePath);

$iconInfo = [
    'categories' => [],
    'icons' => []
];

foreach ($files as $file) {
    $filePath = $basePath . '/' . $file;
    $pathInfo = pathinfo($filePath);

    if(isset($pathInfo['extension']) && $pathInfo['extension'] == 'html') {
        $html = new \PHPHtmlParser\Dom();
        $html->loadFromFile($filePath);

        /** @var \PHPHtmlParser\Dom\Node\HtmlNode $title */
        $title = $html->find('title')[0];
        $title = $title->text;
        $title = trim(substr($title, 0, strpos($title, 'Icons')));
        $categoryId = strtolower(str_replace('  ', '_', str_replace('&', '', $title)));

        $iconInfo['categories'][$categoryId] = $title;

        $icons = $html->find('i');

        foreach($icons as $icon) {
            if($icon instanceof \PHPHtmlParser\Dom\Node\HtmlNode) {
                $text = $icon->getAttribute('class');

                if(strpos($text, 'fa') === 0) {
                    list($style, $class) = explode(' ', $text);
                    $class = substr($class, 3);

                    if(!isset($iconInfo['icons'][$class])) {
                        $iconInfo['icons'][$class] = [
                            'categories' => [],
                            'styles' => []
                        ];
                    }
                    $iconInfo['icons'][$class]['categories'][] = $categoryId;
                    $iconInfo['icons'][$class]['styles'][] = $style;

                    $iconInfo['icons'][$class]['categories'] = array_unique($iconInfo['icons'][$class]['categories']);
                    $iconInfo['icons'][$class]['styles'] = array_unique($iconInfo['icons'][$class]['styles']);
                }
            }
        }
    }
}

function file_force_contents($dir, $contents){
    $parts = explode('/', $dir);
    $file = array_pop($parts);
    $dir = '';
    foreach($parts as $part)
        if(!is_dir($dir .= "/$part")) mkdir($dir);
    file_put_contents("$dir/$file", $contents);
}

$data = json_encode($iconInfo);
echo $data;