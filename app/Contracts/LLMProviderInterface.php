<?php

namespace App\Contracts;

interface LLMProviderInterface
{
    public function makeRequest(string $endpoint, array $payload): array;
    public function supports(string $model): bool;
    public function getProviderName(): string;
    public function isAvailable(): bool;
    
    /**
     * Check real API connectivity and health
     */
    public function checkHealth(): array;
    
    /**
     * Get cached health status
     */
    public function getHealthStatus(): array;
    
    /**
     * Force refresh health status
     */
    public function refreshHealthStatus(): array;
} 