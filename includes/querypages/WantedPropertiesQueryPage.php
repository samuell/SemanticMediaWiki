<?php

namespace SMW;

use Html;

/**
 * Query class that provides content for the Special:WantedProperties page
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author Markus Krötzsch
 * @author mwjames
 */

/**
 * Query class that provides content for the Special:WantedProperties page
 *
 * @ingroup QueryPage
 */
class WantedPropertiesQueryPage extends QueryPage {

	/** @var Store */
	protected $store;

	/** @var Settings */
	protected $settings;

	/** @var Collector */
	protected $collector;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param Settings $settings
	 */
	public function __construct( Store $store, Settings $settings ) {
		$this->store = $store;
		$this->settings = $settings;
	}

	/**
	 * @codeCoverageIgnore
	 * @return string
	 */
	function getName() {
		return "WantedProperties";
	}

	/**
	 * @codeCoverageIgnore
	 * @return boolean
	 */
	function isExpensive() {
		return false; /// disables caching for now
	}

	/**
	 * @codeCoverageIgnore
	 * @return boolean
	 */
	function isSyndicated() {
		return false; ///TODO: why not?
	}

	/**
	 * @codeCoverageIgnore
	 * @return string
	 */
	function getPageHeader() {
		return Html::element( 'p', array(), $this->msg( 'smw_wantedproperties_docu' )->text() );
	}

	/**
	 * @param $skin
	 * @param array $result First item is SMWDIProperty, second item is int
	 *
	 * @return string
	 */
	function formatResult( $skin, $result ) {

		$proplink = '';

		// Only display user-defined properties because it can happen that
		// custom predefined (fixed) properties are mixed within the result
		// (did not use their own fixedProperty table and therefore were
		// selected as well e.g _SF_PDF etc.)
		if ( $result[0]->isUserDefined() ) {
			$proplink = $this->getLinker()->link(
				$result[0]->getDiWikiPage()->getTitle(),
				htmlspecialchars( $result[0]->getLabel() ),
				array( 'action' => 'view' )
			);
		}

		return $proplink ? $this->msg( 'smw_wantedproperty_template', $proplink, $result[1] )->text() : '';
	}

	/**
	 * Get the list of results.
	 *
	 * @param SMWRequestOptions $requestOptions
	 * @return array of SMWDIProperty|SMWDIError
	 */
	function getResults( $requestoptions ) {
		$this->collector = $this->store->getWantedPropertiesSpecial( $requestoptions );
		return $this->collector->getResults();
	}
}