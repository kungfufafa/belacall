<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class OtpService
{
    protected FonnteService $fonnteService;

    protected int $otpLength = 6;

    protected int $expiryMinutes = 5;

    protected int $maxAttempts = 5;

    protected int $resendCooldownSeconds = 60;

    public function __construct(FonnteService $fonnteService)
    {
        $this->fonnteService = $fonnteService;
    }

    /**
     * Generate OTP code for a phone number
     */
    public function generate(string $phone): OtpCode
    {
        // Invalidate existing unverified OTPs for this phone
        OtpCode::query()
            ->where('phone', $phone)
            ->whereNull('verified_at')
            ->delete();

        // Generate new OTP
        $code = $this->generateCode();

        return OtpCode::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes($this->expiryMinutes),
            'attempts' => 0,
        ]);
    }

    /**
     * Send OTP via WhatsApp
     */
    public function send(string $phone): bool
    {
        // Check cooldown
        $lastOtp = OtpCode::query()
            ->where('phone', $phone)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if ($lastOtp && $lastOtp->created_at->diffInSeconds(now()) < $this->resendCooldownSeconds) {
            return false;
        }

        $otp = $this->generate($phone);

        $message = "🔐 *Kode OTP Anda*\n\n"
            ."Kode: *{$otp->code}*\n\n"
            ."Kode ini berlaku selama {$this->expiryMinutes} menit.\n"
            .'Jangan bagikan kode ini kepada siapapun.';

        $result = $this->fonnteService->sendText($phone, $message);

        if ($result === false) {
            Log::error("Failed to send OTP to {$phone}");

            return false;
        }

        Log::info("OTP sent to {$phone}");

        return true;
    }

    /**
     * Verify OTP code
     *
     * @return array{valid: bool, message: string, user?: User}
     */
    public function verify(string $phone, string $code): array
    {
        $otp = OtpCode::query()
            ->where('phone', $phone)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $otp) {
            return [
                'valid' => false,
                'message' => 'Kode OTP tidak ditemukan. Silakan minta kode baru.',
            ];
        }

        if ($otp->isExpired()) {
            return [
                'valid' => false,
                'message' => 'Kode OTP sudah kedaluwarsa. Silakan minta kode baru.',
            ];
        }

        if ($otp->hasExceededMaxAttempts($this->maxAttempts)) {
            return [
                'valid' => false,
                'message' => 'Terlalu banyak percobaan. Silakan minta kode baru.',
            ];
        }

        if ($otp->code !== $code) {
            $otp->incrementAttempts();

            return [
                'valid' => false,
                'message' => 'Kode OTP salah. Silakan coba lagi.',
            ];
        }

        // Mark OTP as verified
        $otp->markAsVerified();

        // Find or create user
        $user = $this->findOrCreateUser($phone);

        return [
            'valid' => true,
            'message' => 'Verifikasi berhasil.',
            'user' => $user,
        ];
    }

    /**
     * Find existing user or create new one
     */
    protected function findOrCreateUser(string $phone): User
    {
        $user = User::query()->where('phone', $phone)->first();

        if (! $user) {
            $user = User::create([
                'name' => $this->generateNameFromPhone($phone),
                'phone' => $phone,
                'role' => \App\Enums\Role::WARGA,
            ]);
        }

        return $user;
    }

    /**
     * Generate a random numeric OTP code
     */
    protected function generateCode(): string
    {
        $min = (int) str_pad('1', $this->otpLength, '0');
        $max = (int) str_pad('9', $this->otpLength, '9');

        return (string) random_int($min, $max);
    }

    /**
     * Generate a temporary name from phone number
     */
    protected function generateNameFromPhone(string $phone): string
    {
        $lastDigits = substr($phone, -4);

        return "Warga {$lastDigits}";
    }

    /**
     * Check if phone can request new OTP (cooldown check)
     */
    public function canRequestOtp(string $phone): bool
    {
        $lastOtp = OtpCode::query()
            ->where('phone', $phone)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $lastOtp) {
            return true;
        }

        return $lastOtp->created_at->diffInSeconds(now()) >= $this->resendCooldownSeconds;
    }

    /**
     * Get remaining cooldown seconds
     */
    public function getRemainingCooldown(string $phone): int
    {
        $lastOtp = OtpCode::query()
            ->where('phone', $phone)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $lastOtp) {
            return 0;
        }

        $elapsed = $lastOtp->created_at->diffInSeconds(now());

        return max(0, $this->resendCooldownSeconds - $elapsed);
    }
}
