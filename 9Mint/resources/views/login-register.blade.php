@extends('layouts.app')

@section('title', 'Login / Register')

@push('styles')
  @vite('resources/css/pages/app-pages.css')
  <style>
    /* Styling for the Google Button to match your theme */
    .google-btn-container {
      margin-top: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    
    .or-separator {
      width: 100%;
      text-align: center;
      border-bottom: 1px solid var(--border-soft);
      line-height: 0.1em;
      margin: 10px 0 20px;
      color: var(--text-secondary);
      font-size: 0.8rem;
    }
    
    .or-separator span {
      background: var(--surface-panel);
      padding: 0 10px;
    }

    .google-auth-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      padding: 10px;
      border: 1px solid var(--border-soft);
      border-radius: 5px;
      background: color-mix(in srgb, var(--surface-panel) 88%, var(--surface-input) 12%);
      color: var(--text-main);
      text-decoration: none;
      font-weight: 500;
      box-shadow: var(--shadow-elevated);
      transition: background-color 0.2s, border-color 0.2s, color 0.2s;
    }

    .google-auth-btn:hover {
      background: color-mix(in srgb, var(--surface-input) 82%, var(--surface-panel) 18%);
      border-color: color-mix(in srgb, var(--link-hover) 40%, var(--border-soft) 60%);
      color: var(--link-hover);
    }

    .google-auth-btn img {
      width: 18px;
      height: 18px;
      margin-right: 10px;
    }
  </style>
@endpush

@section('content')
@php
  $showRegister = $errors->register->any();
@endphp
<div class="auth-page-container">
  <div class="auth-section {{ $showRegister ? 'show-register' : '' }}" id="auth-section">

    {{-- Login --}}
    <div class="auth-form auth-form--login">
      <h2>Login</h2>

      @if ($errors->login->any())
        <div class="error-list">
          <ul>
            @foreach ($errors->login->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ url('/login') }}">
        @csrf
        <input type="text" name="name" placeholder="Username" value="{{ old('name') }}" required autocomplete="username">
        <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
        <label class="remember">
          <input type="checkbox" name="remember" value="1"> Remember me
        </label>
        <button type="submit">Login</button>
      </form>

      {{-- Google Login Option --}}
      <div class="google-btn-container">
        <div class="or-separator"><span>OR</span></div>
        <a href="{{ route('google.login') }}" class="google-auth-btn">
          <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google">
          Sign in with Google
        </a>
      </div>

      <p class="auth-signup-prompt" id="auth-signup-prompt" style="margin-top: 25px;">
        Don't have an account yet?
        <a href="#" id="show-register-link">Sign up now.</a>
      </p>

      @if (session('show_forgot_password') && Route::has('password.request'))
        <a class="forgot-password" href="{{ route('password.request') }}" style="display: block; text-align: center; margin-top: 15px; color: #555; text-decoration: none; font-size: 0.9rem;">
          Forgot Password?
        </a>
      @endif
    </div>

    {{-- Register --}}
    <div class="auth-form auth-form--register" id="register-form">
      <h2>Register</h2>

      @if ($errors->register->any())
        <div class="error-list">
          <ul>
            @foreach ($errors->register->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ url('/register') }}">
        @csrf
        <input type="text" name="name" placeholder="Username" value="{{ old('name') }}" required maxlength="80" autocomplete="username" pattern="[A-Za-z0-9\-]+">
        <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required autocomplete="email">
        <input type="password" name="password" placeholder="Password" required minlength="8" autocomplete="new-password">
        <input type="password" name="password_confirmation" placeholder="Confirm Password" required autocomplete="new-password">
        <button type="submit">Register</button>
      </form>

      {{-- Google Register Option --}}
      <div class="google-btn-container">
        <div class="or-separator"><span>OR</span></div>
        <a href="{{ route('google.login') }}" class="google-auth-btn">
          <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google">
          Sign up with Google
        </a>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const authSection = document.getElementById('auth-section');
    const showRegisterLink = document.getElementById('show-register-link');
    const forms = Array.from(document.querySelectorAll('.auth-form form'));

    const updateAuthButtons = function () {
      forms.forEach(function (form) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (!submitBtn) return;

        const requiredFields = Array.from(form.querySelectorAll('input[required]'));
        const allFilled = requiredFields.every(function (field) {
          return field.value.trim().length > 0;
        });
        const formValid = form.checkValidity();

        submitBtn.classList.toggle('is-ready', allFilled && formValid);
      });
    };

    if (authSection && showRegisterLink) {
      showRegisterLink.addEventListener('click', function (event) {
        event.preventDefault();
        authSection.classList.add('show-register');
      });
    }

    forms.forEach(function (form) {
      form.addEventListener('input', updateAuthButtons);
      form.addEventListener('change', updateAuthButtons);
    });

    updateAuthButtons();
  });
</script>
@endpush