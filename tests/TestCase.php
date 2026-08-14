<?php

namespace Tests;

use App\Models\User;
use App\Services\RegisterService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Mendaftarkan usaha lewat jalur yang sama dengan pengguna sungguhan,
     * sehingga tenant dan kategori bawaannya ikut terbentuk.
     */
    protected function registerBusiness(string $businessName, string $username): User
    {
        return app(RegisterService::class)->register([
            'business_name' => $businessName,
            'name' => $businessName,
            'username' => $username,
            'email' => null,
            'password' => 'rahasia123',
        ]);
    }
}
