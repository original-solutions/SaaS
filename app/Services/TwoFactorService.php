<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    protected Google2FA $engine;

    public function __construct()
    {
        $this->engine = new Google2FA;
    }

    /**
     * Generate a new 2FA secret for the user.
     *
     * @return array{secret: string, qr_uri: string}
     */
    public function enable(User $user): array
    {
        $secret = $this->engine->generateSecretKey();

        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($this->generateRecoveryCodesArray())),
        ]);

        $qrUri = $this->engine->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret,
        );

        return [
            'secret' => $secret,
            'qr_uri' => $qrUri,
        ];
    }

    /**
     * Confirm 2FA activation by verifying a TOTP code.
     */
    public function confirm(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        $secret = decrypt($user->two_factor_secret);

        return $this->engine->verifyKey($secret, $code);
    }

    /**
     * Disable 2FA for the user.
     */
    public function disable(User $user): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);
    }

    /**
     * Verify a TOTP code.
     */
    public function verifyCode(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        $secret = decrypt($user->two_factor_secret);

        return $this->engine->verifyKey($secret, $code);
    }

    /**
     * Verify a recovery code (one-time use).
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        if (! $user->two_factor_recovery_codes) {
            return false;
        }

        $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        if (! in_array($code, $codes, true)) {
            return false;
        }

        // Remove the used recovery code
        $codes = array_values(array_filter($codes, fn (string $c) => $c !== $code));

        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ]);

        return true;
    }

    /**
     * Regenerate recovery codes for the user.
     *
     * @return list<string>
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->generateRecoveryCodesArray();

        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ]);

        return $codes;
    }

    /**
     * Generate a set of recovery codes.
     *
     * @return list<string>
     */
    protected function generateRecoveryCodesArray(): array
    {
        return Collection::times(8, fn () => Str::random(10).'-'.Str::random(10))->all();
    }
}
