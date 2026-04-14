<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\CommerceCore\DataGrids\Sales\PickupPointDataGrid;
use Platform\CommerceCore\Http\Requests\Admin\PickupPointRequest;
use Platform\CommerceCore\Models\PickupPoint;
use Platform\CommerceCore\Repositories\PickupPointRepository;
use Platform\CommerceCore\Services\PickupPointService;

class PickupPointController extends Controller
{
    public function __construct(
        protected PickupPointRepository $pickupPointRepository,
        protected PickupPointService $pickupPointService,
    ) {}

    public function index()
    {
        if (request()->ajax()) {
            return datagrid(PickupPointDataGrid::class)->process();
        }

        return view('commerce-core::admin.pickup-points.index');
    }

    public function create(): View
    {
        return view('commerce-core::admin.pickup-points.form', [
            'pickupPoint' => new PickupPoint([
                'country'   => core()->getConfigData('sales.shipping.origin.country') ?: 'BD',
                'is_active' => true,
            ]),
        ]);
    }

    public function store(PickupPointRequest $request): RedirectResponse
    {
        $pickupPoint = $this->pickupPointRepository->create($request->payload());

        return redirect()
            ->route('admin.sales.pickup-points.edit', $pickupPoint)
            ->with('success', 'Pickup point created.');
    }

    public function edit(PickupPoint $pickupPoint): View
    {
        return view('commerce-core::admin.pickup-points.form', compact('pickupPoint'));
    }

    public function update(PickupPointRequest $request, PickupPoint $pickupPoint): RedirectResponse
    {
        $this->pickupPointRepository->update($request->payload(), $pickupPoint->id);

        return redirect()
            ->route('admin.sales.pickup-points.edit', $pickupPoint)
            ->with('success', 'Pickup point updated.');
    }

    public function destroy(PickupPoint $pickupPoint): JsonResponse|RedirectResponse
    {
        if ($this->pickupPointService->isInUse($pickupPoint)) {
            if (request()->ajax()) {
                return new JsonResponse([
                    'message' => 'This pickup point is already referenced by carts or orders. Disable it instead of deleting it.',
                ], 422);
            }

            return redirect()
                ->route('admin.sales.pickup-points.index')
                ->with('error', 'This pickup point is already referenced by carts or orders. Disable it instead of deleting it.');
        }

        $this->pickupPointRepository->delete($pickupPoint->id);

        if (request()->ajax()) {
            return new JsonResponse([
                'message' => 'Pickup point deleted.',
            ]);
        }

        return redirect()
            ->route('admin.sales.pickup-points.index')
            ->with('success', 'Pickup point deleted.');
    }
}
