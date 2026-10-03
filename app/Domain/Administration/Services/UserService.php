<?php

namespace App\Domain\Administration\Services;

use App\Domain\Administration\DTO\UserDTO;
use App\Domain\Administration\Models\User;
use App\Domain\Administration\Repositories\UserRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Logique métier des utilisateurs internes.
 */
class UserService
{
    public function __construct(
        private readonly UserRepository $repository
    ) {}

    public function creer(UserDTO $dto, ?UploadedFile $photo = null): User
    {
        return DB::transaction(function () use ($dto, $photo) {
            $data = $dto->toArrayForCreate();
            if ($photo) {
                $data['photo'] = $this->stockerPhoto($photo);
            }
            $user = $this->repository->create($data);
            $this->synchroniserProfils($user, $dto);

            return $user;
        });
    }

    public function mettreAJour(User $user, UserDTO $dto, ?UploadedFile $photo = null): User
    {
        return DB::transaction(function () use ($user, $dto, $photo) {
            $data = $dto->toArrayForUpdate();
            if ($photo) {
                if ($user->photo) {
                    Storage::disk('public')->delete($user->photo);
                }
                $data['photo'] = $this->stockerPhoto($photo);
            }
            $this->repository->update($user, $data);
            $this->synchroniserProfils($user, $dto);

            return $user->fresh(['roleRelation', 'profilsComplementaires']);
        });
    }

    public function desactiver(User $user): void
    {
        $this->repository->delete($user);
    }

    /** Le profil principal n'est jamais répété parmi les profils complémentaires. */
    protected function synchroniserProfils(User $user, UserDTO $dto): void
    {
        $user->profilsComplementaires()->sync(
            array_values(array_diff($dto->profils_complementaires, [$dto->role_id]))
        );
    }

    protected function stockerPhoto(UploadedFile $photo): string
    {
        return $photo->store('users/photos', 'public');
    }
}