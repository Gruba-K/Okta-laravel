<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @vite([
            'resources/js/app.js',
            'resources/sass/app.scss',
            'resources/css/app.css',
            
            
        ])
        
      
    </head>
    <body class="antialiased">
        <div class="row" style="height:100vh;">
            <div class="col-lg-12 d-flex justify-content-center align-items-center">
                <div class="card w-50 p-3">
           
                    <div class="card-head d-flex justify-content-between">
                         
                           
                            @if(Auth::check())
                            <a href="#" class="text-success fw-bold text-decoration-none">Welcome {{ Auth::user()->name }}</a>
                            <a href="{{ route('logout') }}" class="text-danger fw-bold text-decoration-none" style="border-left:2px solid white; padding-left:10px; margin-right:10px;">Log out</a>
                            @else
                            {{-- <a href="{{ url('login/okta') }}" class="text-success fw-bold text-decoration-none">Login</a> --}}
                            <a href="#" class="text-success fw-bold text-decoration-none" id="login">Login</a>
                            <a href="#" class="text-primary fw-bold text-decoration-none" id='register'>Register</a>
                            
                            @endif
                          
                    </div>
                    @if(!isset($user))
                    <div class="card-body p-0">
                        <hr>
                        <div class="form">
                            <div class="mb-3">
                                <label for="">Email</label>
                                <input type="email" class="form-control" id="email" placeholder="Please Enter your Email" required>
                            </div>
                            <div class="mb-3">
                                <label for="">Name</label>
                                <input type="text" class="form-control" id="name" placeholder="Please Enter your FullName" required>
                            </div>
                            <div class="mb-3">
                                <label for="">Password</label>
                                <input type="password" class="form-control" id="Pass" placeholder="Please Enter your Password" required>
                            </div>
                        </div>
                    </div>
                    @endif
                         
                </div>
            </div>
        </div>
       
    </body>

    <script type="module" defer>
        var token = "{{ csrf_token() }}";
        var url = "{{ url('/register_post') }}";
        var url1 = "{{ url('/login/okta') }}";
        $(document).ready(function(){
            $("#register").click(function(){
                var data = {
                    'email':$("#email").val(), 
                    'name':$("#name").val(),
                    'password':$("#pass").val(),
                    '_token':token,
                }
                $.ajax({
                    url:url,
                    data:data,
                    type:'POST',
                    success:function(res){
                        
                        if(res['msg'] == "Success"){
                            alert('Successfully Registered');
                            window.location.reload();
                        }
                    },
                    error:function(res){

                        console.log(res);
                    }
                })
            });
            $("#login").click(function(){
                var data = {
                    'email':$("#email").val(), 
                    'name':$("#name").val(),
                    'password':$("#Pass").val(),
                    '_token':token,
                };
                console.log(data);
                $.ajax({
                    url:url1,
                    data:data,
                    type:'POST',
                    success:function(res){
                        
                        if(res['msg'] == "Success"){
                            alert('Successfully Registered');
                            window.location.reload();
                        }else{
                            window.location.reload();
                        }

                    },
                    error:function(res){

                        console.log(res);
                    }
                })
            });
        });
        
    </script>
   
</html>
