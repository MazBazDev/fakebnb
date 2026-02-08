<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Inscription</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Plus Jakarta Sans", system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at 20% 10%, #ffe7ec 0%, transparent 45%),
                        radial-gradient(circle at 80% 0%, #fff4d7 0%, transparent 55%),
                        #f7f4f2;
            color: #1f2937;
            min-height: 100vh;
        }
        .page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 20px;
        }
        .card {
            width: 100%;
            max-width: 480px;
            background: #ffffff;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 18px;
            color: #ff385c;
            margin-bottom: 12px;
        }
        .brand-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: #ff385c;
            box-shadow: 0 0 0 6px rgba(255, 56, 92, 0.15);
        }
        h1 { margin: 0 0 6px; font-size: 28px; }
        p { margin: 0 0 20px; color: #6b7280; font-size: 14px; }
        label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        input {
            display: block;
            width: 100%;
            max-width: 100%;
            padding: 12px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 14px;
            background: #f9fafb;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        input:focus {
            outline: none;
            border-color: #ff385c;
            box-shadow: 0 0 0 4px rgba(255, 56, 92, 0.15);
            background: #ffffff;
        }
        button {
            width: 100%;
            padding: 12px 14px;
            background: linear-gradient(90deg, #ff385c, #ff6b6b);
            color: #fff;
            border: 0;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(255, 56, 92, 0.2);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        button:hover { transform: translateY(-1px); box-shadow: 0 12px 24px rgba(255, 56, 92, 0.28); }
        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px 12px;
            border-radius: 12px;
            font-size: 12px;
            margin-bottom: 16px;
        }
        .link {
            margin-top: 16px;
            font-size: 12px;
            text-align: center;
            color: #6b7280;
        }
        .link a { color: #1f2937; font-weight: 600; text-decoration: none; }
        .link a:hover { color: #ff385c; }
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <div class="brand">
                <span class="brand-dot"></span>
                Fakebnb
            </div>
            <h1>Créer un compte</h1>
            <p>Rejoignez Fakebnb pour publier ou réserver votre prochain séjour.</p>

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
    </div>
</body>
</html>
