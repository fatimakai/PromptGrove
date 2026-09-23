<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Throwable;

class TwoFactorAuthenticationService
{
    public function __construct(private readonly Google2FA $google2fa) {}

    public function beginSetup(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();
        $codes = $this->newRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $this->hashRecoveryCodes($codes),
            'two_factor_confirmed_at' => null,
            'two_factor_last_used_counter' => null,
        ])->save();

        return $codes;
    }

    public function confirm(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code): bool {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if (blank($lockedUser->two_factor_secret) || $lockedUser->two_factor_confirmed_at !== null) {
                return false;
            }

            $counter = $this->verifyTotp($lockedUser, $code, 0);

            if ($counter === false) {
                return false;
            }

            $lockedUser->forceFill([
                'two_factor_confirmed_at' => now(),
                'two_factor_last_used_counter' => $counter,
                'remember_token' => Str::random(60),
            ])->save();

            $user->refresh();

            return true;
        });
    }

    public function verify(User $user, string $code): bool
    {
        $code = trim($code);

        if (preg_match('/^\d{6}$/', $code) === 1) {
            return $this->verifyAndAdvanceTotp($user, $code);
        }

        return $this->consumeRecoveryCode($user, Str::lower($code));
    }

    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->newRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => $this->hashRecoveryCodes($codes),
        ])->save();

        return $codes;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_used_counter' => null,
            'remember_token' => Str::random(60),
        ])->save();
    }

    public function qrCodeDataUri(User $user): ?string
    {
        if (blank($user->two_factor_secret)) {
            return null;
        }

        try {
            $uri = $this->google2fa->getQRCodeUrl(
                config('app.name', 'PromptGrove'),
                $user->email,
                $user->two_factor_secret,
            );
            $renderer = new ImageRenderer(new RendererStyle(220, 2), new SvgImageBackEnd);
            $svg = (new Writer($renderer))->writeString($uri);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private function verifyAndAdvanceTotp(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code): bool {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if (! $lockedUser->twoFactorEnabled()) {
                return false;
            }

            $counter = $this->verifyTotp(
                $lockedUser,
                $code,
                $lockedUser->two_factor_last_used_counter ?? 0,
            );

            if ($counter === false) {
                return false;
            }

            $lockedUser->forceFill(['two_factor_last_used_counter' => $counter])->save();
            $user->refresh();

            return true;
        });
    }

    private function verifyTotp(User $user, string $code, int $oldCounter): int|false
    {
        $result = $this->google2fa->verifyKeyNewer(
            $user->two_factor_secret,
            $code,
            $oldCounter,
            max(0, (int) config('two-factor.window')),
        );

        return is_int($result) ? $result : false;
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        if ($code === '') {
            return false;
        }

        return DB::transaction(function () use ($user, $code): bool {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if (! $lockedUser->twoFactorEnabled()) {
                return false;
            }

            $hashes = $lockedUser->two_factor_recovery_codes ?? [];

            foreach ($hashes as $index => $hash) {
                if (! Hash::check($code, $hash)) {
                    continue;
                }

                unset($hashes[$index]);
                $lockedUser->forceFill([
                    'two_factor_recovery_codes' => array_values($hashes),
                ])->save();
                $user->refresh();

                return true;
            }

            return false;
        });
    }

    private function newRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => Str::lower(Str::random(5).'-'.Str::random(5)))
            ->all();
    }

    private function hashRecoveryCodes(array $codes): array
    {
        return array_map(fn (string $code) => Hash::make($code), $codes);
    }
}
