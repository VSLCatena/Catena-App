<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Firestore;
use App\Helpers\ConnectionHelpers;

class LoginController extends Controller
{
    use ConnectionHelpers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Auth $auth, Firestore $firestore) {
        $this->auth = $auth;
        $this->firestore = $firestore;
    }
    
    public function callback(Request $request) {
        $code = $request->query('code');

        if (!isset($code)) {
            return 'Invalid code!';
        }

        try {
            // Get an user object from the code
            $user = $this->getUserFromCode($code);
            // Store the user object in firebase
            $this->putUserToFirebase($this->firestore, $user);
            // Generate a firebase auth key and return it
            return $this->generateKeyFromUser($this->auth, $user);
        } catch(\Exception $e) {
            return 'Invalid code!';
        }
    }
}
