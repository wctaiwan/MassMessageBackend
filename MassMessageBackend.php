<?php

// Prevent direct access
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

$wgExtensionCredits['specialpage'][] = array(
	'path'   => __FILE__,
	'name'   => 'MassMessageBackend',
	'author' => 'wctaiwan',
	'version' => '0.1',
	'descriptionmsg' => 'massmessagebackend-desc',
);

// Interface messages
$wgMessagesDirs['MassMessageBackend'] = __DIR__ . '/i18n';

// Classes
$wgAutoloadClasses['MassMessageBackendHooks'] = __DIR__ . '/MassMessageBackend.hooks.php';
$wgAutoloadClasses['SpecialManageMassMessageList'] = __DIR__ . '/SpecialManageMassMessageList.php';
$wgAutoloadClasses['MassMessageListContent'] = __DIR__ . '/MassMessageListContent.php';
$wgAutoloadClasses['MassMessageListContentHandler'] = __DIR__ . '/MassMessageListContentHandler.php';

// ContentHandler
$wgContentHandlers['MassMessageListContent'] = 'MassMessageListContentHandler';

// Hooks
$wgHooks['SkinTemplateNavigation'][] = 'MassMessageBackendHooks::onSkinTemplateNavigation';

// Special page
$wgSpecialPages['ManageMassMessageList'] = 'SpecialManageMassMessageList';
