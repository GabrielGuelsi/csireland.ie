{{-- Chat thread partial: CS ↔ Applications per-student messages --}}
<div class="card">
    <div class="card-header"><strong>CS ↔ Applications Chat</strong></div>
    <div class="card-body" style="max-height: 50vh; overflow-y: auto;">
        @forelse($student->chats as $msg)
            <div class="mb-3">
                <div>
                    <strong>{{ optional($msg->author)->name ?? 'System' }}</strong>
                    <span class="badge badge-{{ $msg->author_role === 'application' ? 'info' : ($msg->author_role === 'admin' ? 'dark' : 'secondary') }}">
                        {{ $msg->author_role }}
                    </span>
                    <small class="text-muted float-right">{{ $msg->created_at->format('d/m H:i') }}</small>
                </div>
                <div>{{ $msg->body }}</div>
            </div>
        @empty
            <p class="text-muted">No messages yet.</p>
        @endforelse
    </div>
    <div class="card-footer">
        <form method="POST" action="{{ route('admin.students.chat.store', $student) }}">
            @csrf
            <div class="form-group mb-2">
                <textarea name="body" rows="2" class="form-control" placeholder="Message the CS / Applications team..." required></textarea>
            </div>
            <button class="btn btn-sm btn-primary">Send</button>
        </form>
    </div>
</div>
