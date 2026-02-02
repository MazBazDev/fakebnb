<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Autoriser l'application</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; margin: 0; }
        .card { max-width: 520px; margin: 10vh auto; background: #fff; padding: 24px; border-radius: 16px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); }
        h1 { margin: 0 0 8px; font-size: 24px; }
        p { margin: 0 0 16px; color: #64748b; }
        ul { margin: 0 0 16px; padding-left: 18px; color: #475569; }
        .actions { display: flex; gap: 12px; }
        button { flex: 1; padding: 10px 12px; border: 0; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .approve { background: #0f172a; color: #fff; }
        .deny { background: #e2e8f0; color: #0f172a; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Autoriser {{ $client->name }} ?</h1>
        <p>Cette application demande l'accès à votre compte.</p>

        @if (count($scopes) > 0)
            <p>Elle pourra :</p>
            <ul>
                @foreach ($scopes as $scope)
                    <li>{{ $scope->description }}</li>
                @endforeach
            </ul>
        @endif

        <div class="actions">
            <form method="POST" action="{{ route('passport.authorizations.approve') }}">
                @csrf
                <input type="hidden" name="auth_token" value="{{ $authToken }}" />
                <button type="submit" class="approve">Autoriser</button>
            </form>

            <form method="POST" action="{{ route('passport.authorizations.deny') }}">
                @csrf
                <input type="hidden" name="auth_token" value="{{ $authToken }}" />
                <button type="submit" class="deny">Refuser</button>
            </form>
        </div>
    </div>
</body>
</html>
