@extends('layouts.main')

@section('title', 'Edit Profile')

@section('content')
<div id="main-content" class="content">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        
        <!-- Header Section -->
        <header class="mb-6">
            <h2 class="text-2xl font-semibold text-gray-900">Edit Profile</h2>
        </header>
        
        <div class="profile-container">
            <div class="profile-left">
                <!-- Container Update Profile -->
                <div class="profile-card">
                    <h3 class="profile-card-title">Informasi Profil</h3>
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>
        
            <div class="profile-right">
                <!-- Container Update Password -->
                <div class="profile-card">
                    <h3 class="profile-card-title">Ubah Kata Sandi</h3>
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Profile Page Layout */
    .profile-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }
    
    @media (max-width: 768px) {
        .profile-container {
            grid-template-columns: 1fr;
        }
    }
    
    .profile-card {
        background-color: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        padding: 1.5rem;
    }
    
    .profile-card-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #eee;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endsection