<?php

namespace Webkul\Shop\Http\Controllers\Customer;

use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Platform\PlatformSupport\Services\SecurityAuditLogger;
use Webkul\Shop\Http\Controllers\Controller;
use Webkul\Shop\Http\Requests\Customer\ForgotPasswordRequest;

class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function __construct(protected SecurityAuditLogger $securityAuditLogger) {}

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create()
    {
        return view('shop::customers.forgot-password');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return void
     */
    public function store(ForgotPasswordRequest $request)
    {
        $request->validated();

        try {
            $response = $this->broker()->sendResetLink($request->only(['email']));

            $this->securityAuditLogger->log('password_reset.requested', payload: [
                'guard' => 'customer',
                'email' => $request->input('email'),
                'response' => $response,
                'ip' => $request->ip(),
            ]);

            if ($response == Password::RESET_LINK_SENT) {
                session()->flash('success', trans('shop::app.customers.forgot-password.reset-link-sent'));

                return redirect()->route('shop.customers.forgot_password.create');
            }

            if ($response == Password::RESET_THROTTLED) {
                session()->flash('warning', trans('shop::app.customers.forgot-password.already-sent'));

                return redirect()->route('shop.customers.forgot_password.create');
            }

            return redirect()->route('shop.customers.forgot_password.create')
                ->withInput($request->only(['email']))
                ->withErrors([
                    'email' => trans('shop::app.customers.forgot-password.email-not-exist'),
                ]);
        } catch (\Swift_RfcComplianceException $e) {
            session()->flash('success', trans('shop::app.customers.forgot-password.reset-link-sent'));

            return redirect()->route('shop.customers.forgot_password.create');
        } catch (\Exception $e) {
            report($e);

            session()->flash('error', $e->getMessage());

            return redirect()->route('shop.customers.forgot_password.create');
        }
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return PasswordBroker
     */
    public function broker()
    {
        return Password::broker('customers');
    }
}
