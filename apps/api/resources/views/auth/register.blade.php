<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Inscription</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; margin: 0; }
        .card { max-width: 420px; margin: 10vh auto; background: #fff; padding: 24px; border-radius: 16px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); }
        h1 { margin: 0 0 8px; font-size: 24px; }
        p { margin: 0 0 16px; color: #64748b; }
        label { display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px; }
        input { width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 14px; font-size: 14px; }
        button { width: 100%; padding: 10px 12px; background: #0f172a; color: #fff; border: 0; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .error { background: #fee2e2; color: #b91c1c; padding: 8px 10px; border-radius: 10px; font-size: 12px; margin-bottom: 12px; }
        .link { margin-top: 12px; font-size: 12px; text-align: center; }
        .link a { color: #334155; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Créer un compte</h1>
        <p>Rejoignez Fakebnb en quelques secondes.</p>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('register.store') }}">
            @csrf
            <label for="name">Nom</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus />

            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required />

            <label for="password">Mot de passe</label>
            <input id="password" name="password" type="password" required />

            <label for="password_confirmation">Confirmer le mot de passe</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required />

            <button type="submit">Créer le compte</button>
        </form>

        <div class="link">
            Déjà un compte ? <a href="{{ route('login') }}">Se connecter</a>
        </div>
    </div>
</body>
</html>
