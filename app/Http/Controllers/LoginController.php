<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use League\OAuth2\Client\Provider\GenericProvider;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Firestore;

class LoginController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Auth $auth, Firestore $firestore) {
        $this->auth = $auth;
        $this->firestore = $firestore;
        $this->middleware('guest');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index() {
        $oauthClient = $this->createOAuthClient();
        return view('login')->with([
            'loginUrl' => $oauthClient->getAuthorizationUrl()
        ]);
    }
    
    public function callback(Request $request) {
        $code = $request->query('code');
        if (!isset($code)) {
            return view('login')->with('error', 'Something went wrong');
        }

        $oauthClient = $this->createOAuthClient();

        $accessToken = $oauthClient->getAccessToken('authorization_code', [
            'code' => $code,
        ]);

        // Info about the user itself
        $meRequest = $oauthClient->getAuthenticatedRequest(
            'GET',
            'https://graph.microsoft.com/v1.0/me',
            $accessToken
        );
        $meResponse = $oauthClient->getParsedResponse($meRequest);

        // Info about the committees the user is part of
        $committeesRequest = $oauthClient->getAuthenticatedRequest(
            'POST',
            'https://graph.microsoft.com/v1.0/users/' . $meResponse['id'] . '/getMemberGroups',
            $accessToken,
            [
                'headers' => ['Content-type' => 'application/json'],
                'body' => '{ "securityEnabledOnly": true }',
            ]
            );
        $committeesResponse = $oauthClient->getParsedResponse($committeesRequest);

        return '<pre>'.var_export(array($accessToken, $meResponse, $committeesResponse)).'</pre>';
    }

    private function createOAuthClient(): GenericProvider {
        return new GenericProvider([
            'clientId'                => env('OAUTH_APP_ID'),
            'redirectUri'             => env('OAUTH_REDIRECT_URI'),
            'urlAuthorize'            => env('OAUTH_AUTHORIZE_ENDPOINT'),
            'urlAccessToken'          => env('OAUTH_TOKEN_ENDPOINT'),
            'urlResourceOwnerDetails' => 'https://graph.microsoft.com/v1.0/me',
            'scopes'                   => env('OAUTH_SCOPES'),
          ]);
    }
}
