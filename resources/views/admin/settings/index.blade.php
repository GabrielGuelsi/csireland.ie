@extends('adminlte::page')

@section('title', 'API & Extension Settings')

@section('content_header')
    <h1>API & Extension Settings</h1>
@stop

@section('content')

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- New token alert — shown only once --}}
    @if(session('new_token'))
        <div class="alert alert-warning alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h5><i class="icon fas fa-key"></i> Your new API token — copy it now!</h5>
            <p class="mb-2 text-muted small">This token will <strong>not be shown again</strong>. Paste it in the extension popup settings.</p>
            <div class="input-group">
                <input type="text" id="new-token-val" class="form-control" value="{{ session('new_token') }}" readonly style="font-family:monospace;font-size:12px">
                <div class="input-group-append">
                    <button class="btn btn-warning" onclick="copyToken()">Copy</button>
                </div>
            </div>
        </div>
    @endif

    <div class="row">

        {{-- HOW TO CONNECT section --}}
        <div class="col-md-7">
            <div class="card card-primary card-outline">
                <div class="card-header"><h3 class="card-title">How to connect the Chrome Extension</h3></div>
                <div class="card-body">
                    <ol class="pl-3" style="line-height:2">
                        <li>Generate a token below (give it a name like <em>"Extension – My PC"</em>)</li>
                        <li>Copy the token — it only appears once</li>
                        <li>Open WhatsApp Web in Chrome</li>
                        <li>Click the <strong>EduAuto</strong> extension icon (top-right of browser)</li>
                        <li>Paste your <strong>API URL</strong> and <strong>API Token</strong> into the popup settings</li>
                        <li>Click <strong>Save</strong> — the extension will now pull messages from this platform instead of Google Sheets</li>
                    </ol>

                    <hr>

                    <p class="mb-1"><strong>Your API URL:</strong></p>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" value="{{ url('') }}" readonly style="font-family:monospace;font-size:12px">
                        <div class="input-group-append">
                            <button class="btn btn-default" onclick="navigator.clipboard.writeText('{{ url('') }}')">Copy</button>
                        </div>
                    </div>

                    <p class="text-muted small mb-0">
                        The extension will call <code>{{ url('/api/messages/pending') }}</code> every 30 seconds.
                        When you click "Done" in the extension, it calls <code>POST /api/messages/{literal}{id}{/literal}/sent</code>.
                    </p>
                </div>
            </div>
        </div>

        {{-- Generate token --}}
        <div class="col-md-5">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Generate API Token</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.settings.tokens.create') }}">
                        @csrf
                        <div class="form-group">
                            <label>Token name</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   placeholder='e.g. "Extension – My PC"' required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-key"></i> Generate Token
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Existing tokens --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Active Tokens</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr><th>Name</th><th>Created</th><th>Last used</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($tokens as $token)
                        <tr>
                            <td>{{ $token->name }}</td>
                            <td>{{ $token->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $token->last_used_at?->format('d/m/Y H:i') ?? 'Never' }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.settings.tokens.delete', $token) }}"
                                      onsubmit="return confirm('Revoke this token? The extension using it will stop working.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-danger">Revoke</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">No tokens yet. Generate one above.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@stop

@section('js')
<script>
function copyToken() {
    const el = document.getElementById('new-token-val');
    el.select();
    navigator.clipboard.writeText(el.value).then(() => {
        const btn = el.nextElementSibling.querySelector('button');
        btn.textContent = '✓ Copied!';
        setTimeout(() => btn.textContent = 'Copy', 2000);
    });
}
</script>
@stop
