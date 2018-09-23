<?php

$dir = __DIR__;
require ($dir . '/src/XF.php');

XF::start($dir);
$app = XF::setupApp('XF\Pub\App');

/** @var \KL\EditorManager\XF\Proxy\Controller $proxy */
$proxy = $app->proxy()->controller();

$request = $app->request();
$input = $request->filter([
	'video' => 'str',
	'audio' => 'str',
	'hash' => 'str',
	'_xfResponseType' => 'str',
	'referrer' => 'str'
]);

if ($input['video'])
{
	$recursed = $proxy->resolveKLEMVideoProxyRecursion($request, $input['video']);
	if ($recursed)
	{
		$input['video'] = $recursed[0];
		$input['hash'] = $recursed[1];
	}

	$response = $proxy->outputKLEMVideo($input['video'], $input['hash']);
	$response->send($request);
}
else if ($input['audio'])
{
    $recursed = $proxy->resolveKLEMAudioProxyRecursion($request, $input['audio']);
    if ($recursed)
    {
        $input['audio'] = $recursed[0];
        $input['hash'] = $recursed[1];
    }

    $response = $proxy->outputKLEMAudio($input['audio'], $input['hash']);
    $response->send($request);
}
else
{
	header('Content-type: text/plain; charset=utf-8', true, 400);
	echo "Unknown type";
}