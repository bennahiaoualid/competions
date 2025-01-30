<?php

namespace App\Services\GuestUsers;

use App\Repository\GuestUsers\UserGuestRepository;

class UserGuestService
{
    public function __construct(
        protected UserGuestRepository $userRepository
    ) {
    }

    public function welcome(): \Illuminate\Contracts\View\View
    {
        return $this->userRepository->welcome();
    }

    public function getRandomQuestion(): \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
    {
        return $this->userRepository->getRandomQuestion();
    }

    public function storeResponse(array $data): \Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse
    {
        return $this->userRepository->storeResponse($data);
    }

    public function globalUsersOrder(): \Illuminate\View\View
    {
        return $this->userRepository->globalUsersOrder();
    }


}
