<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Connexion</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; margin: 0; }
        .card { max-width: 420px; margin: 10vh auto; background: #fff; padding: 24px; border-radius: 16px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); }
        h1 { margin: 0 0 8px; font-size: 24px; }
        p { margin: 0 0 16px; color: #64748b; }
        label { display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px; }
        input { display: block; width: 100%; max-width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 14px; font-size: 14px; }
        button { width: 100%; padding: 10px 12px; background: #0f172a; color: #fff; border: 0; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .error { background: #fee2e2; color: #b91c1c; padding: 8px 10px; border-radius: 10px; font-size: 12px; margin-bottom: 12px; }
        .link { margin-top: 12px; font-size: 12px; text-align: center; }
        .link a { color: #334155; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Connexion</h1>
        <p>Connectez-vous pour continuer.</p>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus />

            <label for="password">Mot de passe</label>
            <input id="password" name="password" type="password" required />

            <button type="submit">Se connecter</button>
        </form>

        <div class="link">
            Pas de compte ? <a href="{{ route('register') }}">Créer un compte</a>
        </div>
    </div>
</body>
</html>
