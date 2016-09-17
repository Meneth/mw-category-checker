<?php

class SpecialCategoryChecker extends SpecialPage {
	public function __construct() {
		parent::__construct( 'CategoryChecker', 'autopatrol' );
	}
	
	function execute( $par ) {
		if (  !$this->userCanExecute( $this->getUser() )  ) {
			$this->displayRestrictionError();
			return;
		}
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();
		
		$output->addWikiText( "The following pages have no versioning category. Disambiguation, patch, wiki, and mod pages are excluded.\n" );
		
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
		"page",
		["page_title", "page_id"],
		[
			"page_namespace" => "0",
			"page_is_redirect" => "0"
		]
		);
		
		$filtered = [];
		
		foreach( $res as $row ) {
			$page = WikiPage::newFromID( $row->page_id );
			
			if ( self::showPage( $page ) ) {
				$filtered[] = $page;
			}
		}
		
		$out = "";
		foreach( $filtered as $aPage ) {
			$out .= "# [[" . $aPage->getTitle()->getText() . "]]\n";
		}
		$output->addWikiText( $out );
	}
	
	static function showPage( WikiPage $page ) {
		$categories = $page->getCategories();
		foreach( $categories as $category ) {
			if ( $category->getText() == "Disambiguation"
			|| $category->getText() == "Patches"
			|| $category->getText() == "Wiki"
			|| $category->getText() == "Mods" ) {
				return false;
			}
			$categoryPage = WikiPage::factory( $category );
			foreach( $categoryPage->getCategories() as $category2 ) {
				if ( $category2->getText() == "Articles by version"
				|| $category2->getText() == "Mods" ) {
					return false;
				}
			}
		}
		return true;
	}
}