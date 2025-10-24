{{-- resources/views/profile/index.blade.php --}}
@extends('layouts.main')

@section('title', 'Profil')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endsection

@section('content')
<div class="main-content">
  <div class="header-dashboard">
    <div class="header-content">
      <div class="header-text">
        <h1 class="header-title">Profil Pengguna</h1>
        <p class="header-subtitle">Kelola informasi akun, foto profil, dan reset kata sandi</p>
      </div>
    </div>
  </div>

  <div class="row g-4">
    {{-- Kolom kiri: Foto + Info Akun --}}
    <div class="col-lg-7">
      <div class="dashboard-card">
        <div class="card-header">
          <div class="card-header-content">
            <h5 class="card-title">Informasi Akun</h5>
            <p class="text-muted mb-0">Nama, email, dan foto profil</p>
          </div>
        </div>

        <div class="card-body">
          <form class="profile-form" action="#" method="post" enctype="multipart/form-data">
            <div class="profile-grid">
              {{-- Foto profil --}}
              <section class="profile-image-section">
                <div class="profile-image-container">
                  <div class="profile-image-wrapper">
                    {{-- Placeholder awal (tanpa backend) --}}
                    <div class="profile-image-placeholder"><span class="initial">A</span></div>

                    {{-- Tombol edit foto --}}
                    <label for="profile_image" class="edit-icon-wrapper" title="Ganti foto">
                      <svg xmlns="http://www.w3.org/2000/svg" class="edit-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                      </svg>
                    </label>
                  </div>

                  <input id="profile_image" class="d-none" type="file" accept="image/*" />
                  <p class="error-text mt-2" id="imgErr" style="display:none;"></p>

                  <button type="button" id="btnRemovePhoto" class="btn-outline mt-2 small">
                    Hapus Foto
                  </button>
                </div>
              </section>

              {{-- Form nama & email --}}
              <section class="profile-info-section">
                <div class="form-group">
                  <label for="name" class="form-label">Nama</label>
                  <input id="name" type="text" class="form-input" value="Aulia Rahman" autocomplete="name">
                  <p class="error-text" id="nameErr" style="display:none;"></p>
                </div>

                <div class="form-group">
                  <label for="email" class="form-label">Email</label>
                  <input id="email" type="email" class="form-input" value="aulia@example.com" autocomplete="email">
                  <p class="error-text" id="emailErr" style="display:none;"></p>

                  <div class="verification-notice mt-2">
                    <p class="text-verify">
                      Alamat email Anda belum diverifikasi.
                      <button type="button" class="verify-link" id="resendBtn">
                        Klik di sini untuk mengirim ulang email verifikasi.
                      </button>
                    </p>
                    <p class="success-text" id="verifySuccess" style="display:none;">
                      Link verifikasi baru telah dikirim ke alamat email Anda.
                    </p>
                  </div>
                </div>

                <div class="form-actions">
                  <button type="submit" class="save-button">Simpan Perubahan</button>
                  <span class="success-text" id="saveMsg" style="display:none;">Tersimpan.</span>
                </div>
              </section>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- Kolom kanan: Reset Password --}}
    <div class="col-lg-5">
      <div class="dashboard-card">
        <div class="card-header">
          <div class="card-header-content">
            <h5 class="card-title">Reset Password</h5>
            <p class="text-muted mb-0">Ganti kata sandi akun Anda</p>
          </div>
        </div>

        <div class="card-body">
          <form id="passwordForm" action="#" method="post" class="password-form">
            <div class="form-group">
              <label for="current_password" class="form-label">Password Saat Ini</label>
              <div class="password-field">
                <input id="current_password" type="password" class="form-input" autocomplete="current-password">
                <button class="toggle-eye" type="button" data-target="current_password" aria-label="Tampilkan/Sembunyikan">
                  👁
                </button>
              </div>
              <p class="error-text" id="curErr" style="display:none;"></p>
            </div>

            <div class="form-group">
              <label for="password" class="form-label">Password Baru</label>
              <div class="password-field">
                <input id="password" type="password" class="form-input" autocomplete="new-password">
                <button class="toggle-eye" type="button" data-target="password" aria-label="Tampilkan/Sembunyikan">
                  👁
                </button>
              </div>
              <p class="error-text" id="newErr" style="display:none;"></p>
            </div>

            <div class="form-group">
              <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
              <div class="password-field">
                <input id="password_confirmation" type="password" class="form-input" autocomplete="new-password">
                <button class="toggle-eye" type="button" data-target="password_confirmation" aria-label="Tampilkan/Sembunyikan">
                  👁
                </button>
              </div>
              <p class="error-text" id="confErr" style="display:none;"></p>
            </div>

            <div class="form-actions">
              <button type="submit" class="save-button">Perbarui Password</button>
              <span class="success-text" id="pwdMsg" style="display:none;">Password diperbarui.</span>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  // --- Preview / hapus foto profil (frontend only)
  const inputPhoto = document.getElementById('profile_image');
  const imgErr = document.getElementById('imgErr');
  const removeBtn = document.getElementById('btnRemovePhoto');

  inputPhoto.addEventListener('change', () => {
    imgErr.style.display = 'none';
    const file = inputPhoto.files?.[0];
    if (!file) return;

    const maxBytes = 5 * 1024 * 1024; // 5MB
    if (file.size > maxBytes) {
      imgErr.textContent = 'Ukuran file terlalu besar. Maksimal 5MB.';
      imgErr.style.display = 'block';
      inputPhoto.value = '';
      return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
      const wrapper = document.querySelector('.profile-image-wrapper');
      const placeholder = wrapper.querySelector('.profile-image-placeholder');
      const currentImg = wrapper.querySelector('img.profile-image');

      if (placeholder) placeholder.style.display = 'none';
      if (currentImg) currentImg.remove();

      const img = document.createElement('img');
      img.className = 'profile-image';
      img.alt = 'Profile Image';
      img.src = e.target.result;
      wrapper.appendChild(img);
    };
    reader.readAsDataURL(file);
  });

  removeBtn.addEventListener('click', () => {
    inputPhoto.value = '';
    const wrapper = document.querySelector('.profile-image-wrapper');
    const currentImg = wrapper.querySelector('img.profile-image');
    const placeholder = wrapper.querySelector('.profile-image-placeholder');
    if (currentImg) currentImg.remove();
    if (placeholder) placeholder.style.display = 'flex';
  });

  // --- Simulasi simpan profil (validasi ringan)
  const profileForm = document.querySelector('.profile-form');
  const saveMsg = document.getElementById('saveMsg');
  profileForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const name = document.getElementById('name');
    const email = document.getElementById('email');
    const nameErr = document.getElementById('nameErr');
    const emailErr = document.getElementById('emailErr');
    nameErr.style.display = emailErr.style.display = 'none';

    let ok = true;
    if (!name.value.trim()) { nameErr.textContent = 'Nama wajib diisi.'; nameErr.style.display = 'block'; ok = false; }
    if (!email.value.trim()) { emailErr.textContent = 'Email wajib diisi.'; emailErr.style.display = 'block'; ok = false; }

    if (!ok) return;
    saveMsg.style.display = 'inline';
    setTimeout(() => saveMsg.style.display = 'none', 1800);
  });

  // --- Resend verifikasi (simulasi)
  const resendBtn = document.getElementById('resendBtn');
  const verifySuccess = document.getElementById('verifySuccess');
  resendBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    verifySuccess.style.display = 'block';
    setTimeout(() => verifySuccess.style.display = 'none', 2000);
  });

  // --- Reset password (frontend only)
  const pwdForm = document.getElementById('passwordForm');
  const pwdMsg = document.getElementById('pwdMsg');
  pwdForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const cur = document.getElementById('current_password');
    const p1  = document.getElementById('password');
    const p2  = document.getElementById('password_confirmation');
    const curErr = document.getElementById('curErr');
    const newErr = document.getElementById('newErr');
    const confErr = document.getElementById('confErr');
    curErr.style.display = newErr.style.display = confErr.style.display = 'none';

    let ok = true;
    if (!cur.value.trim()) { curErr.textContent = 'Password saat ini wajib diisi.'; curErr.style.display = 'block'; ok = false; }
    if (p1.value.length < 6) { newErr.textContent = 'Minimal 6 karakter.'; newErr.style.display = 'block'; ok = false; }
    if (p1.value !== p2.value) { confErr.textContent = 'Konfirmasi tidak cocok.'; confErr.style.display = 'block'; ok = false; }
    if (!ok) return;

    pwdMsg.style.display = 'inline';
    setTimeout(() => pwdMsg.style.display = 'none', 1800);
    cur.value = p1.value = p2.value = '';
  });

  // --- Toggle eye
  document.querySelectorAll('.toggle-eye').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-target');
      const input = document.getElementById(id);
      input.type = input.type === 'password' ? 'text' : 'password';
    });
  });
</script>
@endsection
