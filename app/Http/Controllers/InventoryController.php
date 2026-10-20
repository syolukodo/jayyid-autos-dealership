<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Support\InventoryLanding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * /inventory and the indexable /cars-for-sale/{landing} pages share this
 * controller. Search engines see one canonical URL per stock view: a
 * single-filter query is folded into its landing page with a 301, and
 * other filter/sort combinations are noindex with a canonical to /inventory.
 */
class InventoryController extends Controller
{
    public function __invoke(Request $request, ?string $landing = null): View|RedirectResponse
    {
        $page = InventoryLanding::resolve((string) $landing);
        abort_if($landing !== null && $page === null, 404);

        $filters = array_map(
            fn ($value) => is_string($value) ? trim($value) : '',
            $request->only(['condition', 'make', 'body', 'budget']) + ['condition' => '', 'make' => '', 'body' => '', 'budget' => '']
        );
        $sort = array_key_exists((string) $request->query('sort'), Vehicle::SORTS) ? (string) $request->query('sort') : 'newest';

        if ($page === null && $sort === 'newest' && ($match = InventoryLanding::matching($filters))) {
            return redirect()->to($match->url().$this->pageQuery($request), 301);
        }

        if ($page !== null) {
            $filters = array_merge($filters, $page->filters);
        }

        $vehicles = Vehicle::query()
            ->inStock()
            ->filter($filters)
            ->sortBy($sort)
            ->paginate((int) config('showroom.inventory_per_page'))
            ->withQueryString();

        $baseUrl = $page?->url() ?? route('inventory.index');
        $isFiltered = $page === null && (array_filter($filters) !== [] || $sort !== 'newest');

        return view('pages.inventory', [
            'vehicles' => $vehicles,
            'filters' => $filters,
            'sort' => $sort,
            'landing' => $page,
            'fromPrice' => $page ? Vehicle::query()->inStock()->filter($page->filters)->min('price') : null,
            'canonical' => $baseUrl.($vehicles->currentPage() > 1 && ! $isFiltered ? '?page='.$vehicles->currentPage() : ''),
            'robots' => $isFiltered ? 'noindex, follow' : 'index, follow',
        ]);
    }

    private function pageQuery(Request $request): string
    {
        $page = (int) $request->query('page', 1);

        return $page > 1 ? '?page='.$page : '';
    }
}
