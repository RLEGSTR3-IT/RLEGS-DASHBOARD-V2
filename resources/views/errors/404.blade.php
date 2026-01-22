{{-- resources/views/errors/404.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ isset($exception) ? $exception->getStatusCode() : '404' }} - Error</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #dc3545;
            --text-dark: #212529;
            --text-muted: #6c757d;
            --border-color: #e9ecef;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        /* Logo */
        .error-logo {
            margin-bottom: 2rem;
            text-align: center;
        }

        .error-logo img {
            height: 60px;
            width: auto;
        }

        /* Container */
        .error-container {
            max-width: 700px;
            width: 100%;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Header with Icon + Code */
        .error-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .error-icon {
            font-size: 5rem;
            color: var(--primary);
        }

        .error-code {
            font-size: 8rem;
            font-weight: 900;
            color: var(--primary);
            line-height: 1;
            letter-spacing: -0.05em;
        }

        /* Content */
        .error-content {
            text-align: center;
        }

        .error-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .error-description {
            font-size: 1.125rem;
            color: var(--text-muted);
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        /* Buttons */
        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 3rem;
        }

        .btn {
            padding: 0.875rem 2rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-dark);
            border: 2px solid var(--border-color);
            font-weight: 400;
        }

        .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Accordion */
        .accordion {
            text-align: left;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }

        .accordion-header {
            background: #ffffff;
            padding: 1rem 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background 0.2s;
            user-select: none;
        }

        .accordion-header:hover {
            background: #f8f9fa;
        }

        .accordion-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .accordion-title i {
            color: var(--primary);
            font-size: 0.875rem;
        }

        .accordion-icon {
            color: var(--text-muted);
            font-size: 0.875rem;
            transition: transform 0.3s;
        }

        .accordion.active .accordion-icon {
            transform: rotate(180deg);
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: #f8f9fa;
        }

        .accordion.active .accordion-content {
            max-height: 500px;
        }

        .accordion-body {
            padding: 1.5rem;
        }

        .accordion-body ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .accordion-body li {
            padding: 0.5rem 0;
            color: var(--text-muted);
            font-size: 0.9rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            line-height: 1.5;
        }

        .accordion-body li::before {
            content: "→";
            color: var(--primary);
            font-weight: 600;
            flex-shrink: 0;
        }

        /* Footer */
        .error-footer {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
            text-align: center;
        }

        .error-footer p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .error-logo img {
                height: 50px;
            }

            .error-header {
                gap: 1rem;
            }

            .error-icon {
                font-size: 3.5rem;
            }

            .error-code {
                font-size: 5rem;
            }

            .error-title {
                font-size: 1.5rem;
            }

            .error-description {
                font-size: 1rem;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 1rem;
            }

            .error-logo img {
                height: 40px;
            }

            .error-header {
                flex-direction: column;
                gap: 0.5rem;
            }

            .error-icon {
                font-size: 3rem;
            }

            .error-code {
                font-size: 4rem;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .error-actions {
                flex-direction: column;
            }

            .accordion-header {
                padding: 0.875rem 1rem;
            }

            .accordion-body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    @php
        $errorCode = 404;
        if (isset($exception)) {
            try {
                $errorCode = $exception->getStatusCode();
            } catch (\Exception $e) {
                $errorCode = 500;
            }
        }
        
        $errors = [
            400 => [
                'icon' => 'circle-exclamation',
                'title' => 'Bad Request',
                'message' => 'Permintaan tidak valid. Periksa kembali data yang Anda kirim.',
                'suggestions' => [
                    'Periksa format data yang Anda masukkan',
                    'Pastikan semua field terisi dengan benar',
                    'Hubungi administrator jika masalah berlanjut'
                ]
            ],
            401 => [
                'icon' => 'user-lock',
                'title' => 'Unauthorized',
                'message' => 'Anda harus login terlebih dahulu untuk mengakses halaman ini.',
                'suggestions' => [
                    'Login dengan akun yang valid',
                    'Periksa session Anda',
                    'Hubungi administrator jika tidak bisa login'
                ]
            ],
            403 => [
                'icon' => 'lock',
                'title' => 'Akses Ditolak',
                'message' => 'Anda tidak memiliki izin untuk mengakses halaman ini.',
                'suggestions' => [
                    'Login dengan akun yang memiliki akses',
                    'Hubungi administrator untuk meminta akses',
                    'Kembali ke halaman sebelumnya'
                ]
            ],
            404 => [
                'icon' => 'magnifying-glass',
                'title' => 'Halaman Tidak Ditemukan',
                'message' => 'Halaman yang Anda cari tidak ditemukan. Mungkin URL salah atau halaman telah dipindahkan.',
                'suggestions' => [
                    'Periksa kembali URL yang Anda masukkan',
                    'Kembali ke halaman sebelumnya',
                    'Gunakan menu navigasi untuk mencari halaman',
                    'Hubungi administrator jika masalah berlanjut'
                ]
            ],
            419 => [
                'icon' => 'clock-rotate-left',
                'title' => 'Session Expired',
                'message' => 'Session Anda telah berakhir. Silakan muat ulang halaman.',
                'suggestions' => [
                    'Muat ulang halaman ini',
                    'Login kembali jika diperlukan',
                    'Periksa koneksi internet Anda'
                ]
            ],
            429 => [
                'icon' => 'gauge-high',
                'title' => 'Terlalu Banyak Permintaan',
                'message' => 'Anda mengirim terlalu banyak permintaan. Mohon tunggu beberapa saat.',
                'suggestions' => [
                    'Tunggu beberapa menit sebelum mencoba lagi',
                    'Jangan muat ulang halaman terlalu cepat',
                    'Hubungi administrator jika masalah berlanjut'
                ]
            ],
            500 => [
                'icon' => 'server',
                'title' => 'Internal Server Error',
                'message' => 'Terjadi kesalahan pada server. Tim kami sedang menangani masalah ini.',
                'suggestions' => [
                    'Coba muat ulang halaman dalam beberapa saat',
                    'Bersihkan cache browser Anda',
                    'Hubungi administrator jika masalah berlanjut'
                ]
            ],
            502 => [
                'icon' => 'triangle-exclamation',
                'title' => 'Bad Gateway',
                'message' => 'Server tidak dapat terhubung dengan benar.',
                'suggestions' => [
                    'Tunggu beberapa saat dan coba lagi',
                    'Periksa koneksi internet Anda',
                    'Hubungi administrator'
                ]
            ],
            503 => [
                'icon' => 'screwdriver-wrench',
                'title' => 'Sedang Maintenance',
                'message' => 'Aplikasi sedang dalam perbaikan. Mohon coba lagi nanti.',
                'suggestions' => [
                    'Coba lagi dalam beberapa menit',
                    'Ikuti media sosial kami untuk info terbaru',
                    'Hubungi support jika urgent'
                ]
            ],
            504 => [
                'icon' => 'hourglass-end',
                'title' => 'Gateway Timeout',
                'message' => 'Server membutuhkan waktu terlalu lama untuk merespon.',
                'suggestions' => [
                    'Coba muat ulang halaman',
                    'Periksa koneksi internet Anda',
                    'Hubungi administrator jika masalah berlanjut'
                ]
            ]
        ];

        $currentError = $errors[$errorCode] ?? $errors[404];
    @endphp

    <!-- Logo -->
    <div class="error-logo">
        <img src="https://rlegstr3.biz.id/img/logo-treg3.png" alt="RLEGS TR3 Logo">
    </div>

    <!-- Main Container -->
    <div class="error-container">
        <!-- Icon + Code (Horizontal) -->
        <div class="error-header">
            <i class="fas fa-{{ $currentError['icon'] }} error-icon"></i>
            <div class="error-code">{{ $errorCode }}</div>
        </div>

        <!-- Content -->
        <div class="error-content">
            <h1 class="error-title">{{ $currentError['title'] }}</h1>
            <p class="error-description">{{ $currentError['message'] }}</p>

            <!-- Buttons -->
            <div class="error-actions">
                <a href="{{ url('/') }}" class="btn btn-primary">
                    <i class="fas fa-home"></i>
                    <span>Kembali ke Beranda</span>
                </a>
                <button onclick="history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    <span>Halaman Sebelumnya</span>
                </button>
            </div>

            <!-- Accordion Suggestions -->
            @if(isset($currentError['suggestions']))
            <div class="accordion" id="suggestionsAccordion">
                <div class="accordion-header" onclick="toggleAccordion()">
                    <div class="accordion-title">
                        <i class="fas fa-lightbulb"></i>
                        <span>Saran untuk Anda</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-icon"></i>
                </div>
                <div class="accordion-content">
                    <div class="accordion-body">
                        <ul>
                            @foreach($currentError['suggestions'] as $suggestion)
                            <li>{{ $suggestion }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="error-footer">
            <p>Made with ❤️ by RLEGS TR3 Development Team</p>
        </div>
    </div>

    <script>
        // Accordion toggle
        function toggleAccordion() {
            const accordion = document.getElementById('suggestionsAccordion');
            accordion.classList.toggle('active');
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') history.back();
            if (e.key === 'h' || e.key === 'H') window.location.href = '{{ url('/') }}';
        });
    </script>
</body>
</html>