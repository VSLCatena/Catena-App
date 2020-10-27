<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\OAuth2\Client\Provider\GenericProvider;
use Kreait\Firebase\Firestore;
use App\Helpers\AzureHelpers;

class UpdateCommittees extends Command
{
    use AzureHelpers;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catena:updateCommittees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all committees in Firebase';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Firestore $firestore)
    {
        parent::__construct();
        $this->firestore = $firestore;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $azCommittees = $this->getAllCommittees();

        // Firebase
        $database = $this->firestore->database();
        $committeeCol = $database->collection('committees');

        // We start with an empty list, and if a firebase result isn't in our list of committees, we remove it later
        $committeeIdsToRemove = array();

        // We grab all the committees from firebase
        $fbCommittees = $committeeCol->documents();
        foreach ($fbCommittees as $snapshot) {
            $committeeId = $snapshot->id();

            // If we found the comittee in Azure
            $foundAz = count(array_filter($azCommittees, function($committee) use($committeeId) {
                return $committee['id'] == $committeeId;
            })) > 0;

            if (!$foundAz) {
                // If it wasn't found in Azure we want to delete it
                $committeeIdsToRemove[] = $committeeId;
            }
        }
        
        // We do it all as a batch, so we don't cause a million updates to people's phone
        $batch = $database->batch();

        // Delete all committees that doesn't exist anymore
        foreach ($committeeIdsToRemove as $committeeId) {
            $batch->delete($committeeCol->document($committeeId));
        }

        // Then we just replace all other committees
        foreach ($azCommittees as $azCommittee) {
            $batch->set(
                $committeeCol->document($azCommittee['id']), 
                [
                    'name' => $azCommittee['displayName'],
                    'description' => $azCommittee['description'],
                    'email' => $azCommittee['mail'],
                ]
            );
        }

        // And commit the batch
        $batch->commit();

        return 0;
    }

    private function createOAuthClient(): GenericProvider {
        return new GenericProvider([
            'clientId'                => env('OAUTH_APP_ID'),
            'clientSecret'            => env('OAUTH_APP_PASSWORD'),
            'redirectUri'             => env('OAUTH_REDIRECT_URI'),
            'urlAuthorize'            => env('OAUTH_AUTHORIZE_ENDPOINT'),
            'urlAccessToken'          => env('OAUTH_TOKEN_ENDPOINT'),
            'urlResourceOwnerDetails' => 'https://graph.microsoft.com/v1.0/me',
          ]);
    }
}
