<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\KategoriMenuResource;
use App\Http\Resources\Api\MenuResource;
use App\Models\KategoriMenu;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MenuController extends Controller
{
    public function categories(): AnonymousResourceCollection
    {
        $kategoris = KategoriMenu::query()
            ->orderBy('nama_kategori')
            ->get();

        return KategoriMenuResource::collection($kategoris);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $menus = Menu::query()
            ->with([
                'kategoriMenu',
                'condiments' => fn ($query) => $query->where('status_ketersediaan', true),
            ])
            ->when(
                $request->integer('kategori_id'),
                fn ($query, int $kategoriId) => $query->where('kategori_menu_id', $kategoriId),
            )
            ->when(
                $request->string('search')->toString(),
                fn ($query, string $search) => $query->where('nama_menu', 'ilike', "%{$search}%"),
            )
            ->orderBy('kategori_menu_id')
            ->orderBy('nama_menu')
            ->get();

        return MenuResource::collection($menus);
    }
}
