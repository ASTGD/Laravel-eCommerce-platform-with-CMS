<?php

namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Platform\PlatformSupport\Services\SecurityAuditLogger;
use Webkul\Admin\Http\Controllers\Controller;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    public function __construct(protected SecurityAuditLogger $securityAuditLogger) {}

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param  string|null  $token
     * @return Factory|View
     */
    public function create($token = null)
    {
        return view('admin::users.reset-password.create')->with([
            'token' => $token,
            'email' => request('email'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        try {
            $this->validate(request(), [
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|confirmed|min:6',
            ]);

            $response = $this->broker()->reset(
                request(['email', 'password', 'password_confirmation', 'token']), function ($admin, $password) {
                    $this->resetPassword($admin, $password);
                }
            );

            if ($response == Password::PASSWORD_RESET) {
                request()->session()->regenerate();

                $this->securityAuditLogger->logForActor('password_reset.completed', auth()->guard('admin')->user(), [
                    'guard' => 'admin',
                    'email' => request('email'),
                    'ip' => request()->ip(),
                ]);

                return redirect()->route('admin.dashboard.index');
            }

            $this->securityAuditLogger->log('password_reset.failed', payload: [
                'guard' => 'admin',
                'email' => request('email'),
                'response' => $response,
                'ip' => request()->ip(),
            ]);

            return back()
                ->withInput(request(['email']))
                ->withErrors([
                    'email' => trans($response),
                ]);
        } catch (\Exception $e) {
            session()->flash('error', trans($e->getMessage()));

            return redirect()->back();
        }
    }

    /**
     * Reset the given admin's password.
     *
     * @param  CanResetPassword  $admin
     * @param  string  $password
     * @return void
     */
    protected function resetPassword($admin, $password)
    {
        $admin->password = Hash::make($password);

        $admin->setRememberToken(Str::random(60));

        $admin->save();

        event(new PasswordReset($admin));

        auth()->guard('admin')->login($admin);
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return PasswordBroker
     */
    public function broker()
    {
        return Password::broker('admins');
    }
}
