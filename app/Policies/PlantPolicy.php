<?php

namespace App\Policies;

use App\Models\Plant;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlantPolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar tanaman.
     * Admin: ✅ | Pengamat: ✅
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail tanaman.
     * Admin: ✅ | Pengamat: ✅
     */
    public function view(User $user, Plant $plant): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna dapat menambah data tanaman.
     * Admin: ✅ | Pengamat: ❌
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Menentukan apakah pengguna dapat mengedit data tanaman.
     * Admin: ✅ | Pengamat: ❌
     */
    public function update(User $user, Plant $plant): bool
    {
        return $user->isAdmin();
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data tanaman.
     * Admin: ✅ | Pengamat: ❌
     */
    public function delete(User $user, Plant $plant): bool
    {
        return $user->isAdmin();
    }
}
