<?php

class MassMessageBackendHooks {

	public static function onSkinTemplateNavigation( &$sktemplate, &$links ) {
		$title = $sktemplate->getTitle();
		if ( $title->getContentModel() === 'MassMessageListContent'
			&& array_key_exists( 'edit', $links['views'] )
		) {
			$links['views']['edit']['href'] = SpecialPage::getTitleFor(
				'ManageMassMessageList', $title
			)->getFullUrl();
		}
		return true;
	}
}
