<?php

namespace App\Http\Middleware;

use App\Enums\JToastEnum;
use App\Support\TwilioClientIdentity;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'canAccessAdmin' => $request->user() && $request->user()->hasRole(['admin', 'super_admin']),
                'isSuperAdmin' => $request->user() && $request->user()->hasRole('super_admin'),
            ],
            'twilio' => [
                /** Twilio Voice Client name for operators and TwiML `<Dial><Client>` (ADMIN_IDENTITY). */
                'admin_identity' => TwilioClientIdentity::sanitize((string) config('services.twilio.admin_identity')),
                /** Voice SDK edge / signaling region — empty means SDK default. */
                'voice_sdk_edge' => (string) config('services.twilio.voice_sdk_edge'),
            ],
            'flash' => [
                JToastEnum::SUCCESS => fn () => $request->session()->get(JToastEnum::SUCCESS),
                JToastEnum::WARNING => fn () => $request->session()->get(JToastEnum::WARNING),
                JToastEnum::DANGER => fn () => $request->session()->get(JToastEnum::DANGER),
                JToastEnum::RESTORE => fn () => $request->session()->get(JToastEnum::RESTORE),
            ],
        ];
    }
}
