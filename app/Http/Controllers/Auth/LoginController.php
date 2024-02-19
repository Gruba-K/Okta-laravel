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

class LoginController extends Controller
{
    /**
     * Redirect the user to the Okta authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct(){
        $this->middleware('web');
    }
    public function redirectToProvider(Request $req)
    {   
        // dd(Socialite::driver('okta')->redirect());
        // return Socialite::driver('okta')->redirect();
        $name = $req->name;
        // $last_name = explode('.',$name)[1];
        // $first_name = explode('.',$name)[0];
        $email = $req->email;
        $password = $req->password;
        
        $response = Http::withHeaders([
            
            // 'Authorization' => 'SSWS 00yMjb23vuBHrmOmOxGoQTOWhDtPKGWTiiks9thdL9',
            "Accept" => "application/json",
            'Content-Type' => 'application/json',
        ])
        ->post('https://dev-79450819.okta.com/api/v1/authn', [
            
            'username'=>$email,
            'password'=>$password,
            'options'=>[
                'multiOptionalFactorEnroll'=>'false',
                'warnBeforePasswordExpired'=>'false',
            ]
            // Additional data if needed
        ]);
        $responseData = json_decode($response->getBody(), true);
        $status_code = $response->getStatusCode();
        if($status_code == 200){

           
            $user_v = User::where('email',$email)->first();
            
            if($user_v && Hash::check($password,$user_v->password)){
                Auth::login($user_v);
                return response()->json(['msg'=>'Success']);
            }else{
                return response()->json(['msg'=>'Failed']);
            }
        }else{
            return response()->json(['msg'=>'Failed']);
        }
        
    }

    /**
     * Obtain the user information from Okta.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback(\Illuminate\Http\Request $request)
    {
        
        $user = Socialite::driver('okta')->user();
        if(Session::has('id_token')){
           
            Session::forget('id_token');
        }
        Session::put('id_token',$user->accessTokenResponseBody['id_token']);
        //    dd($user->accessTokenResponseBody['id_token']." | ".Session::get('id_token'));
        $localUser = User::where('email', $user->email)->first();

        // create a local user with the email and token from Okta
        if (! $localUser) {
            return redirect('/');
        } else {
            // if the user already exists, just update the token:
            $localUser->token = $user->token;
            $localUser->save();
        }

        try {
            Auth::login($localUser);
        } catch (\Throwable $e) {
            return redirect('/login-okta');
        }

        return redirect('/home');
    }
    public function register_post(Request $req){
        $name = $req->name;
        $last_name = explode('.',$name)[1];
        $first_name = explode('.',$name)[0];
        $email = $req->email;
        $password = $req->password;
        
        $response = Http::withHeaders([
            
            'Authorization' => 'SSWS 00yMjb23vuBHrmOmOxGoQTOWhDtPKGWTiiks9thdL9',
            "Accept" => "application/json",
            'Content-Type' => 'application/json',
        ])
        ->post('https://dev-79450819.okta.com/api/v1/users?activate=true', [
            'profile' => [
                'firstName'=>$first_name,
                'lastName'=>$last_name,
                'email'=>$email,
                'login'=>$email,
            ],
            'credentials'=>[
                'password'=>$password,
            ]
            // Additional data if needed
        ]);
        $responseData = json_decode($response->getBody(), true);
        $okta_id = $responseData['id'];
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
               
        $redirectUri = urlencode("http://127.0.0.1:8000/");

        // Extract parameters from the request or provide them manually
       
        $idToken = Session::get('id_token');
        $postLogoutRedirectUri = $redirectUri;
        

        // Build the URL with parameters
        $url = "https://dev-79450819.okta.com/oauth2/v1/logout?"
            . "id_token_hint=$idToken&"
            . "post_logout_redirect_uri=$postLogoutRedirectUri"
            ;

        // Send GET request to the logout endpoint
        // dd($url);
        $response = Http::get($url);
        // dd($response);
        Auth::logout();
        
        return redirect('/');
       
    }
   
}