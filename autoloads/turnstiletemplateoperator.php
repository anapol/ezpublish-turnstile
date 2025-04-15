<?php
/**
 * Turnstile CAPTCHA extension for eZ Publish
 * Based on the reCAPTCHA extension by Bruce Morrison <bruce@stuffandcontent.com>
 * Adapted for Turnstile by [Your Name/Company]
 * Copyright (C) 2008. Bruce Morrison. All rights reserved.
 * Copyright (C) [Current Year] [Your Name/Company]. All rights reserved.
 * http://www.stuffandcontent.com
 * [Your Website]
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

class TurnstileTemplateOperator {
	
	var $Operators;

	function TurnstileTemplateOperator()
	{
		$this->Operators = array( 'turnstile_get_html' );
	}


	function &operatorList()
	{
		return $this->Operators;
	}

	function namedParameterPerOperator()
	{
		return true;
	}

	function namedParameterList()
	{
		return array( 
			'turnstile_get_html' => array(), 
		);
	}

	function modify( &$tpl, &$operatorName, &$operatorParameters, &$rootNamespace, &$currentNamespace, &$operatorValue, &$namedParameters )
	{
		switch( $operatorName )
		{
			case 'turnstile_get_html':
				// No external library needed for Turnstile HTML generation

				// Retrieve the Turnstile Site key from the ini file
				$ini = eZINI::instance( 'turnstile.ini' );
				$siteKey = $ini->variable( 'Keys', 'SiteKey' );
				if ( is_array($siteKey) )
				{
					$hostname = eZSys::hostname();
					if (isset($siteKey[$hostname]))
						$siteKey = $siteKey[$hostname];
					else {
                        // Fallback to the first key if no hostname match (or handle error)
                        $keyArray = array_values($siteKey);
                        $siteKey = $keyArray[0] ?? null;
                    }
				}

                if (empty($siteKey) || $siteKey === 'Enter your Site Key here') {
                    eZDebug::writeError("Turnstile Site Key is not configured. Cannot display widget.", __METHOD__);
                    $operatorValue = '<!-- Turnstile Site Key not configured -->';
                    break;
                }

				// check if the current user is able to bypass filling in the captcha and
				// return nothing so that no captcha is displayed
				$currentUser = eZUser::currentUser();
				$accessAllowed = $currentUser->hasAccessTo( 'turnstile', 'bypass_captcha' );
				if ( isset($accessAllowed["accessWord"]) && $accessAllowed["accessWord"] == 'yes') {
					$operatorValue = '<!-- User bypasses CAPTCHA -->';
                } else {
					// Generate the Turnstile widget HTML
					$turnstileScript = '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
                    $turnstileDiv = '<div class="cf-turnstile" data-sitekey="' . htmlspecialchars($siteKey) . '"></div>';
					$operatorValue = $turnstileScript . "\n" . $turnstileDiv;
                }
				break;
		}
	}
};

?>

