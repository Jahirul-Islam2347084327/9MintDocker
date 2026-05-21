@extends('layouts.app')

@section('title', 'Complete Your Profile')

@push('styles')
  @vite('resources/css/pages/app-pages.css')
  <style>
    .setup-container {
        max-width: 450px;
        margin: 60px auto;
        padding: 30px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        text-align: center;
        border: 1px solid #eaeaea;
    }
    .setup-container h2 {
        margin-bottom: 10px;
        color: #111;
        font-size: 1.8rem;
    }
    .setup-container p {
        margin-bottom: 25px;
        color: #666;
        font-size: 0.95rem;
        line-height: 1.5;
    }
    .setup-container input {
        width: 100%;
        padding: 12px 15px;
        margin-bottom: 20px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 1rem;
        color: #111; /* Forces the text to be dark! */
        background-color: #fff; /* Ensures the input box stays white */
    }
    .setup-container input:focus {
        outline: none;
        border-color: #555;
    }
    .setup-container button {
        width: 100%;
        padding: 12px;
        background-color: #000;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .setup-container button:hover {
        background-color: #333;
    }
    .error-box {
        background-color: #fee2e2;
        color: #dc2626;
        padding: 10px 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        text-align: left;
        font-size: 0.85rem;
    }
  </style>
@endpush

@section('content')
<div class="auth-page-container">
    <div class="setup-container">
        <h2>Welcome to 9Mint! 🎉</h2>
        <p>Since this is your first time logging in with Google, please choose a unique username for your profile.</p>

        @if ($errors->any())
            <div class="error-box">
                <ul style="list-style-type: disc; padding-left: 20px; margin: 0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('username.update') }}">
            @csrf
            {{-- Using the exact pattern rules from your register page --}}
            <input type="text" name="username" placeholder="Choose a Username" value="{{ old('username') }}" required maxlength="80" pattern="[A-Za-z0-9\-]+" title="Letters, numbers, and dashes only.">
            
            <button type="submit">Complete Setup</button>
        </form>
    </div>
</div>
@endsection