@extends('layouts.app')

@section('title', 'My Account')

@push('styles')
  @vite('resources/css/pages/app-pages.css')
@endpush

@section('content')
  {{-- Dashboard --}}
  <div class="profile-page">
    <h1 class="profile-title">My Account Dashboard</h1>

    {{--  Display Status Feedback --}}
    @if (session('status'))
      <div class="profile-status">
        {{ session('status') }}
      </div>
    @endif

    <div class="profile-layout">
      <div class="profile-main">
        {{-- Account Customization --}}
        <div class="profile-card">
          @include('partials.update-customization-form')
        </div>

        {{-- Account Details --}}
        <div class="profile-card">
          @include('partials.update-details-form')
        </div>

        {{-- Email Notifications Settings --}}
        <div class="profile-card">
            <h3 style="margin-top: 0; margin-bottom: 10px; font-size: 1.25rem;">Email Notifications</h3>
            <p style="color: #a0aec0; font-size: 0.9rem; margin-bottom: 20px;">Choose whether you want to receive an email when you get a new notification on 9Mint.</p>

            <form method="POST" action="{{ route('profile.email-preferences.update') }}">
                @csrf
                @method('PATCH')
                
                <label style="display: flex; align-items: center; cursor: pointer; margin-bottom: 20px;">
                    <input type="checkbox" 
                           name="receives_email_notifications" 
                           value="1" 
                           style="width: 18px; height: 18px; margin-right: 10px; cursor: pointer;"
                           {{ auth()->user()->receives_email_notifications ? 'checked' : '' }}>
                    <span style="font-weight: 500;">Send me email notifications</span>
                </label>

                <button type="submit" style="background-color: #0dcaf0; color: #000; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                    Save Preferences
                </button>
            </form>
        </div>
      </div>

      <div class="profile-side">
        @include('partials.activity-links')
      </div>
    </div>
  </div>
@endsection