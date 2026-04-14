<?php

namespace Webkul\Shop\Http\Controllers\Customer;

use Cookie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Webkul\Core\Repositories\SubscribersListRepository;
use Webkul\Customer\Repositories\CustomerGroupRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Shop\Http\Controllers\Controller;
use Webkul\Shop\Http\Requests\Customer\RegistrationRequest;
use Webkul\Shop\Mail\Customer\EmailVerificationNotification;
use Webkul\Shop\Mail\Customer\RegistrationNotification;

class RegistrationController extends Controller
{
    public const REGISTRATION_NOTICE_SESSION_KEY = 'customer_registration_notice';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected CustomerRepository $customerRepository,
        protected CustomerGroupRepository $customerGroupRepository,
        protected SubscribersListRepository $subscriptionRepository
    ) {}

    /**
     * Opens up the user's sign up form.
     *
     * @return View
     */
    public function index()
    {
        return view('shop::customers.sign-up');
    }

    /**
     * Method to store user's sign up form data to DB.
     *
     * @return Response
     */
    public function store(RegistrationRequest $registrationRequest)
    {
        $emailVerificationEnabled = (bool) core()->getConfigData('customer.settings.email.verification');

        $customerGroup = core()->getConfigData('customer.settings.create_new_account_options.default_group');

        $subscription = $this->subscriptionRepository->findOneWhere(['email' => request()->input('email')]);

        $data = array_merge($registrationRequest->only([
            'first_name',
            'last_name',
            'email',
            'password_confirmation',
            'is_subscribed',
        ]), [
            'password' => bcrypt(request()->input('password')),
            'api_token' => Str::random(80),
            'is_verified' => ! $emailVerificationEnabled,
            'customer_group_id' => $this->customerGroupRepository->findOneWhere(['code' => $customerGroup])->id,
            'channel_id' => core()->getCurrentChannel()->id,
            'token' => md5(uniqid(rand(), true)),
            'subscribed_to_news_letter' => (bool) (request()->input('is_subscribed') ?? $subscription?->is_subscribed),
        ]);

        Event::dispatch('customer.registration.before');

        $customer = $this->customerRepository->create($data);

        if ($subscription) {
            $this->subscriptionRepository->update([
                'customer_id' => $customer->id,
            ], $subscription->id);
        }

        if (
            ! empty($data['is_subscribed'])
            && ! $subscription
        ) {
            Event::dispatch('customer.subscription.before');

            $subscription = $this->subscriptionRepository->create([
                'email' => $data['email'],
                'customer_id' => $customer->id,
                'channel_id' => core()->getCurrentChannel()->id,
                'is_subscribed' => 1,
                'token' => uniqid(),
            ]);

            Event::dispatch('customer.subscription.after', $subscription);
        }

        Event::dispatch('customer.create.after', $customer);

        Event::dispatch('customer.registration.after', $customer);

        session()->put(self::REGISTRATION_NOTICE_SESSION_KEY, [
            'email'                 => $customer->email,
            'requires_verification' => $emailVerificationEnabled,
        ]);

        return redirect()->route('shop.customers.register.result');
    }

    /**
     * Show the post-registration result page.
     */
    public function registrationResult(): View|RedirectResponse
    {
        if (! session()->has(self::REGISTRATION_NOTICE_SESSION_KEY)) {
            return redirect()->route('shop.customers.register.index');
        }

        return view('shop::customers.registration-result', [
            'notice' => session(self::REGISTRATION_NOTICE_SESSION_KEY),
        ]);
    }

    /**
     * Method to verify account.
     *
     * @param  string  $token
     * @return Response
     */
    public function verifyAccount($token)
    {
        $customer = $this->customerRepository->findOneByField('token', $token);

        if ($customer) {
            $this->customerRepository->update([
                'is_verified' => 1,
                'token' => null,
            ], $customer->id);

            session()->forget(self::REGISTRATION_NOTICE_SESSION_KEY);

            if ((bool) core()->getConfigData('emails.general.notifications.emails.general.notifications.registration')) {
                Mail::queue(new RegistrationNotification($customer));
            }

            $this->customerRepository->syncNewRegisteredCustomerInformation($customer);

            session()->flash('success', trans('shop::app.customers.signup-form.verified'));
        } else {
            session()->flash('warning', trans('shop::app.customers.signup-form.verify-failed'));
        }

        return redirect()->route('shop.customer.session.index');
    }

    /**
     * Resend verification email.
     *
     * @return Response
     */
    public function resendVerificationEmail(Request $request, ?string $email = null)
    {
        $email ??= (string) $request->string('email')->trim();

        if (! $email) {
            session()->flash('error', trans('shop::app.customers.signup-form.verification-not-sent'));

            return redirect()->back();
        }

        $verificationData = [
            'email' => $email,
            'token' => md5(uniqid(rand(), true)),
        ];

        $customer = $this->customerRepository->findOneByField('email', $email);

        if (! $customer) {
            session()->flash('error', trans('shop::app.customers.signup-form.verification-not-sent'));

            return redirect()->back();
        }

        $this->customerRepository->update(['token' => $verificationData['token']], $customer->id);

        try {
            Mail::queue(new EmailVerificationNotification($customer));

            if (Cookie::has('enable-resend')) {
                Cookie::queue(Cookie::forget('enable-resend'));
            }

            if (Cookie::has('email-for-resend')) {
                Cookie::queue(Cookie::forget('email-for-resend'));
            }
        } catch (\Exception $e) {
            report($e);

            session()->flash('error', trans('shop::app.customers.signup-form.verification-not-sent'));

            return redirect()->back();
        }

        session()->put(self::REGISTRATION_NOTICE_SESSION_KEY, [
            'email'                 => $email,
            'requires_verification' => true,
        ]);

        session()->flash('success', trans('shop::app.customers.signup-form.verification-sent'));

        return redirect()->back();
    }
}
