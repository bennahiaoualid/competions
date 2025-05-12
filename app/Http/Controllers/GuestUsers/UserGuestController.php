<?php

namespace App\Http\Controllers\GuestUsers;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuestUsers\StoreResponseRequest;
use App\Repository\GuestUsers\UserGuestRepository;


class UserGuestController extends Controller
{

    public function __construct(
        protected UserGuestRepository $userGuestRepository
    ) {
    }

    public function index(): \Illuminate\Contracts\View\View
    {
        return $this->userGuestRepository->welcome();
    }

    public function getRandomQuestion(): \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
    {
        return $this->userGuestRepository->getRandomQuestion();
    }

    function storeResponse(StoreResponseRequest $request): \Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse
    {
        return $this->userGuestRepository->storeResponse($request->validated());
    }

    function globalUsersOrder() : \Illuminate\Contracts\View\View
    {
        return $this->userGuestRepository->globalUsersOrder();
    }

    function getGlobalUserResponse()
    {
        return $this->userGuestRepository->getGlobalUserResponse();
    }

}
