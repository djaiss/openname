<?php

namespace App\Http\Controllers;

use App\Http\Requests\NameListRequest;
use App\Http\ViewModels\User\ListViewModel;
use App\Models\ListCategory;
use App\Services\CreateList;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redirect;

class ListController extends Controller
{
    public function index(): View
    {
        $lists = Cache::remember('user-lists-' . auth()->id(), 604800, fn () => ListViewModel::index());

        return view('user.lists.index', [
            'lists' => $lists['lists'],
        ]);
    }

    public function new(): View
    {
        $listCategories = ListCategory::all()
            ->map(fn (ListCategory $listCategory) => [
                'id' => $listCategory->id,
                'name' => $listCategory->name,
            ]);

        return view('user.lists.new', [
            'listCategories' => $listCategories,
        ]);
    }

    public function store(NameListRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $list = (new CreateList(
            name: $validated['listname'],
            description: $validated['description'],
            isPublic: false,
            canBeModified: true,
            gender: $validated['gender'],
            categoryId: $validated['category'],
        ))->execute();

        Cache::forget('route-list-' . $list->id);
        Cache::forget('user-lists-' . auth()->id());

        return Redirect::route('list.show', [
            'liste' => $list->id,
        ]);
    }

    public function show(Request $request): View
    {
        $requestedList = $request->attributes->get('list');

        $details = Cache::remember('list-details-' . $requestedList->id, 604800, fn () => ListViewModel::show($requestedList));

        return view('user.lists.show', [
            'term' => null,
            'list' => $details,
            'search_items' => null,
        ]);
    }

    public function edit(Request $request): View
    {
        $listCategories = ListCategory::all()
            ->map(fn (ListCategory $listCategory) => [
                'id' => $listCategory->id,
                'name' => $listCategory->name,
            ]);

        return view('user.lists.edit', [
            'list' => ListViewModel::edit($request->attributes->get('list')),
            'listCategories' => $listCategories,
        ]);
    }

    public function update(NameListRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $list = $request->attributes->get('list');

        $list->name = $validated['listname'];
        $list->description = $validated['description'];
        $list->gender = $validated['gender'];
        if (auth()->user()->is_administrator) {
            $list->list_category_id = $validated['category'];
        }
        $list->save();

        Cache::forget('route-list-' . $list->id);
        Cache::forget('user-lists-' . auth()->id());
        Cache::forget('list-details-' . $list->id);

        return Redirect::route('list.show', [
            'liste' => $list->id,
        ])->withStatus('La liste a été mise à jour.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $list = $request->attributes->get('list');

        $list->delete();

        Cache::forget('route-list-' . $list->id);
        Cache::forget('user-lists-' . auth()->id());
        Cache::forget('list-details-' . $list->id);

        return Redirect::route('list.index');
    }

    public function delete(Request $request): View
    {
        return view('user.lists.destroy', [
            'list' => ListViewModel::delete($request->attributes->get('list')),
        ]);
    }
}
