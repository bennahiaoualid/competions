<?php

namespace App\Interface\GuestUsers;

interface UserGuestRepositoryInterface
{
    public function findChoice($choiceId);
    public function createResponse(array $data);
    public function getLatestPendingResponse($questionId, $userId);
    public function getUserRespondedQuestionsPaginated($userId);
    public function findQuestion($questionId);
    public function getRandomEligibleQuestionForUser($userId);
    public function getRandomAIQuestionForUser($userId, $questionId = null);
    public function getRandomPremiumQuestionForUser($userId);
    public function getEligibleAIQuestionCountForUser(int $userId): int;
}
