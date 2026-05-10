<?php

namespace Webkul\Shop\Http\Controllers\Customer;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Platform\PlatformSupport\Services\SecurityAuditLogger;
use Webkul\Shop\Http\Controllers\Controller;
use Webkul\Shop\Http\Requests\Customer\LoginRequest;

class SessionController extends Controller
{
    public function __construct(protected SecurityAuditLogger $securityAuditLogger) {}

    /**
     * Display the resource.
     *
     * @return RedirectResponse|View
     */
    public function index()
    {
        if (auth()->guard('customer')->check()) {
            return redirect()->route($this->resolvedRedirectRoute(request('redirect_to')));
        }

        return view('shop::customers.sign-in');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function store(LoginRequest $loginRequest)
    {
        $credentials = $loginRequest->only(['email', 'password']);

        $credentials['channel_id'] = core()->getCurrentChannel()->id;

        if (! auth()->guard('customer')->attempt($credentials)) {
            $this->securityAuditLogger->log('customer.login.failed', payload: [
                'email' => $loginRequest->input('email'),
                'ip' => $loginRequest->ip(),
            ]);

            session()->flash('error', trans('shop::app.customers.login-form.invalid-credentials'));

            return redirect()->back();
        }

        if (! auth()->guard('customer')->user()->status) {
            $customer = auth()->guard('customer')->user();

            $this->securityAuditLogger->logForActor('customer.login.blocked_inactive', $customer, [
                'email' => $loginRequest->input('email'),
                'ip' => $loginRequest->ip(),
            ]);

            auth()->guard('customer')->logout();
            $loginRequest->session()->invalidate();
            $loginRequest->session()->regenerateToken();

            session()->flash('warning', trans('shop::app.customers.login-form.not-activated'));

            return redirect()->back();
        }

        if (! auth()->guard('customer')->user()->is_verified) {
            $customer = auth()->guard('customer')->user();

            $this->securityAuditLogger->logForActor('customer.login.blocked_unverified', $customer, [
                'email' => $loginRequest->input('email'),
                'ip' => $loginRequest->ip(),
            ]);

            auth()->guard('customer')->logout();
            $loginRequest->session()->invalidate();
            $loginRequest->session()->regenerateToken();

            session()->flash('info', trans('shop::app.customers.login-form.verify-first'));

            Cookie::queue(Cookie::make('enable-resend', 'true', 1));

            Cookie::queue(Cookie::make('email-for-resend', $loginRequest->get('email'), 1));

            return redirect()->back();
        }

        $loginRequest->session()->regenerate();

        /**
         * Event passed to prepare cart after login.
         */
        Event::dispatch('customer.after.login', auth()->guard()->user());

        session()->forget(RegistrationController::REGISTRATION_NOTICE_SESSION_KEY);

        $this->securityAuditLogger->logForActor('customer.login.success', auth()->guard('customer')->user(), [
            'ip' => $loginRequest->ip(),
        ]);

        return redirect()->route($this->resolvedRedirectRoute($loginRequest->input('redirect_to')));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy()
    {
        $customer = auth()->guard('customer')->user();
        $id = $customer->id;

        auth()->guard('customer')->logout();

        Event::dispatch('customer.after.logout', $id);

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->securityAuditLogger->logForActor('customer.logout', $customer, [
            'ip' => request()->ip(),
        ]);

        return redirect()->route('shop.home.index');
    }

    /**
     * Resolve the post-login redirect route.
     */
    protected function resolvedRedirectRoute(?string $requestedRedirect = null): string
    {
        if ($requestedRedirect === 'account') {
            return 'shop.customers.account.index';
        }

        if (core()->getConfigData('customer.settings.login_options.redirected_to_page') === 'account') {
            return 'shop.customers.account.index';
        }

        return 'shop.home.index';
    }
}
