<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use League\OAuth2\Client\Provider\GenericProvider;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Firestore;
use App\Helpers\AzureHelpers;
use App\Helpers\FirebaseHelpers;

class ApiLoginController extends Controller
{
    use AzureHelpers;
    use FirebaseHelpers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Auth $auth, Firestore $firestore) {
        $this->auth = $auth;
        $this->firestore = $firestore;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function login() {
        return response()->redirectTo($this->getAuthorizationUrl(true));
    }

    public function callback(Request $request) {
        $code = $request->query('code');

        if (!isset($code)) {
            return response()->json([
                'error' => 'Invalid code!'
            ]);
        }

        try {
            // Get an user object from the code
            $user = $this->getUserFromCode($code, true);
            // Store the user object in firebase
            $this->putUserToFirebase($this->firestore, $user);
            // Generate a firebase auth key
            $key = $this->generateKeyFromUser($this->auth, $user);

            // Return to login with the new auth code to login to firebase
            return response()->json([
                'authCode' => $key
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'error' => 'Invalid code!'
            ]);
        }

    }
}
