<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Storage;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Log;
use App\Models\User;
class FCMService
{
    protected $client;
    protected $projectId;
    protected $serviceAccount;

    public function __construct()
    {
        $this->client = new Client();
        $this->projectId = env('FIREBASE_PROJECT_ID');
        $this->serviceAccount = json_decode(Storage::disk('private')->get('firebase/'.env('FIREBASE_JSON_FILE')), true);
    }

    /**
     * Send a notification to a specific device.
     *
     */
    public function sendToDevice(string $deviceToken, string $title, string $body, array $data = [])
    {

        $device_type = User::where('device_token', $deviceToken)->value('device_type');
        $url = 'https://fcm.googleapis.com/v1/projects/' . $this->projectId . '/messages:send';

        if ($device_type === 'ios') {
             $message = [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data,
                ],
            ];
        }else{
            $data['title'] = $title;
            $data['body'] = $body;
            $message = [
                'message' => [
                    'token' => $deviceToken,
                    'data' => $data,
                ],
            ];
        }
        $res = $this->sendRequest($url, $message);
        return $res;
    }

    /**
     * Send the request to Firebase.
     */
    protected function sendRequest(string $url, array $message)
    {
        $clientEmail = $this->serviceAccount['client_email'];
        $privateKey = $this->serviceAccount['private_key'];

        $token = $this->createCustomToken($clientEmail, $privateKey);
        
        $client = new Client();
        try {
            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $message,
            ]);
            return response()->json(['success' => 1, 'response' => json_decode($response->getBody(), true)]);
        } catch (GuzzleException $e) {
            return response()->json(['success' => 0, 'error' => $e->getMessage()]);
        }

    }

    /**
     * Create a custom token for Firebase authentication.
     *
     * @param string $clientEmail
     * @param string $privateKey
     * @return string
     */
    protected function createCustomToken(string $clientEmail, string $privateKey): string
    {
        $client = new GoogleClient();
        $client->setAuthConfig(storage_path('app/private/firebase/'.env('FIREBASE_JSON_FILE'))); 
        $client->addScope('https://www.googleapis.com/auth/cloud-platform');

        $token = $client->fetchAccessTokenWithAssertion();
        return $token['access_token'];
        
    }
}