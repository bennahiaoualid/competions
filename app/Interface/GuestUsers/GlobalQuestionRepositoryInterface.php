<?php

namespace App\Interface\GuestUsers;


use App\Models\GuestUsers\GlobalQuestion;

interface GlobalQuestionRepositoryInterface
{
    function all();
    function create(array $data);
    function approve(GlobalQuestion $question);
    function delete(GlobalQuestion $question);


}
