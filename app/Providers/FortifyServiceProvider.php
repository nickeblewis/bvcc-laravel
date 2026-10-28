<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Support\Present;
use App\Support\Seo;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => $this->sitePage('account/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ], new Seo(
            title: 'Log in',
            description: 'Log in to your '.config('site.short_name').' account, or register if you do not have one.',
            index: false,
        )));

        Fortify::resetPasswordView(fn (Request $request) => $this->sitePage('account/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ], new Seo(title: 'Choose a new password', description: 'Choose a new password for your account.', index: false)));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => $this->sitePage('account/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ], new Seo(title: 'Forgotten your password?', description: 'Get an email with a link to choose a new password.', index: false)));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => $this->sitePage('account/Register', [
            'tiers' => Present::tiers(),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ], new Seo(
            title: 'Register',
            description: 'Create your '.config('site.short_name').' account and choose a membership.',
            breadcrumbs: [['Log in', '/login']],
            index: false,
        )));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * The login, sign-up and password pages are public pages, drawn in the
     * site layout with their own Seo, like Site\SiteController::page().
     *
     * @param  array<string, mixed>  $props
     */
    private function sitePage(string $component, array $props, Seo $seo): Response
    {
        return Inertia::render("site/{$component}", [...$props, 'meta' => $seo->forClient()])
            ->withViewData(['seo' => $seo]);
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('passkeys', function (Request $request) {
            return Limit::perMinute(10)->by(
                ($request->input('credential.id') ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });
    }
}
