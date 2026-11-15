<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()->orderByDesc('is_superadmin')->orderBy('name')->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User(), 'applicationAccess' => [], 'featureAccess' => []]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, true);

        DB::transaction(function () use ($data): void {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_superadmin' => $data['is_superadmin'],
                'is_active' => $data['is_active'],
            ]);
            $this->syncPermissions($user, $data);
        });

        return redirect()->route('admin.users.index')->with('status', 'Akun pengguna berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        $applicationAccess = $user->applicationAccess()->pluck('application_slug')->all();
        $featureAccess = $user->featureAccess()->get()->mapWithKeys(fn ($row) => ["{$row->application_slug}.{$row->feature_slug}" => true])->all();

        return view('admin.users.form', compact('user', 'applicationAccess', 'featureAccess'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, false, $user);
        $actor = $request->user();

        if ($user->is($actor) && (! $data['is_superadmin'] || ! $data['is_active'])) {
            return back()->withErrors(['is_superadmin' => 'Kamu tidak dapat menonaktifkan atau mencabut akses Superadmin dari akunmu sendiri.'])->withInput();
        }

        if ($user->is_superadmin && (! $data['is_superadmin'] || ! $data['is_active']) && User::where('is_superadmin', true)->where('is_active', true)->count() <= 1) {
            return back()->withErrors(['is_superadmin' => 'Sistem harus memiliki minimal satu Superadmin aktif.'])->withInput();
        }

        DB::transaction(function () use ($data, $user): void {
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'is_superadmin' => $data['is_superadmin'],
                'is_active' => $data['is_active'],
            ]);
            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }
            $user->save();
            $this->syncPermissions($user, $data);
        });

        return redirect()->route('admin.users.index')->with('status', 'Pengguna dan hak akses berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Kamu tidak dapat menghapus akun yang sedang digunakan.']);
        }

        if ($user->is_superadmin && User::where('is_superadmin', true)->where('is_active', true)->count() <= 1) {
            return back()->withErrors(['user' => 'Superadmin aktif terakhir tidak dapat dihapus.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'Akun berhasil dihapus.');
    }

    private function validated(Request $request, bool $creating, ?User $user = null): array
    {
        $passwordRules = $creating ? ['required', 'string', 'min:12', 'confirmed'] : ['nullable', 'string', 'min:12', 'confirmed'];
        $apps = config('portal.applications');
        $featureRules = [];
        foreach ($apps as $app => $definition) {
            foreach (array_keys($definition['features']) as $feature) {
                $featureRules["features.{$app}.{$feature}"] = ['sometimes', 'boolean'];
            }
        }

        return $request->validate(array_merge([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => $passwordRules,
            'is_superadmin' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'applications' => ['sometimes', 'array'],
            'applications.*' => ['string', Rule::in(array_keys($apps))],
        ], $featureRules), [], [
            'is_superadmin' => 'hak Superadmin',
            'is_active' => 'status aktif',
        ]) + [
            'is_superadmin' => $request->boolean('is_superadmin'),
            'is_active' => $request->boolean('is_active'),
            'applications' => $request->input('applications', []),
            'features' => $request->input('features', []),
        ];
    }

    private function syncPermissions(User $user, array $data): void
    {
        $apps = $data['applications'] ?? [];
        $user->applicationAccess()->delete();
        $user->featureAccess()->delete();

        foreach ($apps as $application) {
            $user->applicationAccess()->create(['application_slug' => $application]);

            foreach (array_keys(config("portal.applications.{$application}.features", [])) as $feature) {
                if (($data['features'][$application][$feature] ?? false) === true || ($data['features'][$application][$feature] ?? false) === '1') {
                    $user->featureAccess()->create([
                        'application_slug' => $application,
                        'feature_slug' => $feature,
                    ]);
                }
            }
        }
    }
}
