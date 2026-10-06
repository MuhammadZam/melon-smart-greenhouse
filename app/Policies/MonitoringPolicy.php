<?php

namespace App\Policies;

use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MonitoringPolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar monitoring.
     * Admin: ✅ | Pengamat: ✅
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail monitoring.
     * Admin: ✅ | Pengamat: ✅
     */
    public function view(User $user, Monitoring $monitoring): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna dapat menambah data monitoring.
     * Admin: ✅ | Pengamat: ✅
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna dapat mengedit/memperbarui data monitoring.
     * Admin: ✅ | Pengamat: ❌
     */
    public function update(User $user, Monitoring $monitoring): bool
    {
        return $user->isAdmin();
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data monitoring.
     * Admin: ✅ | Pengamat: ❌
     */
    public function delete(User $user, Monitoring $monitoring): bool
    {
        return $user->isAdmin();
    }
}
