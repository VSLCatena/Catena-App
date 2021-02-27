<?php
namespace App\Helpers;

use League\OAuth2\Client\Provider\GenericProvider;
use App\Models\User;
use Illuminate\Support\Facades\Log;

trait AzureHelpers {

    /** Creates an authorization url */
    function getAuthorizationUrl($asApi = false): String {
        return $this->createOAuthClient(false, $asApi)->getAuthorizationUrl();
    }

    /**
     * Gets a user object from an OAuth code.
     */
    function getUserFromCode($code, $asApi = false) {
        $oauthClient = $this->createOAuthClient(false, $asApi);

        // Get an accessToken from the code we received
        $accessToken = $oauthClient->getAccessToken('authorization_code', [
            'code' => $code,
        ]);

        // Info about the user itself
        $meResponse = $oauthClient->getResourceOwner($accessToken);
        $meArray = $meResponse->toArray();

        // Info about the committees the user is part of
        $memberGroupsRequest = $oauthClient->getAuthenticatedRequest(
            'POST',
            'https://graph.microsoft.com/v1.0/users/' . $meResponse->getId() . '/getMemberGroups',
            $accessToken,
            [
                'headers' => ['Content-type' => 'application/json'],
                'body' => '{ "securityEnabledOnly": true }',
            ]
        );
        $memberGroupsResponse = array_values($oauthClient->getParsedResponse($memberGroupsRequest)['value']);

        // Get a list of only valid committees the user is in as the response from the graph API above can be very messy.
        // This is because it will also return stuff like member groups
        $userCommittees = $this->getValidCommittees($memberGroupsResponse);
        // Map them to just their id's 
        $userCommitteeIds = array_values(
            array_map(function($committee) {
                return $committee['id'];
            }, $userCommittees)
        );

        // Create and return the user
        return new User(
            $meResponse->getId(),
            $meArray['displayName'],
            '???',
            $userCommitteeIds,
        );
    }

    /** Returns a list of valid committees */
    function getValidCommittees($arrayOfIds) {
        $allCommittees = $this->getAllCommittees();
        
        // Filter only the committees whose id's can be found in the list of id's
        return array_filter($allCommittees, function($committee) use($arrayOfIds) {
            return in_array($committee['id'], $arrayOfIds);
        });
    }
    
    /**
     * Ease-of-use function to get all the committees in Azure.
     */
    function getAllCommittees() {
        $oauthClient = $this->createOAuthClient(true);

        // Get a client credentials access token from Azure
        $accessToken = $oauthClient->getAccessToken('client_credentials', [
            'scope' => 'https://graph.microsoft.com/.default',
        ]);


        // Do a request to Azure to get a list of all committees
        $request = $oauthClient->getAuthenticatedRequest(
            'GET',
            'https://graph.microsoft.com/v1.0/groups/' . env('AD_PARENT_COMMITTEE_GROUP') . '/members?$select=id,displayName,description,mail',
            $accessToken,
        );

        // Parse the response
        $response = $oauthClient->getParsedResponse($request);
        
        // The committees from Azure
        $committees = $response['value'];
        // Filter out the items which don't have a displayName (They're also included and are weird and empty)
        $committees = array_filter($committees, function($item) {
            return $item['displayName'] != null;
        });

        return $committees;
    }

    /**
     * Creates an OAuth object with or without the clientSecret.
     * I found out that if you try to work with an user OAuth object that contains a clientSecret
     * that it will throw a weird exception.
     * 
     * Secret is only used for OAuth calls that are called on behalf of the server, not the user.
     */
    private function createOAuthClient($withSecret = false, $asApi = false): GenericProvider {
        $options = [
            'clientId'                => env('OAUTH_APP_ID'),
            'redirectUri'             => $asApi ? env('OAUTH_API_REDIRECT_URI') : env('OAUTH_REDIRECT_URI'),
            'urlAuthorize'            => env('OAUTH_AUTHORIZE_ENDPOINT'),
            'urlAccessToken'          => env('OAUTH_TOKEN_ENDPOINT'),
            'urlResourceOwnerDetails' => 'https://graph.microsoft.com/v1.0/me',
            'scopes'                  => env('OAUTH_SCOPES'),
        ];

        if ($withSecret)
            $options['clientSecret'] = env('OAUTH_APP_PASSWORD');

        return new GenericProvider($options);
    }
}