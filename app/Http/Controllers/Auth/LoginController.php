<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Socialite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\Client;

class LoginController extends Controller
{
    /**
     * Redirect the user to the Okta authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    protected $httpClient;
    public function __construct(){
        $this->httpClient = new Client([
            'base_uri' => 'https://dev-79450819.okta.com',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }
    public function redirectToProvider(Request $req)
    {   
        // dd(Socialite::driver('okta'));
        // return Socialite::driver('okta')->redirect();
        // $name = $req->name;
       
        // $email = $req->email;
        // $password = $req->password;
        
        // $response = Http::withHeaders([
            
        //     // 'Authorization' => 'SSWS 00yMjb23vuBHrmOmOxGoQTOWhDtPKGWTiiks9thdL9',
        //     "Accept" => "application/json",
        //     'Content-Type' => 'application/json',
        // ])
        // ->post('https://dev-79450819.okta.com/api/v1/authn', [
            
        //     'username'=>$email,
        //     'password'=>$password,
        //     'options'=>[
        //         'multiOptionalFactorEnroll'=>'false',
        //         'warnBeforePasswordExpired'=>'false',
        //     ]
        //     // Additional data if needed
        // ]);
        // $responseData = json_decode($response->getBody(), true);
        // dd($responseData);
        // $status_code = $response->getStatusCode();
        // if($status_code == 200){

           
        //     $user_v = User::where('email',$email)->first();
            
        //     if($user_v && Hash::check($password,$user_v->password)){
        //         Auth::login($user_v);
        //         return response()->json(['msg'=>'Success']);
        //     }else{
        //         return response()->json(['msg'=>'Failed']);
        //     }
        // }else{
        //     return response()->json(['msg'=>'Failed']);
        // }
        // ----------------------------------------------------------
        
        $authUrl = 'https://dev-79450819.okta.com/oauth2/v1/authorize?' . http_build_query([
            'response_type' => 'code',
            'client_id' => '0oaemgpnn2OOWXgvd5d7',
            'redirect_uri' => 'http://127.0.0.1:8000/authorization-code/callback',
            'scope' => 'openid profile email',
            'state' => $req->session()->get('_token'),
        ]);

        return redirect()->away($authUrl);
        
    }

    /**
     * Obtain the user information from Okta.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback(\Illuminate\Http\Request $request)
    {
        dd($request);
        $response = $this->httpClient->post('/oauth2/v1/token', [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $request->input('code'),
                'redirect_uri' => 'http://127.0.0.1:8000/authorization-code/callback',
                'client_id' => '0oaemgpnn2OOWXgvd5d7',
                'client_secret' => 'OWkN7qEcC1VWcTIcoi5wzwBbpwkClcEoCBIg0kfKqDlhtW09rT-01C2pVgqx8YKn',
            ],
        ]);
        $user1 = json_decode($response->getBody(), true);
        $user = '';
       if($response->getStatusCode() == '200'){
        $accessToken = json_decode($response->getBody(), true)['access_token'];
        $response1 = $this->httpClient->post('/oauth2/v1/introspect', [
            'form_params' => [
               'token'=>$accessToken,
               'token_type_hint'=>'access_token'
            ],
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("0oaemgpnn2OOWXgvd5d7:OWkN7qEcC1VWcTIcoi5wzwBbpwkClcEoCBIg0kfKqDlhtW09rT-01C2pVgqx8YKn")
            ]
        ]);
        $user = json_decode($response1->getBody(), true);
        
       }
        
        $localUser = User::where('email', $user['username'])->first();

        // create a local user with the email and token from Okta
        if (! $localUser) {
            return redirect('/');
        } else {
            // if the user already exists, just update the token:
            $localUser->token = $accessToken;
            $localUser->save();
        }

        try {
            Auth::login($localUser);
        } catch (\Throwable $e) {
            return redirect('/login-okta');
        }

        return redirect('/home');
    }
    // public function handleProviderCallback(\Illuminate\Http\Request $request)
    // {
    //     dd(Socialite::driver('okta'));
    //     $user = Socialite::driver('okta')->user();
    //     if(Session::has('id_token')){
           
    //         Session::forget('id_token');
    //     }
    //     Session::put('id_token',$user->accessTokenResponseBody['id_token']);
    //     //    dd($user->accessTokenResponseBody['id_token']." | ".Session::get('id_token'));
    //     $localUser = User::where('email', $user->email)->first();

    //     // create a local user with the email and token from Okta
    //     if (! $localUser) {
    //         return redirect('/');
    //     } else {
    //         // if the user already exists, just update the token:
    //         $localUser->token = $user->token;
    //         $localUser->save();
    //     }

    //     try {
    //         Auth::login($localUser);
    //     } catch (\Throwable $e) {
    //         return redirect('/login-okta');
    //     }

    //     return redirect('/home');
    // }

    public function register_post(Request $req){
        $name = $req->name;
        $last_name = explode('.',$name)[1];
        $first_name = explode('.',$name)[0];
        $email = $req->email;
        $password = $req->password;
        
        $response = Http::withHeaders([
            
           
            "Accept" => "application/json",
            'Content-Type' => 'application/json',
            'Authorization' => 'SSWS 00yMjb23vuBHrmOmOxGoQTOWhDtPKGWTiiks9thdL9',
        ])
        ->post('https://dev-79450819.okta.com/api/v1/users?activate=true', [
            'profile' => [
                'firstName'=>$first_name,
                'lastName'=>$last_name,
                'email'=>$email,
                'login'=>$email,
                "mobilePhone"=> "555-415-1337"
            ],
            'credentials'=>[
                'password'=>[
                    'value'=>$password,
                ],
            ]
            // Additional data if needed
        ]);
        $responseData = json_decode($response->getBody(), true);
        // dd($responseData);
        $okta_id = $responseData['id'];

        // $response1 = Http::withHeaders([
            
        //     'Authorization' => 'SSWS 00yMjb23vuBHrmOmOxGoQTOWhDtPKGWTiiks9thdL9',
        //     "Accept" => "application/json",
        //     'Content-Type' => 'application/json',
        // ])
        // ->post('https://dev-79450819.okta.com/api/v1/users/'.$okta_id.'/lifecycle/activate?sendEmail=false');
       
        // $responseData1 = json_decode($response1->getBody(), true);
        // dd($responseData1);
        $data = User::create([
            'name'=>$name,
            'email'=>$email,
            'password'=>Hash::make($password),
            'token'=>'',
            'remember_token'=>$okta_id,
        ]);
        return response()->json(['msg'=>'Success']);
    }
    public function logout(Request $request)
    {
               
        $redirectUri = urlencode(url('/')); // Redirect to home page after logout
         // Okta client credentials
        $clientId = '0oaemgpnn2OOWXgvd5d7';
        $clientSecret = 'OWkN7qEcC1VWcTIcoi5wzwBbpwkClcEoCBIg0kfKqDlhtW09rT-01C2pVgqx8YKn';

        // Instantiate Guzzle HTTP client
        $httpClient = new Client();
        try {
        // Send request to Okta logout endpoint with authentication credentials
        $response = $httpClient->get("https://dev-79450819.okta.com/oauth2/default/v1/logout", [
            'query' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'post_logout_redirect_uri' => $redirectUri
            ]
        ]);
        $user = json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            // Handle exception (e.g., display error message)
            return "Error: " . $e->getMessage();
        }
        Auth::logout();
        // Redirect the user to Okta logout endpoint
        // return redirect()->away($logoutUrl);
        
        
        
       
    }
   
}