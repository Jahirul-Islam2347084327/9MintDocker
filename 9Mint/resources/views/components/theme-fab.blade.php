<div class="theme-fab-stack">
    <button type="button" class="theme-fab" id="theme-toggle" aria-label="Toggle theme">
        <span id="theme-icon" aria-hidden="true">🌙</span>
    </button>

    @auth
        @if (auth()->user()->hasAdminRole())
            <form method="POST" action="{{ route('admin.view-mode.update') }}" class="theme-fab-stack__form">
                @csrf
                <input
                    type="hidden"
                    name="mode"
                    value="{{ auth()->user()->isInAdminView() ? \App\Models\User::ADMIN_VIEW_MODE_CUSTOMER : \App\Models\User::ADMIN_VIEW_MODE_ADMIN }}"
                >
                <button
                    type="submit"
                    class="theme-fab theme-fab--admin-mode"
                    aria-label="{{ auth()->user()->isInAdminView() ? 'Switch to customer view' : 'Switch to admin view' }}"
                    title="{{ auth()->user()->isInAdminView() ? 'Switch to customer view' : 'Switch to admin view' }}"
                >
                    <span class="theme-fab__mode-label">
                        {{ auth()->user()->isInAdminView() ? 'Customer' : 'Admin' }}
                    </span>
                </button>
            </form>
        @endif
    @endauth
</div>
