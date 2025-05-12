<?php

namespace App\Interface\GuestUsers;

interface UserGuestRepositoryInterface
{
    public function welcome();
    public function getRandomQuestion();
    public function globalUsersOrder();
    public function getGlobalUserResponse();

}
