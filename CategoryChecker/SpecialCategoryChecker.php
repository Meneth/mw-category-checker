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
		
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
		"page",
		["page_title", "page_id"],
		[
			"page_namespace" => "0",
			"page_is_redirect" => "0"
		]
		);
		
		$output->addWikiText( "The following pages have no versioning category. Disambiguation, patch, wiki, and mod pages are excluded.\n" );
		$out = "";
		foreach( $res as $row ) {
			$page = WikiPage::newFromID( $row->page_id );
			
			if ( self::showVersionPage( $page ) ) {
				self::appendPage( $page, $out );
			}
		}
		$output->addWikiText( $out );
		
		$output->addWikiText( "\nThe following pages have no assessment.\n" );
		$out = "";
		foreach( $res as $row ) {
			$page = WikiPage::newFromID( $row->page_id );
			
			if ( self::showAssessmentPage( $page ) ) {
				self::appendPage( $page, $out );
			}
		}
		$output->addWikiText( $out );
		
		$output->addWikiText( "\nThe following pages are uncategorized (needing editing, verisoning, and hidden categories not considered).\n" );
		$out = "";
		foreach( $res as $row ) {
			$page = WikiPage::newFromID( $row->page_id );
			
			if ( self::showCategoryPage( $page ) ) {
				self::appendPage( $page, $out );
			}
		}
		$output->addWikiText( $out );
	}
	
	static function showVersionPage( WikiPage &$page ) {
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
	
	static function showAssessmentPage( WikiPage &$page ) {
		$talkPage = WikiPage::factory( $page->getTitle()->getTalkPage() );
		if ( !$talkPage->exists() ) {
			return true;
		}
		$categories = $talkPage->getCategories();
		foreach( $categories as $category ) {
			$categoryPage = WikiPage::factory( $category );
			foreach( $categoryPage->getCategories() as $category2 ) {
				if ( $category2->getText() == "Articles by quality" ) {
					return false;
				}
			}
		}
		return true;
	}
	
	static function showCategoryPage( WikiPage &$page ) {
		$categories = $page->getCategories();
		foreach( $categories as $category ) {
			$categoryPage = WikiPage::factory( $category );
			if ( !$categoryPage->getCategories()->valid() ) {
				return false; // Assume uncategorized categories are valid
			}
			foreach( $categoryPage->getCategories() as $category2 ) {
				if ( $category2->getText() != "Articles by version"
				&& $category2->getText() != "Need editing"
				&& $category2->getText() != "Hidden categories" ) {
					return false;
				}
			}
		}
		return true;
	}
	
	static function appendPage( &$page, &$out ) {
		$out .= "# [[" . $page->getTitle()->getText() . "]]\n";
	}
}