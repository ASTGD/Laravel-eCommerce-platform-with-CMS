<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Event;
use Platform\PlatformSupport\Services\SecurityAuditLogger;
use Webkul\Shop\Http\Requests\Customer\LoginRequest;

class CustomerController extends APIController
{
    public function __construct(protected SecurityAuditLogger $securityAuditLogger) {}

    /**
     * Login Customer
     *
     * @return JsonResponse
     */
    public function login(LoginRequest $request)
    {
        if (! auth()->guard('customer')->attempt($request->only(['email', 'password']))) {
            $this->securityAuditLogger->log('customer.login.failed', payload: [
                'email' => $request->input('email'),
                'ip' => $request->ip(),
                'surface' => 'api',
            ]);

            return response()->json([
                'message' => trans('shop::app.customers.login-form.invalid-credentials'),
            ], Response::HTTP_FORBIDDEN);
        }

        if (! auth()->guard('customer')->user()->status) {
            $customer = auth()->guard('customer')->user();

            $this->securityAuditLogger->logForActor('customer.login.blocked_inactive', $customer, [
                'email' => $request->input('email'),
                'ip' => $request->ip(),
                'surface' => 'api',
            ]);

            auth()->guard('customer')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'message' => trans('shop::app.customers.login-form.not-activated'),
            ], Response::HTTP_FORBIDDEN);
        }

        if (! auth()->guard('customer')->user()->is_verified) {
            $customer = auth()->guard('customer')->user();

            $this->securityAuditLogger->logForActor('customer.login.blocked_unverified', $customer, [
                'email' => $request->input('email'),
                'ip' => $request->ip(),
                'surface' => 'api',
            ]);

            Cookie::queue(Cookie::make('enable-resend', 'true', 1));

            Cookie::queue(Cookie::make('email-for-resend', $request->get('email'), 1));

            auth()->guard('customer')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'message' => trans('shop::app.customers.login-form.verify-first'),
            ], Response::HTTP_FORBIDDEN);
        }

        $request->session()->regenerate();

        /**
         * Event passed to prepare cart after login.
         */
        Event::dispatch('customer.after.login', auth()->guard()->user());

        $this->securityAuditLogger->logForActor('customer.login.success', auth()->guard('customer')->user(), [
            'ip' => $request->ip(),
            'surface' => 'api',
        ]);

        return response()->json([]);
    }
}
