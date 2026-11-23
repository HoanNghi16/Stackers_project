<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

ob_start();
require __DIR__ . '/../../index.php';
ob_end_clean();
require __DIR__ . '/../../class/xac-thuc-otp/otp.php';

$option = (string)($_GET['option'] ?? 'dang-nhap');
$optionHopLe = in_array($option, ['dang-nhap', 'xac-thuc'], true);
$returnTo = '';
$requestedReturnTo = (string)($_GET['return_to'] ?? '');
if ($requestedReturnTo !== '') {
    $returnParts = parse_url($requestedReturnTo);
    if (is_array($returnParts)
        && isset($returnParts['path'])
        && str_starts_with($returnParts['path'], '/')
        && !str_starts_with($returnParts['path'], '//')
        && !isset($returnParts['scheme'])
        && !isset($returnParts['host'])
        && !isset($returnParts['user'])
        && !isset($returnParts['pass'])) {
        $returnTo = $returnParts['path']
            . (isset($returnParts['query']) ? '?' . $returnParts['query'] : '')
            . (isset($returnParts['fragment']) ? '#' . $returnParts['fragment'] : '');
    }
}
$csrf = $_SESSION['otp_csrf'] ??= bin2hex(random_bytes(32));

$jsonResponse = static function (array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$optionHopLe) {
        $jsonResponse(['ok' => false, 'message' => 'Tùy chọn xác thực không hợp lệ.'], 400);
    }

    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        $jsonResponse(['ok' => false, 'message' => 'Phiên xác thực đã hết hạn. Hãy tải lại trang.'], 403);
    }

    try {
        $otpController = new CtrlXacThucOTP($conn, $option);
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'lookup') {
            $identifier = trim((string)($_POST['identifier'] ?? ''));
            if ($identifier === '') {
                $jsonResponse(['ok' => false, 'message' => 'Vui lòng nhập mã định danh hoặc email.'], 422);
            }

            $user = $otpController->XacThucNguoiDung($identifier);
            if (!$user) {
                unset($_SESSION['otp_pending_user'], $_SESSION['otp_pending_code']);
                $jsonResponse(['ok' => false, 'message' => 'Không tìm thấy người dùng.']);
            }

            $_SESSION['otp_pending_user'] = [
                'manguoidung' => $user['manguoidung'],
                'vaitro' => $user['vaitro'],
                'ten' => $user['ten'],
                'anhdaidien' => $user['anhdaidien'],
                'email' => $user['email'],
            ];
            unset($_SESSION['otp_pending_code']);

            $email = (string)($user['email'] ?? '');
            $maskedEmail = $email;
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                [$local, $domain] = explode('@', $email, 2);
                $maskedEmail = mb_substr($local, 0, 1) . str_repeat('•', max(3, mb_strlen($local) - 1)) . '@' . $domain;
            }

            $jsonResponse([
                'ok' => true,
                'user' => [
                    'ten' => (string)($user['ten'] ?? ''),
                    'anhdaidien' => (string)($user['anhdaidien'] ?? ''),
                    'email' => $maskedEmail,
                    'has_email' => (bool)filter_var($email, FILTER_VALIDATE_EMAIL),
                ],
            ]);
        }

        if ($action === 'send') {
            $user = $_SESSION['otp_pending_user'] ?? null;
            if (!is_array($user)) {
                $jsonResponse(['ok' => false, 'message' => 'Hãy xác thực người dùng trước.'], 409);
            }
            if ((string)($_POST['method'] ?? '') !== 'email') {
                $jsonResponse(['ok' => false, 'message' => 'Phương thức gửi mã không hợp lệ.'], 422);
            }

            $email = (string)($user['email'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $jsonResponse(['ok' => false, 'message' => 'Tài khoản này chưa có email hợp lệ để nhận mã OTP.']);
            }

            $envPath = dirname(__DIR__, 2) . '/.env';
            $env = is_file($envPath) ? parse_ini_file($envPath, false, INI_SCANNER_RAW) : false;
            $env = is_array($env) ? $env : [];
            $smtpHost = trim((string)($env['SMTP_HOST'] ?? ''));
            $smtpUser = trim((string)($env['SMTP_USERNAME'] ?? ''));
            $smtpPassword = trim((string)($env['SMTP_PASSWORD'] ?? ''));
            $fromEmail = trim((string)($env['SMTP_FROM_EMAIL'] ?? $smtpUser));

            if ($smtpHost === '' || $smtpUser === '' || $smtpPassword === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                $jsonResponse(['ok' => false, 'message' => 'Máy chủ chưa được cấu hình SMTP để gửi email OTP.']);
            }

            require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
            $code = (string)random_int(100000, 999999);

            try {
                $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
                $mailer->isSMTP();
                $mailer->Host = $smtpHost;
                $mailer->SMTPAuth = true;
                $mailer->Username = $smtpUser;
                $mailer->Password = $smtpPassword;
                $mailer->Port = (int)($env['SMTP_PORT'] ?? 587);
                $secure = strtolower(trim((string)($env['SMTP_SECURE'] ?? 'tls')));
                $mailer->SMTPSecure = $secure === 'ssl'
                    ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                    : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mailer->CharSet = 'UTF-8';
                $mailer->setFrom($fromEmail, (string)($env['SMTP_FROM_NAME'] ?? 'Stackers'));
                $mailer->addAddress($email, (string)($user['ten'] ?? ''));
                $mailer->isHTML(true);
                $mailer->Subject = 'Mã xác thực OTP';
                $mailer->Body = '<p>Xin chào ' . htmlspecialchars((string)($user['ten'] ?? ''), ENT_QUOTES, 'UTF-8') . ',</p>'
                    . '<p>Mã OTP của bạn là: <strong style="font-size:24px;letter-spacing:6px">' . $code . '</strong></p>'
                    . '<p>Mã có hiệu lực trong 5 phút. Nếu bạn không yêu cầu mã này, hãy bỏ qua email.</p>';
                $mailer->AltBody = "Mã OTP của bạn là {$code}. Mã có hiệu lực trong 5 phút.";
                $mailer->send();
            } catch (Throwable $error) {
                error_log('PHPMailer OTP error: ' . $error->getMessage());
                $jsonResponse(['ok' => false, 'message' => 'Không gửi được email OTP. Vui lòng thử lại sau.'], 502);
            }

            $_SESSION['otp_pending_code'] = [
                'hash' => password_hash($code, PASSWORD_DEFAULT),
                'expires_at' => time() + 300,
                'attempts' => 0,
            ];
            $jsonResponse(['ok' => true, 'message' => 'Đã gửi mã OTP đến ' . $email . '.']);
        }

        if ($action === 'verify') {
            $user = $_SESSION['otp_pending_user'] ?? null;
            $pending = $_SESSION['otp_pending_code'] ?? null;
            if (!is_array($user) || !is_array($pending)) {
                $jsonResponse(['ok' => false, 'message' => 'Hãy yêu cầu gửi mã OTP trước.'], 409);
            }

            if (time() > (int)$pending['expires_at']) {
                unset($_SESSION['otp_pending_code']);
                $jsonResponse(['ok' => false, 'message' => 'Mã OTP đã hết hạn. Hãy yêu cầu mã mới.']);
            }

            $pending['attempts'] = (int)$pending['attempts'] + 1;
            $_SESSION['otp_pending_code'] = $pending;
            if ($pending['attempts'] > 5) {
                unset($_SESSION['otp_pending_code']);
                $jsonResponse(['ok' => false, 'message' => 'Bạn đã nhập sai quá số lần cho phép. Hãy yêu cầu mã mới.']);
            }

            $inputCode = trim((string)($_POST['code'] ?? ''));
            if ($inputCode === '' || !password_verify($inputCode, (string)$pending['hash'])) {
                $jsonResponse([
                    'ok' => false,
                    'message' => 'Mã OTP không chính xác.',
                    'attempts' => $pending['attempts'],
                ]);
            }

            $otpController->HoanTatXacThuc($user);
            unset($_SESSION['otp_pending_user'], $_SESSION['otp_pending_code']);
            $redirectTo = $option === 'dang-nhap' ? '../login/' : $returnTo;
            $jsonResponse(['ok' => true, 'message' => 'Xác thực thành công.', 'redirect_to' => $redirectTo]);
        }

        if ($action === 'restart') {
            unset($_SESSION['otp_pending_user'], $_SESSION['otp_pending_code']);
            $jsonResponse(['ok' => true]);
        }

        $jsonResponse(['ok' => false, 'message' => 'Yêu cầu không hợp lệ.'], 400);
    } catch (Throwable $error) {
        error_log('OTP request error: ' . $error->getMessage());
        $jsonResponse(['ok' => false, 'message' => 'Có lỗi khi xử lý xác thực OTP.'], 500);
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực OTP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center px-4 py-8">
    <main class="w-full max-w-md mx-auto">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <header class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Xác thực OTP</h1>
                <p class="text-gray-500 mt-1 text-sm">Xác thực danh tính người dùng</p>
            </header>

            <section id="step-user">
                <h2 class="text-lg font-semibold text-gray-800 mb-5">Xác thực người dùng</h2>
                <label for="identifier" class="block text-sm font-medium text-gray-700 mb-1">Mã định danh hoặc email</label>
                <input id="identifier" type="text" autocomplete="username"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Nhập mã định danh hoặc email">

                <div id="user-preview" class="hidden mt-5 items-center gap-3 rounded-lg border border-gray-200 p-4">
                    <div id="avatar-fallback" class="h-12 w-12 shrink-0 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-lg font-semibold"></div>
                    <img id="avatar-image" class="hidden h-12 w-12 shrink-0 rounded-full object-cover" alt="Ảnh đại diện">
                    <div class="min-w-0">
                        <p id="user-name" class="font-semibold text-gray-800"></p>
                        <p id="user-email" class="text-sm text-gray-500"></p>
                    </div>
                </div>

                <button id="lookup-button" type="button" onclick="checkUser()"
                    class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-medium transition duration-200">
                    Kiểm tra người dùng
                </button>
                <button id="continue-button" type="button" onclick="showSection('step-method')"
                    class="hidden mt-3 w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-medium transition duration-200">
                    Tiếp tục
                </button>
            </section>

            <section id="step-method" class="hidden">
                <h2 class="text-lg font-semibold text-gray-800 mb-5">Nhận mã xác thực</h2>
                <p class="text-sm text-gray-600 mb-4">Mã OTP sẽ được gửi qua email đã đăng ký.</p>
                <label class="flex items-center gap-3 border border-gray-200 p-4 rounded-lg">
                    <input type="radio" name="otp-method" value="email" checked class="accent-blue-600">
                    <span class="text-sm text-gray-700">Email</span>
                </label>
                <div class="mt-6 flex gap-3">
                    <button type="button" onclick="showSection('step-user')"
                        class="flex-1 border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2.5 rounded-lg font-medium transition">Quay lại</button>
                    <button type="button" onclick="sendOTP()"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg font-medium transition">Gửi mã OTP</button>
                </div>
            </section>

            <section id="step-otp" class="hidden">
                <h2 class="text-lg font-semibold text-gray-800 mb-5">Nhập mã OTP</h2>
                <p id="otp-message" class="text-sm text-gray-600 mb-4">Mã OTP có hiệu lực trong 5 phút.</p>
                <label for="otp-code" class="block text-sm font-medium text-gray-700 mb-1">Mã OTP</label>
                <input id="otp-code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-center text-xl tracking-[0.5em] focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="••••••">
                <div class="mt-4 flex justify-between text-sm text-gray-600">
                    <span>Thời gian còn lại</span><strong id="timer" class="text-gray-800">05:00</strong>
                </div>
                <div class="mt-2 flex justify-between text-sm text-gray-600">
                    <span>Số lần nhập</span><strong id="attempt" class="text-gray-800">0 / 5</strong>
                </div>
                <div class="mt-6 flex gap-3">
                    <button type="button" onclick="showSection('step-method')"
                        class="flex-1 border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2.5 rounded-lg font-medium transition">Quay lại</button>
                    <button type="button" onclick="verifyOTP()"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg font-medium transition">Xác thực</button>
                </div>
            </section>

            <section id="step-result" class="hidden">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Kết quả xác thực</h2>
                <p id="result-message" class="text-gray-600"></p>
                <button type="button" onclick="restart()"
                    class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg font-medium transition">Thực hiện lại</button>
            </section>

            <div id="message" role="status" class="hidden mt-5 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"></div>
        </div>
    </main>

    <script>
        const csrfToken = <?= json_encode($csrf) ?>;
        const sections = ['step-user', 'step-method', 'step-otp', 'step-result'];
        let timerInterval = null;
        let remainingSeconds = 300;

        async function request(action, values = {}) {
            const body = new URLSearchParams({ action, csrf: csrfToken, ...values });
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body
            });
            return response.json();
        }

        function showSection(id) {
            sections.forEach(section => document.getElementById(section).classList.add('hidden'));
            document.getElementById(id).classList.remove('hidden');
            hideMessage();
        }

        function showMessage(message) {
            const element = document.getElementById('message');
            element.textContent = message;
            element.classList.remove('hidden');
        }

        function hideMessage() {
            document.getElementById('message').classList.add('hidden');
        }

        async function checkUser() {
            hideMessage();
            const identifier = document.getElementById('identifier').value.trim();
            if (!identifier) {
                showMessage('Vui lòng nhập mã định danh hoặc email.');
                return;
            }

            try {
                const result = await request('lookup', { identifier });
                if (!result.ok) {
                    document.getElementById('user-preview').classList.add('hidden');
                    document.getElementById('continue-button').classList.add('hidden');
                    showMessage(result.message);
                    return;
                }

                const user = result.user;
                const name = user.ten || 'Người dùng';
                document.getElementById('user-name').textContent = name;
                document.getElementById('user-email').textContent = user.has_email
                    ? `Email nhận mã: ${user.email}`
                    : 'Tài khoản chưa có email hợp lệ';

                const image = document.getElementById('avatar-image');
                const fallback = document.getElementById('avatar-fallback');
                const initial = Array.from(name.trim())[0] || '?';
                fallback.textContent = initial.toLocaleUpperCase('vi');
                image.onerror = () => {
                    image.classList.add('hidden');
                    fallback.classList.remove('hidden');
                };
                if (user.anhdaidien) {
                    image.src = user.anhdaidien;
                    image.classList.remove('hidden');
                    fallback.classList.add('hidden');
                } else {
                    image.removeAttribute('src');
                    image.classList.add('hidden');
                    fallback.classList.remove('hidden');
                }

                document.getElementById('user-preview').classList.remove('hidden');
                document.getElementById('user-preview').classList.add('flex');
                document.getElementById('continue-button').classList.remove('hidden');
            } catch (error) {
                showMessage('Không thể kết nối để tra cứu người dùng.');
            }
        }

        async function sendOTP() {
            hideMessage();
            const method = document.querySelector('input[name="otp-method"]:checked')?.value || '';
            try {
                const result = await request('send', { method });
                if (!result.ok) {
                    showMessage(result.message);
                    return;
                }
                document.getElementById('otp-message').textContent = result.message;
                document.getElementById('otp-code').value = '';
                document.getElementById('attempt').textContent = '0 / 5';
                remainingSeconds = 300;
                updateTimer();
                showSection('step-otp');
                startTimer();
            } catch (error) {
                showMessage('Không thể gửi mã OTP. Vui lòng thử lại.');
            }
        }

        async function verifyOTP() {
            hideMessage();
            const code = document.getElementById('otp-code').value.trim();
            if (!/^\d{6}$/.test(code)) {
                showMessage('Vui lòng nhập mã OTP gồm 6 chữ số.');
                return;
            }
            try {
                const result = await request('verify', { code });
                if (!result.ok) {
                    if (typeof result.attempts === 'number') {
                        document.getElementById('attempt').textContent = `${result.attempts} / 5`;
                    }
                    showMessage(result.message);
                    return;
                }
                stopTimer();
                document.getElementById('result-message').textContent = result.message;
                showSection('step-result');
                if (result.redirect_to) {
                    window.setTimeout(() => window.location.assign(result.redirect_to), 700);
                }
            } catch (error) {
                showMessage('Không thể xác thực mã OTP. Vui lòng thử lại.');
            }
        }

        function startTimer() {
            stopTimer();
            timerInterval = setInterval(() => {
                remainingSeconds--;
                updateTimer();
                if (remainingSeconds <= 0) {
                    stopTimer();
                    showMessage('Mã OTP đã hết hạn. Hãy yêu cầu mã mới.');
                }
            }, 1000);
        }

        function updateTimer() {
            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;
            document.getElementById('timer').textContent =
                `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        function stopTimer() {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
        }

        async function restart() {
            stopTimer();
            await request('restart').catch(() => {});
            document.getElementById('identifier').value = '';
            document.getElementById('otp-code').value = '';
            document.getElementById('user-preview').classList.add('hidden');
            document.getElementById('continue-button').classList.add('hidden');
            showSection('step-user');
        }

        document.getElementById('identifier').addEventListener('keydown', event => {
            if (event.key === 'Enter') checkUser();
        });
        document.getElementById('otp-code').addEventListener('keydown', event => {
            if (event.key === 'Enter') verifyOTP();
        });
    </script>
</body>
</html>
