<section>
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="profile-form" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <!-- Container untuk Flexbox -->
        <div class="profile-grid">
            <!-- Container Update Profile Picture -->
            <div class="profile-image-section">
                <div class="profile-image-container">
                    <div class="profile-image-wrapper">
                        @if($user->profile_image)
                            <img src="{{ asset('storage/'.$user->profile_image) }}" 
                                alt="Profile Image" 
                                class="profile-image">
                        @else
                            <div class="profile-image-placeholder">
                                <span class="initial">{{ substr($user->name, 0, 1) }}</span>
                            </div>
                        @endif
                        
                        <!-- Edit Icon Overlay -->
                        <label for="profile_image" class="edit-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" class="edit-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                            </svg>
                        </label>
                    </div>
                </div>
                <div class="image-upload-info">
                    <input id="profile_image" class="hidden" type="file" name="profile_image" accept="image/*" />
                </div>
                @error('profile_image')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <!-- Container untuk Form Input -->
            <div class="profile-info-section">
                <!-- Update Name -->
                <div class="form-group">
                    <label for="name" class="form-label">{{ __('Nama') }}</label>
                    <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
                    @error('name')
                        <p class="error-text">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Update Email -->
                <div class="form-group">
                    <label for="email" class="form-label">{{ __('Email') }}</label>
                    <input id="email" name="email" type="email" class="form-input" value="{{ old('email', $user->email) }}" required autocomplete="username" />
                    @error('email')
                        <p class="error-text">{{ $message }}</p>
                    @enderror

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <div class="verification-notice">
                            <p class="text-verify">
                                {{ __('Alamat email Anda belum diverifikasi.') }}

                                <button form="send-verification" class="verify-link">
                                    {{ __('Klik di sini untuk mengirim ulang email verifikasi.') }}
                                </button>
                            </p>

                            @if (session('status') === 'verification-link-sent')
                                <p class="success-text">
                                    {{ __('Link verifikasi baru telah dikirim ke alamat email Anda.') }}
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="form-actions">
            <button type="submit" class="save-button">{{ __('Simpan') }}</button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="success-text"
                >{{ __('Tersimpan.') }}</p>
            @endif
        </div>
    </form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const profileImageInput = document.getElementById('profile_image');
        
        profileImageInput.addEventListener('change', function() {
            const file = this.files[0];
            
            if (file) {
                // Untuk menampilkan nama file jika diperlukan
                if (document.getElementById('file-name-am')) {
                    document.getElementById('file-name-am').textContent = file.name;
                }
                
                const reader = new FileReader();
                const profileImage = document.querySelector('.profile-image');
                const profilePlaceholder = document.querySelector('.profile-image-placeholder');
                
                reader.onload = function(e) {
                    // Jika ada gambar profil, update source-nya
                    if (profileImage) {
                        profileImage.src = e.target.result;
                    } 
                    // Jika tidak ada gambar (menggunakan placeholder), buat elemen gambar baru
                    else if (profilePlaceholder) {
                        // Sembunyikan placeholder
                        profilePlaceholder.style.display = 'none';
                        
                        // Buat elemen gambar baru
                        const newImage = document.createElement('img');
                        newImage.src = e.target.result;
                        newImage.className = 'profile-image';
                        newImage.alt = 'Profile Image';
                        
                        // Tambahkan gambar ke wrapper
                        const wrapper = document.querySelector('.profile-image-wrapper');
                        wrapper.appendChild(newImage);
                    }
                };
                
                reader.readAsDataURL(file);
            }
        });
    });
</script>

    <style>
        /* Profile Form Styles */
        .profile-form {
            width: 100%;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        .profile-image-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.25rem;
            background-color: #f9fafb;
            border-radius: 0.5rem;
        }

        .profile-image-container {
            margin: 1rem 0;
            position: relative;
        }

        .profile-image-wrapper {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
        }

        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #133057;
            color: white;
        }

        .initial {
            font-size: 4rem;
            font-weight: bold;
        }

        /* Edit Icon Styling */
        .edit-icon-wrapper {
            position: absolute;
            z-index: 100;
            bottom: 2;
            right: 2;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s ease;
            border: 2px solid rgba(255, 255, 255, 0.758);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .edit-icon-wrapper:hover {
            background-color: #0e223e;
        }

        .edit-icon {
            width: 18px;
            height: 18px;
            color: white;
        }

        .image-upload-info {
            margin-top: 0.5rem;
            text-align: center;
        }

        .file-name {
            font-size: 0.8rem;
            color: #6b7280;
            max-width: 100%;
            text-overflow: ellipsis;
            overflow: hidden;
        }

        /* Form common elements */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.625rem 0.75rem;
            font-size: 0.95rem;
            line-height: 1.5;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-input:focus {
            border-color: #133057;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(19, 48, 87, 0.15);
        }

        .form-actions {
            margin-top: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .save-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1.25rem;
            font-size: 0.95rem;
            font-weight: 500;
            color: #ffffff;
            background-color: #133057;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background-color 0.15s ease-in-out;
        }

        .save-button:hover {
            background-color: #0e223e;
        }

        .save-button:focus {
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(19, 48, 87, 0.25);
        }

        /* Status messages */
        .error-text {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 0.375rem;
        }

        .success-text {
            color: #10b981;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .verification-notice {
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }

        .text-verify {
            color: #4b5563;
        }

        .verify-link {
            background: none;
            border: none;
            font-size: inherit;
            color: #133057;
            text-decoration: underline;
            cursor: pointer;
            padding: 0;
        }

        .hidden {
            display: none;
        }
    </style>
</section>