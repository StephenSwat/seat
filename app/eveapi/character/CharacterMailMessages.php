<?php

namespace Seat\EveApi\Character;

use Seat\EveApi\BaseApi;
use Pheal\Pheal;

class MailMessages extends BaseApi {

	public static function Update($keyID, $vCode)
	{

		// Start and validate they key pair
		BaseApi::bootstrap();
		BaseApi::validateKeyPair($keyID, $vCode);

		// Set key scopes and check if the call is banned
		$scope = 'Char';
		$api = 'MailMessages';

		if (BaseApi::isBannedCall($api, $scope, $keyID))
			return;

		// Get the characters for this key
		$characters = BaseApi::findKeyCharacters($keyID);

		// Check if this key has any characters associated with it
		if (!$characters)
			return;

		// Lock the call so that we are the only instance of this running now()
		// If it is already locked, just return without doing anything
		if (!BaseApi::isLockedCall($api, $scope, $keyID))
			$lockhash = BaseApi::lockCall($api, $scope, $keyID);
		else
			return;

		// Next, start our loop over the characters and upate the database
		foreach ($characters as $characterID) {

			// Prepare the Pheal instance
			$pheal = new Pheal($keyID, $vCode);

			// Do the actual API call. pheal-ng actually handles some internal
			// caching too.
			try {
				
				$mail_messages = $pheal
					->charScope
					->MailMessages(array('characterID' => $characterID));

			} catch (\Pheal\Exceptions\APIException $e) {

				// If we cant get account status information, prevent us from calling
				// this API again
				BaseApi::banCall($api, $scope, $keyID, 0, $e->getCode() . ': ' . $e->getMessage());
			    return;

			} catch (\Pheal\Exceptions\PhealException $e) {

				throw $e;
			}

			// Check if the data in the database is still considered up to date.
			// checkDbCache will return true if this is the case
			if (!BaseApi::checkDbCache($scope, $api, $mail_messages->cached_until, $characterID)) {

				// Loop over the list we got from the api and update the db,
				// remebering the messageID's for downloading the bodies too
				$bodies = array();
				foreach ($mail_messages->messages as $message) {

					$mail_body_data = \EveCharacterMailMessages::where('characterID', '=', $characterID)
						->where('messageID', '=', $message->messageID)
						->first();

					if (!$mail_body_data) {

						$mail_body = new \EveCharacterMailMessages;
						$bodies[] = $message->messageID; // Record the messagID to download later
					} else {

						// Check if we have the body for this existing message, else
						// we will add it to the list to download
						if (!\EveCharacterMailBodies::where('messageID', '=', $message->messageID))
							$bodies[] = $message->messageID;

						continue;
					}

					$mail_body->characterID = $characterID;
					$mail_body->messageID = $message->messageID;
					$mail_body->senderID = $message->senderID;
					$mail_body->senderName = $message->senderName;
					$mail_body->sentDate = $message->sentDate;
					$mail_body->title = $message->title;
					$mail_body->toCorpOrAllianceID = (strlen($message->toCorpOrAllianceID) > 0 ? $message->toCorpOrAllianceID : null);
					$mail_body->toCharacterIDs = (strlen($message->toCharacterIDs) > 0 ? $message->toCharacterIDs : null);
					$mail_body->toListID = (strlen($message->toListID) > 0 ? $message->toListID : null);
					$mail_body->save();
				}

				// Split the bodies we need to download into chunks of 10 each. Pheal-NG will
				// log the whole request as a file name for chaching...
				// which is tooooooo looooooooooooong
				$bodies = array_chunk($bodies, 10);

				// Iterate over the chunks.
				foreach ($bodies as $chunk) {

					try {
						
						$mail_bodies = $pheal
							->charScope
							->MailBodies(array('characterID' => $characterID, 'ids' => implode(',', $chunk)));

					} catch (\Pheal\Exceptions\PhealException $e) {

						throw $e;
					}

					// Loop over the received bodies
					foreach ($mail_bodies->messages as $body) {

						// Actually, this check is pretty redundant, so maybe remove it
						$body_data = \EveCharacterMailBodies::where('messageID', '=', $body->messageID)->first();

						if (!$body_data)
							$new_body = new \EveCharacterMailBodies;
						else
							continue;
					
						$new_body->messageID = $body->messageID;
						$new_body->body = $body->__toString();
						$new_body->save();	
					}
				}

				// Update the cached_until time in the database for this api call
				BaseApi::setDbCache($scope, $api, $mail_messages->cached_until, $characterID);
			}
		}

		// Unlock the call
		BaseApi::unlockCall($lockhash);

		return $mail_messages;
	}
}
