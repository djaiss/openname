<?php

namespace App\Http\Controllers;

use App\Http\ViewModels\Names\NameViewModel;
use App\Services\ToggleNameToFavorites;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function update(Request $request): View
    {
        $name = $request->attributes->get('name');

        $favorited = (new ToggleNameToFavorites(
            nameId: $name->id,
        ))->execute();

        return view('components.name-items', [
            'name' => NameViewModel::summary($name),
            'favorited' => $favorited,
        ]);
    }
}
