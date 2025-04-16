<?php
/**
 * Turnstile CAPTCHA extension for eZ Publish
 * Based on the reCAPTCHA extension by Bruce Morrison <bruce@stuffandcontent.com>
 * Adapted for Turnstile by anapol s.r.o.
 * Copyright (C) 2008. Bruce Morrison. All rights reserved.
 * Copyright (C) 2025 anapol s.r.o. All rights reserved.
 * http://www.stuffandcontent.com
 * https://anapol.cz
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

// Include the super class file
include_once( "kernel/classes/ezdatatype.php" );
// No external library needed for Turnstile verification

// Define the name of datatype string
define( "EZ_DATATYPESTRING_TURNSTILE", "turnstile" );


class turnstileType extends eZDataType
{
  /*!
   Construction of the class, note that the second parameter in eZDataType
   is the actual name showed in the datatype dropdown list.
  */
  function turnstileType()
  {
    $this->eZDataType( EZ_DATATYPESTRING_TURNSTILE, "Turnstile",
                           array( 'serialize_supported' => false,
                                  'translation_allowed' => false ) );
  }

  /*!
    Validates the input and returns true if the input was
    valid for this datatype.
  */
  function validateObjectAttributeHTTPInput( $http, $base,
                                               $objectAttribute )
  {
    $classAttribute = $objectAttribute->contentClassAttribute();

    $ini = eZINI::instance( 'turnstile.ini' );
    $newOjbectsOnly = $ini->variable( 'PublishSettings', 'NewObjectsOnly' ) == 'true';

    // If editing an existing object and NewObjectsOnly is true, skip validation
    if ( $newOjbectsOnly && $objectAttribute->attribute( 'object' )->attribute( 'status' ) == eZContentObject::STATUS_PUBLISHED )
       return eZInputValidator::STATE_ACCEPTED;

    // Skip validation if it's just information collection (e.g., preview) or if Turnstile validation passes
    if ( $classAttribute->attribute( 'is_information_collector' ) or self::turnstileValidate($http) )
      return eZInputValidator::STATE_ACCEPTED;

    // Use a more generic error message or one specific to Turnstile
    $objectAttribute->setValidationError(ezpI18n::tr( 'extension/turnstile', "The CAPTCHA challenge failed. Please try again." ));
    return eZInputValidator::STATE_INVALID;
  }

  function validateCollectionAttributeHTTPInput( $http, $base, $objectAttribute )
  {
    if (self::turnstileValidate($http))
      return eZInputValidator::STATE_ACCEPTED;

    $objectAttribute->setValidationError(ezpI18n::tr( 'extension/turnstile', "The CAPTCHA challenge failed. Please try again." ));
    return eZInputValidator::STATE_INVALID;
  }

  function isIndexable()
  {
    return false;
  }

  function isInformationCollector()
  {
    return true;  // Changed from false to enable information collection
  }

  function hasObjectAttributeContent( $contentObjectAttribute )
  {
    return false; // Turnstile widget itself doesn't store content
  }

  static function turnstileValidate( $http )
  {
    // check if the current user is able to bypass filling in the captcha and
    // return true without checking if so
    $currentUser = eZUser::currentUser();
    $accessAllowed = $currentUser->hasAccessTo( 'turnstile', 'bypass_captcha' );
    if ( isset($accessAllowed["accessWord"]) && $accessAllowed["accessWord"] == 'yes')
      return true;

    $ini = eZINI::instance( 'turnstile.ini' );
    // If SecretKey is an array try and find a match for the current host
    $secretKey = $ini->variable( 'Keys', 'SecretKey' );
    if ( is_array($secretKey) )
    {
      $hostname = eZSys::hostname();
      if (isset($secretKey[$hostname]))
        $secretKey = $secretKey[$hostname];
      else {
          // Fallback to the first key if no hostname match (or handle error)
          $keyArray = array_values($secretKey);
          $secretKey = isset($keyArray[0]) ? $keyArray[0] : null;
      }
    }

    if (empty($secretKey) || $secretKey === 'Enter your Secret Key here') {
         eZDebug::writeError("Turnstile Secret Key is not configured.", __METHOD__);
         return false; // Cannot validate without a secret key
    }


    $turnstileToken = $http->postVariable('cf-turnstile-response');

    if ( empty($turnstileToken) ) {
        eZDebug::writeNotice("Turnstile response token not found in POST data.", __METHOD__);
        return false; // No token submitted
    }

    $remoteIp = eZSys::clientIP(); // Use eZSys utility function

    // Prepare data for Cloudflare API
    $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $postData = http_build_query([
        'secret' => $secretKey,
        'response' => $turnstileToken,
        'remoteip' => $remoteIp
    ]);

    // Use cURL or file_get_contents with stream context for the POST request
    // Using file_get_contents for simplicity here, consider cURL for more options/robustness
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => $postData,
            'timeout' => 5 // Add a timeout
        ],
         // Add SSL context if needed, especially on older PHP versions or strict environments
         "ssl" => [
            "verify_peer" => true,
            "verify_peer_name" => true,
        ],
    ];
    $context  = stream_context_create($options);
    $response = @file_get_contents($verifyUrl, false, $context);

    if ($response === FALSE) {
        eZDebug::writeError("Failed to connect to Turnstile verification server.", __METHOD__);
        // Decide how to handle connection errors - fail open (true) or closed (false)?
        // Failing closed is generally safer for CAPTCHA.
        return false;
    }

    $responseData = json_decode($response, true);

    if ($responseData === null || !isset($responseData['success'])) {
         eZDebug::writeError("Invalid response received from Turnstile verification server.", __METHOD__);
         eZDebug::writeDebug("Turnstile Response: " . $response, __METHOD__);
         return false;
    }

    if ($responseData['success'] !== true) {
        eZDebug::writeNotice("Turnstile verification failed.", __METHOD__);
        if (isset($responseData['error-codes']) && is_array($responseData['error-codes'])) {
             eZDebug::writeNotice("Turnstile error codes: " . implode(', ', $responseData['error-codes']), __METHOD__);
        }
        return false;
    }

    // Verification successful
    return true;
  }

}
eZDataType::register( EZ_DATATYPESTRING_TURNSTILE, "turnstileType" );
