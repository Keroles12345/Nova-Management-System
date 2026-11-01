<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mode === 'login' ? 'Sign in' : 'Create account' }} · NOVA</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;background:#f5f7fb;color:#20283d;font-family:Tahoma,Arial,sans-serif;display:grid;place-items:center;padding:22px}
        .auth-wrap{width:min(980px,100%);min-height:590px;display:grid;grid-template-columns:1fr 1fr;border-radius:24px;background:#fff;overflow:hidden;box-shadow:0 24px 70px #28324c1c}
        .auth-art{background:linear-gradient(145deg,#242a43,#181d31);color:white;padding:52px 44px;display:flex;flex-direction:column;justify-content:space-between;position:relative;overflow:hidden}
        .auth-art:before,.auth-art:after{content:"";position:absolute;border:1px solid #ffffff18;border-radius:50%;width:320px;height:320px;left:-125px;top:80px}
        .auth-art:after{width:220px;height:220px;left:-75px;top:130px}
        .brand{display:flex;align-items:center;gap:12px;font-size:19px;font-weight:800;position:relative;z-index:1}.brand-mark{width:38px;height:38px;border-radius:13px;background:linear-gradient(145deg,#8c7bff,#5549d6);display:grid;place-items:center}
        .art-copy{position:relative;z-index:1;max-width:360px}.art-copy h1{font-size:32px;line-height:1.5;margin:0 0 12px}.art-copy p{color:#b6bdd2;font-size:13px;line-height:2;margin:0}
        .art-foot{font-size:10px;color:#929ab2;position:relative;z-index:1}.auth-form{padding:55px clamp(28px,6vw,65px);display:flex;justify-content:center;flex-direction:column}
        .auth-form h2{font-size:24px;margin:0 0 9px}.subtitle{font-size:12px;color:#9098aa;margin:0 0 28px}.field{display:grid;gap:8px;margin-bottom:16px}.field label{font-size:11px;font-weight:700;color:#465067}.field input{border:1px solid #e6e9f0;background:#fbfcfe;border-radius:9px;padding:13px 12px;outline:none;font-size:12px}
        .field input:focus{border-color:#766ae8;box-shadow:0 0 0 3px #766ae81c}.submit{border:0;border-radius:9px;background:#6558df;color:white;padding:13px;font-size:12px;font-weight:700;cursor:pointer;margin-top:5px}.submit:hover{background:#5144c9}
        .switch{font-size:11px;color:#858da0;text-align:center;margin-top:22px}.switch a{color:#5d51d4;font-weight:700}.errors{background:#fff0f1;color:#c34755;padding:11px;border-radius:8px;font-size:11px;margin-bottom:15px}
        @media(max-width:720px){.auth-wrap{grid-template-columns:1fr;min-height:auto}.auth-art{padding:25px;min-height:210px}.art-copy h1{font-size:22px}.art-copy p{font-size:11px}.art-foot{display:none}.auth-form{padding:32px 25px}.auth-art:before{top:-190px;left:-90px}}
    </style>
</head>
<body>
<div class="auth-wrap">
    <section class="auth-art">
        <a class="brand" href="{{ route('login') }}"><span class="brand-mark">N</span><span>NOVA</span></a>
        <div class="art-copy">
            <h1>Your entire business,<br>in one place.</h1>
            <p>Track store performance, organize team tasks, and make better decisions from a single dashboard.</p>
        </div>
        <div class="art-foot">NOVA COMMERCE SUITE &nbsp; · &nbsp; A secure workspace</div>
    </section>
    <section class="auth-form">
        <h2>{{ $mode === 'login' ? 'Welcome back' : 'Get started' }}</h2>
        <p class="subtitle">{{ $mode === 'login' ? 'Sign in to continue to your workspace.' : 'Create an account and organize your work.' }}</p>
        @if ($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route($mode === 'login' ? 'login.store' : 'register.store') }}">
            @csrf
            @if ($mode === 'register')
                <div class="field"><label for="name">Full name</label><input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required></div>
            @endif
            <div class="field"><label for="email">Email address</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></div>
            <div class="field"><label for="password">Password</label><input id="password" type="password" name="password" autocomplete="{{ $mode === 'login' ? 'current-password' : 'new-password' }}" required></div>
            @if ($mode === 'register')
                <div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required></div>
            @endif
            @if ($mode === 'login')
                <label style="display:flex;align-items:center;gap:8px;color:#778095;font-size:10px;margin-bottom:14px"><input type="checkbox" name="remember" value="1"> Remember me</label>
            @endif
            <button class="submit" type="submit">{{ $mode === 'login' ? 'Sign in' : 'Create account' }}</button>
        </form>
        <div class="switch">
            @if ($mode === 'login')
                Don't have an account? <a href="{{ route('register') }}">Create an account</a>
            @else
                Already have an account? <a href="{{ route('login') }}">Sign in</a>
            @endif
        </div>
    </section>
</div>
</body>
</html>
