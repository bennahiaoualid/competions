<?php

namespace App\Interface\Competition;

use App\Models\Competition\Question;

interface QuestionRepositoryInterface
{
    function all($level_id);
    function create(array $data);
    function update(Question $question, array $data);

}
