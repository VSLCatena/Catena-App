<?php

namespace App\Helpers;

use App\Models\User;

trait FirebaseHelpers {
    
    /**
     * Puts the given user object into firebase
     */
    function putUserToFirebase($firestore, User $user) {

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'memberNumber' => $user->memberNumber,
            'committees' => $user->committees,
        ];

        // Update user in firebase
        $database = $this->firestore->database();

        $database->collection('users')
            ->document($user->id)
            ->set($data);
    }

    /**
     * Generates an authentication key for the given user
     */
    function generateKeyFromUser($auth, $user) {
        return (string)$auth->createCustomToken($user->id);
    }
}