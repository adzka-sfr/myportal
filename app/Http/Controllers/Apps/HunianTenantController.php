<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\HunianLog;
use App\Models\HunianTenant;
use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Informasi penghuni: profil, KTP, akun login, dan status aktif. */
class HunianTenantController extends Controller
{
    public const KTP_DIR = 'hunian/ktp';

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $tenants = HunianTenant::query()
            ->with(['activeOccupancy.room', 'reservation.room', 'user:id,email'])
            ->when($filters['q'] ?? null, function ($query, $term) {
                $like = '%' . addcslashes($term, '%_\\') . '%';
                $query->where(fn($q) => $q->where('full_name', 'like', $like)->orWhere('nik', 'like', $like)->orWhere('phone', 'like', $like));
            })
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('is_active', $status === 'active'))
            ->orderByDesc('is_active')->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        return view('portal.kelola_hunian.tenants', [
            'tenants' => $tenants,
            'filters' => $filters,
            'counts' => [
                'total' => HunianTenant::count(),
                'active' => HunianTenant::where('is_active', true)->count(),
                'with_account' => HunianTenant::whereNotNull('user_id')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $tenant = $this->withKtp($request, function (?string $ktp) use ($data) {
            $tenant = HunianTenant::create([
                'full_name' => $data['full_name'],
                'nik' => $data['nik'],
                'phone' => $data['phone'],
                'ktp_address' => $data['ktp_address'],
                'ktp_photo_path' => $ktp,
                'is_active' => $data['is_active'],
                'user_id' => $this->syncAccount(null, $data)?->id,
            ]);
            HunianLog::record('tenant_created', "Penghuni {$tenant->full_name} ditambahkan.", null, $tenant->full_name);

            return $tenant;
        });

        return redirect()->route('apps.hunian.tenants')->with('status', "Penghuni {$tenant->full_name} ditambahkan.");
    }

    public function update(Request $request, HunianTenant $tenant): RedirectResponse
    {
        $data = $this->validated($request, $tenant);
        if (! $data['is_active'] && $tenant->is_active && $this->hasOpenOccupancy($tenant)) {
            return back()->withErrors(['is_active' => 'Penghuni masih menghuni atau memesan kamar. Check-out / batalkan booking dulu.'])->withInput();
        }

        $oldPhoto = $tenant->ktp_photo_path;
        $this->withKtp($request, function (?string $ktp) use ($tenant, $data) {
            $tenant->update([
                'full_name' => $data['full_name'],
                'nik' => $data['nik'],
                'phone' => $data['phone'],
                'ktp_address' => $data['ktp_address'],
                'is_active' => $data['is_active'],
                'user_id' => $this->syncAccount($tenant, $data)?->id,
            ] + ($ktp ? ['ktp_photo_path' => $ktp] : []));
            HunianLog::record('tenant_updated', "Data penghuni {$tenant->full_name} diubah.", null, $tenant->full_name);
        });

        if ($request->hasFile('ktp_photo') && $oldPhoto) {
            Storage::disk('local')->delete($oldPhoto);
        }

        return redirect()->route('apps.hunian.tenants')->with('status', "Data {$tenant->full_name} diperbarui.");
    }

    public function toggleActive(HunianTenant $tenant): RedirectResponse
    {
        $activate = ! $tenant->is_active;
        if (! $activate && $this->hasOpenOccupancy($tenant)) {
            return back()->withErrors(['tenant' => 'Penghuni masih menghuni atau memesan kamar. Check-out / batalkan booking dulu.']);
        }

        DB::transaction(function () use ($tenant, $activate) {
            $tenant->update(['is_active' => $activate]);
            $tenant->user?->update(['is_active' => $activate]);
            HunianLog::record('tenant_updated', "Penghuni {$tenant->full_name} " . ($activate ? 'diaktifkan.' : 'dinonaktifkan.'), null, $tenant->full_name);
        });

        return back()->with('status', "{$tenant->full_name} " . ($activate ? 'diaktifkan.' : 'dinonaktifkan.'));
    }

    public function destroy(HunianTenant $tenant): RedirectResponse
    {
        if ($tenant->occupancies()->exists() || $tenant->tickets()->exists()) {
            return back()->withErrors(['tenant' => 'Penghuni sudah punya riwayat hunian atau keluhan. Nonaktifkan saja agar riwayat tetap tersimpan.']);
        }

        DB::transaction(function () use ($tenant) {
            HunianLog::record('tenant_deleted', "Penghuni {$tenant->full_name} dihapus.", null, $tenant->full_name);
            $tenant->delete();
            $tenant->user?->delete();
        });
        if ($tenant->ktp_photo_path) {
            Storage::disk('local')->delete($tenant->ktp_photo_path);
        }

        return redirect()->route('apps.hunian.tenants')->with('status', "Penghuni {$tenant->full_name} dihapus.");
    }

    public function ktp(HunianTenant $tenant): Response
    {
        abort_unless($tenant->ktp_photo_path && Storage::disk('local')->exists($tenant->ktp_photo_path), 404);

        return Storage::disk('local')->response($tenant->ktp_photo_path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function hasOpenOccupancy(HunianTenant $tenant): bool
    {
        return $tenant->occupancies()->whereIn('status', ['active', 'reserved'])->exists();
    }

    private function validated(Request $request, ?HunianTenant $tenant = null): array
    {
        $hasAccount = $tenant?->user_id !== null;
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'nik' => ['required', 'digits:16', Rule::unique('hunian_tenants', 'nik')->ignore($tenant?->id)],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'ktp_address' => ['required', 'string', 'max:500'],
            'ktp_photo' => [$tenant?->ktp_photo_path ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_active' => ['sometimes', 'boolean'],
            'email' => [
                Rule::requiredIf(fn() => ! $hasAccount && $request->filled('password')),
                'nullable',
                'email',
                'max:190',
                Rule::unique('users', 'email')->ignore($tenant?->user_id),
            ],
            'password' => [
                Rule::requiredIf(fn() => ! $hasAccount && $request->filled('email')),
                'nullable',
                'string',
                'min:12',
                'max:100',
            ],
            '_dialog' => ['nullable', 'string'],
            '_edit_id' => ['nullable', 'integer'],
        ], [
            'nik.digits' => 'NIK harus 16 digit angka.',
            'phone.regex' => 'Format nomor telepon tidak valid.',
        ], [
            'full_name' => 'nama lengkap',
            'ktp_address' => 'alamat sesuai KTP',
            'ktp_photo' => 'foto KTP',
            'phone' => 'nomor telepon',
            'password' => 'kata sandi',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    /** Buat atau perbarui akun login penghuni; mengembalikan user terkait (atau null bila tanpa akun). */
    private function syncAccount(?HunianTenant $tenant, array $data): ?User
    {
        $user = $tenant?->user;

        if ($user) {
            $user->name = $data['full_name'];
            $user->is_active = $data['is_active'];
            if (! empty($data['email'])) {
                $user->email = $data['email'];
            }
            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }
            $user->save();

            return $user;
        }

        if (empty($data['email'])) {
            return null;
        }

        return User::create([
            'name' => $data['full_name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_superadmin' => false,
            'is_active' => $data['is_active'],
        ]);
    }

    /** Simpan foto KTP (bila diunggah) lalu jalankan transaksi; foto dibersihkan jika transaksi gagal. */
    private function withKtp(Request $request, Closure $callback): mixed
    {
        $path = $request->file('ktp_photo')?->store(self::KTP_DIR, 'local');

        try {
            return DB::transaction(fn() => $callback($path));
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
    }
}
