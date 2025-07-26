<?php

namespace App\Interface\GuestUsers;

interface UserGuestRepositoryInterface
{
    public function findChoice($choiceId);
    public function createResponse(array $data);
    public function updateResponse($responseId, array $data);
    public function getLatestPendingResponse($questionId, $userId);
    public function getUserRespondedQuestionsPaginated($userId);
    public function findQuestion($questionId);
}
