<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use League\OAuth2\Client\Provider\GenericProvider;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Firestore;
use App\Helpers\AzureHelpers;
use App\Helpers\FirebaseHelpers;

class LoginController extends Controller
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
    public function index() {
        return view('login')->with([
            'loginUrl' => $this->getAuthorizationUrl()
        ]);
    }

    public function callback(Request $request) {
        $code = $request->query('code');

        if (!isset($code)) {
            return view('callback')->with([
                'error' => 'Invalid code!'
            ]);
        }

        try {
            // Get an user object from the code
            $user = $this->getUserFromCode($code);
            // Store the user object in firebase
            $this->putUserToFirebase($this->firestore, $user);
            // Generate a firebase auth key
            $key = $this->generateKeyFromUser($this->auth, $user);

            // Return to login with the new auth code to login to firebase
            return view('callback')->with([
                'authCode' => $key
            ]);
        } catch(\Exception $e) {
            throw $e;
            return view('callback')->with([
                'error' => 'Invalid code! ' . $e->getMessage()
            ]);
        }

    }
}
