<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function getAllWithFilters(?array $filters = null): Collection;
    public function getActiveUsers(): Collection;
    public function getByRole(int $role): Collection;
    public function getAdmins(): Collection;
    public function findByLogin(string $login): ?User;
    public function findByEmail(string $email): ?User;
    public function canDelete(User $user): bool;
    public function deleteWithCheck(User $user): bool;
    public function getStats(): array;
    public function createWithValidation(array $data): User;
    public function updateWithValidation(User $user, array $data): User;
    public function changePassword(User $user, string $newPassword): User;
}
