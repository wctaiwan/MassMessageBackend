<?php

class MassMessageBackendHooks {

	/**
	 * Override the Edit tab for delivery lists
	 * @param SkinTemplate $sktemplaye
	 * @param array $links
	 * @return bool
	 */
	public static function onSkinTemplateNavigation( &$sktemplate, &$links ) {
		$title = $sktemplate->getTitle();
		if ( $title->getContentModel() === 'MassMessageListContent'
			&& array_key_exists( 'edit', $links['views'] )
		) {
			$links['views']['edit']['href'] = SpecialPage::getTitleFor(
				'EditMassMessageList', $title
			)->getFullUrl();
		}
		return true;
	}
}
