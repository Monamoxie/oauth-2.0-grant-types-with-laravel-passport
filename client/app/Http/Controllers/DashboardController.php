<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DashboardController extends Controller
{

    public function index(Request $request)
    {
        // check if there's an access token for this user to fetch their posts from a resource server
        $userToken = auth()->user()->userOAuthToken;
        $userPosts = [];
        
        if($userToken !== null) {
            // make request to fetch posts with the token
            $resourceResponse = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '. $userToken->access_token
            ])->get(env('RESOURCE_APP_URL') . 'api/user/resource/posts');
             
            if($resourceResponse->status() === 200) {
                $userPosts = $resourceResponse->json();
            }
            
        }

        return view('dashboard', compact('userPosts'));
    }

    public function approveRequest(Request $request)
    {
        
        $sessionState = $request->session()->put('state', Str::random(40));
          
        $query = http_build_query([
            'client_id' => env('CLIENT_ID'),
            'redirect_uri' => env('APP_URL') . 'dashboard/oauth/callback',
            'response_type' => 'code',
            'scope' => 'view-posts view-users',
            'state' => $sessionState,
        ]);
        return redirect(env('RESOURCE_APP_URL') . 'oauth/authorize?' . $query);
    }

    public function requestCallback(Request $request)
    {    
     
        $resourceResponse = Http::post(env('RESOURCE_APP_URL') . 'oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => env('CLIENT_ID'),
            'client_secret' => env('CLIENT_SECRET'),
            'redirect_uri' => env('APP_URL') . 'dashboard/oauth/callback',
            'code' => $request->code,
        ]);
         
        $resourceResponse = json_decode($resourceResponse->getBody());

        if ($request->user()->userOAuthToken) {
            $request->user()->userOAuthToken()->delete();
        }
        
        $request->user()->userOAuthToken()->create([
            'access_token' => $resourceResponse->access_token
        ]);

        return redirect('/dashboard');

    }
}