<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterService
{
    /**
     * Kategori bawaan supaya pengguna baru bisa langsung mencatat
     * tanpa harus menyiapkan kategori lebih dulu.
     *
     * @var array<string, array<int, string>>
     */
    private const DEFAULT_CATEGORIES = [
        TransactionType::Masuk->value => [
            'Penjualan',
            'Modal Masuk',
            'Pendapatan Lain',
        ],
        TransactionType::Keluar->value => [
            'Belanja Stok',
            'Gaji',
            'Sewa',
            'Listrik & Air',
            'Transportasi',
            'Operasional Lain',
        ],
    ];

    /**
     * Mendaftarkan usaha baru beserta pemiliknya.
     *
     * @param  array{business_name: string, name: string, username: string, email: ?string, password: string}  $data
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $tenant = Tenant::create([
                'name' => $data['business_name'],
            ]);

            $user = new User([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
            ]);
            $user->tenant_id = $tenant->id;
            $user->save();

            $this->seedDefaultCategories($tenant);

            return $user;
        });
    }

    private function seedDefaultCategories(Tenant $tenant): void
    {
        $now = now();
        $rows = [];

        foreach (self::DEFAULT_CATEGORIES as $type => $names) {
            foreach ($names as $name) {
                $rows[] = [
                    'tenant_id' => $tenant->id,
                    'name' => $name,
                    'type' => $type,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        Category::insert($rows);
    }
}
