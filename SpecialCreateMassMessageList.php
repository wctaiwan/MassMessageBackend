<?php

class SpecialCreateMassMessageList extends FormSpecialPage {

	public function __construct() {
		parent::__construct( 'CreateMassMessageList' );
	}

	/**
	 * @return array
	 */
	protected function getFormFields() {
		return array(
			'title' => array(
				'type' => 'text',
				'label-message' => 'massmessage-create-title',
			),
			'description' => array(
				'type' => 'textarea',
				'rows' => 5,
				'label-message' => 'massmessage-create-description',
			),
			'content' => array(
				'type' => 'radio',
				'options' => $this->getContentOptions(),
				'default' => 'empty',
				'label-message' => 'massmessage-create-content',
			),
			'source' => array(
				'type' => 'text',
				'disabled' => true,
				'label-message' => 'massmessage-create-source',
			),
		);
	}

	/**
	 * @param HTMLForm $form
	 */
	protected function alterForm( HTMLForm $form ) {
		$form->setWrapperLegendMsg( 'createmassmessagelist' );
	}

	/**
	 * @param array $data
	 * @return Status
	 */
	public function onSubmit( array $data ) {
		$title = Title::newFromText( $data['title'] );
		if ( !$title ) {
			return Status::newFatal( 'massmessage-create-invalidtitle' );
		} else if ( $title->exists() ) {
			return Status::newFatal( 'massmessage-create-exists' );
		} else if ( !$title->userCan( 'edit' ) || !$title->userCan( 'create' ) ) {
			return Status::newFatal( 'massmessage-create-nopermission' );
		}

		if ( $data['content'] === 'import' ) {

			// TODO: Implement importing from existing lists
			$targets = array();

		} else {
			$targets = array();
		}


		$jsonText = FormatJson::encode(
			array( 'description' => $data['description'], 'targets' => $targets )
		);
		if ( !$jsonText ) {
			return Status::newFatal( 'massmessage-create-tojsonerror' );
		}
		$content = new MassMessageListContent( $jsonText );

		$result = WikiPage::factory( $title )->doEditContent(
			$content,
			$this->msg( 'massmessage-create-editsummary' )->escaped()
		);
		if ( $result->isOK() ) {
			$this->getOutput()->redirect( $title->getFullUrl() );
		}
		return $result;
	}

	public function onSuccess() {
		// No-op: We have already redirected.
	}

	protected function getContentOptions() {
		$mapping = array(
			'massmessage-create-empty' => 'empty',
			'massmessage-create-import' => 'import',
		);

		$options = array();
		foreach ( $mapping as $msgKey => $option ) {
			$options[$this->msg( $msgKey )->escaped()] = $option;
		}
		return $options;
	}
}
