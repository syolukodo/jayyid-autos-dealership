<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PromoTarget;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePromoCodeRequest;
use App\Models\PromoCode;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Admin > Promo Codes. The add/edit form is a modal on the list page, so
 * create and edit render the list with the modal open.
 */
class PromoCodeController extends Controller
{
    public function index(): View
    {
        return $this->page();
    }

    public function create(): View
    {
        return $this->page(new PromoCode);
    }

    public function edit(PromoCode $promoCode): View
    {
        return $this->page($promoCode->load('vehicles:id'));
    }

    public function store(SavePromoCodeRequest $request): RedirectResponse
    {
        $promo = DB::transaction(fn () => $this->save(new PromoCode, $request));

        return redirect()->route('admin.promo-codes.index')->with('status', 'Promo code '.$promo->code.' saved.');
    }

    public function update(SavePromoCodeRequest $request, PromoCode $promoCode): RedirectResponse
    {
        DB::transaction(fn () => $this->save($promoCode, $request));

        return redirect()->route('admin.promo-codes.index')->with('status', 'Promo code '.$promoCode->code.' saved.');
    }

    public function toggle(PromoCode $promoCode): RedirectResponse
    {
        $promoCode->update(['is_active' => ! $promoCode->is_active]);

        return redirect()->route('admin.promo-codes.index')->with('status', 'Promo code status updated.');
    }

    public function destroy(PromoCode $promoCode): RedirectResponse
    {
        $promoCode->delete();

        return redirect()->route('admin.promo-codes.index')->with('status', 'Promo code deleted.');
    }

    private function save(PromoCode $promo, SavePromoCodeRequest $request): PromoCode
    {
        $target = PromoTarget::from($request->validated('target_type'));

        $promo->fill([
            'code' => $request->validated('code'),
            'title' => $request->validated('title'),
            'discount_type' => $request->validated('discount_type'),
            'discount_value' => $request->validated('discount_value'),
            'min_order_amount' => $request->validated('min_order_amount') ?? 0,
            'target_type' => $target,
            'target_brand' => $target === PromoTarget::Brand ? $request->validated('target_brand') : null,
            'target_category' => $target === PromoTarget::Category ? $request->validated('target_category') : null,
            'usage_limit' => $request->validated('usage_limit'),
            'usage_limit_per_user' => $request->validated('usage_limit_per_user') ?? 1,
            'start_date' => $request->validated('start_date'),
            'expiry_date' => $request->validated('expiry_date'),
            'is_active' => $request->boolean('is_active'),
        ])->save();

        $promo->vehicles()->sync($target === PromoTarget::SpecificVehicles ? $request->validated('vehicle_ids', []) : []);

        return $promo;
    }

    private function page(?PromoCode $editing = null): View
    {
        $promos = PromoCode::query()->withCount('redemptions')->orderBy('code')->get();
        $vehicles = Vehicle::query()->orderBy('id')->get(['id', 'title', 'make', 'category']);

        return view('admin.promo-codes', [
            'promos' => $promos,
            'editing' => $editing,
            'formOpen' => $editing !== null,
            'vehicles' => $vehicles,
            'brands' => $vehicles->pluck('make')->filter()->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'categories' => $vehicles->map(fn (Vehicle $v) => $v->category->value)->unique()->sort()->values(),
            'activeCount' => $promos->where('is_active', true)->count(),
            'totalUsed' => $promos->sum('redemptions_count'),
            'totalLimit' => $promos->sum('usage_limit'),
            'scopedCount' => $promos->where('target_type', '!==', PromoTarget::All)->count(),
        ]);
    }
}
