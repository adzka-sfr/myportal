<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\ShoppingItem;
use App\Models\ShoppingList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShoppingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $lists = $user->shoppingLists()->withCount('items')->orderBy('created_at')->get();
        if ($lists->isEmpty()) {
            $lists = collect([$user->shoppingLists()->create(['name' => 'Daftar belanja'])]);
        }
        $list = $request->filled('list')
            ? $user->shoppingLists()->with('items')->findOrFail($request->integer('list'))
            : $user->shoppingLists()->with('items')->oldest()->firstOrFail();

        return view('apps.shopping', compact('lists', 'list'));
    }

    public function addItem(Request $request): RedirectResponse
    {
        $data = $request->validate(['list_id' => ['required', 'integer'], 'name' => ['required', 'string', 'max:160'], 'quantity' => ['nullable', 'string', 'max:60']]);
        $list = $request->user()->shoppingLists()->findOrFail($data['list_id']);
        $list->items()->create(['name' => $data['name'], 'quantity' => $data['quantity'] ?? null]);

        return redirect()->route('apps.shopping', ['list' => $list->id])->with('status', 'Barang ditambahkan.');
    }

    public function toggleItem(Request $request, ShoppingItem $item): RedirectResponse
    {
        $this->ensureItemBelongsToUser($request, $item);
        $item->is_checked = ! $item->is_checked;
        $item->save();

        return back()->with('status', 'Status barang diperbarui.');
    }

    public function deleteItem(Request $request, ShoppingItem $item): RedirectResponse
    {
        $this->ensureItemBelongsToUser($request, $item);
        $item->delete();

        return back()->with('status', 'Barang dihapus dari daftar.');
    }

    public function createList(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $list = $request->user()->shoppingLists()->create(['name' => $data['name']]);

        return redirect()->route('apps.shopping', ['list' => $list->id])->with('status', 'Daftar baru dibuat.');
    }

    public function deleteList(Request $request, ShoppingList $list): RedirectResponse
    {
        abort_unless($list->user_id === $request->user()->id, 404);
        abort_if($request->user()->shoppingLists()->count() <= 1, 422, 'Sisakan minimal satu daftar belanja.');
        $next = $request->user()->shoppingLists()->whereKeyNot($list->id)->oldest()->firstOrFail();
        $list->delete();

        return redirect()->route('apps.shopping', ['list' => $next->id])->with('status', 'Daftar dihapus.');
    }

    private function ensureItemBelongsToUser(Request $request, ShoppingItem $item): void
    {
        abort_unless($item->shoppingList()->where('user_id', $request->user()->id)->exists(), 404);
    }
}
