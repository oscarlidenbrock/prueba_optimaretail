<?php

use App\Models\User;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

test('two factor factory state builds encrypted confirmed credentials without enabling the feature', function () {
    $this->freezeTime();
    $factory = User::factory();

    $user = $factory->withTwoFactor()->make();

    $encrypter = Fortify::currentEncrypter();
    $secret = $encrypter->decrypt($user->two_factor_secret);
    $recoveryCodes = json_decode($encrypter->decrypt($user->two_factor_recovery_codes), true, flags: JSON_THROW_ON_ERROR);

    expect($secret)->toMatch('/^[A-Z2-7]+$/');
    expect($recoveryCodes)->toHaveCount(8);

    foreach ($recoveryCodes as $recoveryCode) {
        expect($recoveryCode)->toBeString()->not->toBeEmpty();
    }

    expect(array_unique($recoveryCodes))->toHaveCount(8);
    expect($user->two_factor_confirmed_at)->toEqual(now());
    expect($factory->make()->two_factor_secret)->toBeNull();
    expect(Features::enabled(Features::twoFactorAuthentication()))->toBeFalse();
});
