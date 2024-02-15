<?php

namespace App\Http\Controllers\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Socialite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Firebase\JWT\JWT;
class LoginController extends Controller
{
    /**
     * Redirect the user to the Okta authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider()
    {   
        // dd(Socialite::driver('okta')->redirect());
        return Socialite::driver('okta')->redirect();
        
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
            $localUser = User::create([
                'email' => $user->email,
                'password'=>'Pass@123',
                'name'  => $user->name,
                'token' => $user->token,
            ]);
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