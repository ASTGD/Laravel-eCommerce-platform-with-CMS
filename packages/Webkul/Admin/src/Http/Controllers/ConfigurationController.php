<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Admin\Http\Requests\ConfigurationForm;
use Webkul\Core\Repositories\CoreConfigRepository;

class ConfigurationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected CoreConfigRepository $coreConfigRepository) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        if (
            request()->route('slug')
            && request()->route('slug2')
        ) {
            return view('admin::configuration.edit');
        }

        return view('admin::configuration.index');
    }

    /**
     * Display a listing of the resource.
     */
    public function search(): JsonResponse
    {
        $results = $this->coreConfigRepository->search(
            system_config()->getItems(),
            request()->query('query')
        );

        return new JsonResponse([
            'data' => $results,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ConfigurationForm $request): RedirectResponse
    {
        $data = $request->all();

        if (isset($data['sales']['carriers'])) {
            if (! $this->hasEnabledConfigurationGroup($data['sales']['carriers'])) {
                session()->flash('error', trans('admin::app.configuration.index.enable-at-least-one-shipping'));

                return redirect()->back();
            }
        } elseif (isset($data['sales']['payment_methods'])) {
            if (! $this->hasEnabledPaymentMethod($data)) {
                session()->flash('error', trans('admin::app.configuration.index.enable-at-least-one-payment'));

                return redirect()->back();
            }
        }

        $this->coreConfigRepository->create($request->except(['_token', 'admin_locale']));

        session()->flash('success', trans('admin::app.configuration.index.save-message'));

        return redirect()->back();
    }

    /**
     * Determine whether the submitted configuration contains at least one
     * toggleable group with the active flag enabled.
     */
    protected function hasEnabledConfigurationGroup(array $groups): bool
    {
        foreach ($groups as $group) {
            if (! is_array($group)) {
                continue;
            }

            if (
                array_key_exists('active', $group)
                && (bool) $group['active']
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Limit payment validation to the tab-selected method groups when the
     * payment-method configuration UI provides that metadata.
     */
    protected function hasEnabledPaymentMethod(array $data): bool
    {
        $groups = $data['sales']['payment_methods'] ?? [];

        $activeConfiguration = system_config()->getActiveConfigurationItem();

        if (
            ! $activeConfiguration
            || $activeConfiguration->getChildren()->isEmpty()
        ) {
            return $this->hasEnabledConfigurationGroup($groups);
        }

        foreach ($activeConfiguration->getChildren() as $child) {
            $groupKey = Str::afterLast($child->getKey(), '.');

            $hasActiveToggle = $child->getFields()->contains(
                fn ($field) => $field->getName() === 'active'
            );

            if (! $hasActiveToggle) {
                continue;
            }

            $submittedGroup = $groups[$groupKey] ?? null;

            if (
                is_array($submittedGroup)
                && array_key_exists('active', $submittedGroup)
            ) {
                if ((bool) $submittedGroup['active']) {
                    return true;
                }

                continue;
            }

            if ((bool) system_config()->getConfigData($child->getKey().'.active', $data['channel'] ?? null, $data['locale'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Download the file for the specified resource.
     */
    public function download(): StreamedResponse
    {
        $path = request()->route()->parameters()['path'];

        $fileName = 'configuration/'.$path;

        $config = $this->coreConfigRepository->findOneByField('value', $fileName);

        return Storage::download($config['value']);
    }
}
