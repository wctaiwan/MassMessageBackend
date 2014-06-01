<?php

class SpecialManageMassMessageList extends FormSpecialPage {

	protected $titleText;
	protected $isTitleValid;

	public function __construct() {
		parent::__construct( 'ManageMassMessageList' );
	}

	protected function setParameter( $par ) {
		if ( $par === null || $par === '' ) {
			$this->titleText = '';
			$this->isTitleValid = true;
		} else {
			$title = Title::newFromText( $par );

			if ( $title !== null && $title->exists()
				&& $title->hasContentModel( 'MassMessageListContent' )
			) {
				$this->titleText = $title->getText(); // Use the canonical form.
				$this->isTitleValid = true;
			} else {
				$this->isTitleValid = false;
			}
		}
	}

	protected function getFormFields() {
		$fields = array();

		// If the title is valid or empty
		if ( $this->isTitleValid ) {
			$fields['title'] = array(
				'type' => 'text',
				'label-message' => 'massmessage-manage-title',
			);
			$fields['content'] = array(
				'type' => 'textarea',
				'label-message' => 'massmessage-manage-content'
			);

			// If modifying an existing list
			if ( $this->titleText !== '' ) {
				// Set the title and prevent modification.
				$fields['title']['default'] = $this->titleText;
				$fields['title']['disabled'] = true;

				// Set the default content.
				$page = WikiPage::factory( Title::newFromText( $this->titleText ) );
				$fields['content']['default'] = $page->getContent()->getNativeData();
			}
		}
		return $fields;
	}

	protected function alterForm( HTMLForm $form ) {
		// Hide the form if the title is invalid.
		if ( !$this->isTitleValid ) {
			$form->setWrapperLegend( false );
			$form->suppressDefaultSubmit( true );
		}
	}

	protected function preText() {
		if ( $this->isTitleValid ) {
			$msgKey = 'massmessage-manage-header';
		} else {
			$msgKey = 'massmessage-manage-invalidtitle';
		}
		return '<p>' . $this->msg( $msgKey )->text() . '</p>';
	}

	public function onSubmit( array $data ) {
		$title = Title::newFromText( $data['title'] );
		if ( !$title ) {
			return Status::newFatal( 'massmessage-manage-invalidtitle' );
		} else if ( $title->exists() && $this->titleText === '' ) {
			return Status::newFatal( 'massmessage-manage-exists' );
		} else if ( !$title->userCan( 'edit' ) || !$title->exists() && !$title->userCan( 'create' )	) {
			return Status::newFatal( 'massmessage-manage-nopermission' );
		}

		$content = new MassMessageListContent( $data['content'] );

		$page = WikiPage::factory( $title );
		$result = $page->doEditContent(
			$content,
			$this->msg( 'massmessage-manage-editsummary' )->text()
		);
		return $result;
	}

	public function onSuccess() {
		$this->getOutput()->addHTML(
			$this->msg( 'massmessage-manage-success' )->parse()
		);
	}
}
