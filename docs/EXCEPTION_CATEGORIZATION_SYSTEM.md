# Exception Categorization System for AI Question Generation

## Overview

This document outlines the comprehensive exception categorization system implemented for the AI Question Generation service. The system categorizes exceptions into 5 main categories to provide better error handling, logging, and user communication.

## Exception Categories

### 1. Question Generation Process Errors (Service-Specific)
**Cannot be generalized to other services**

| **Exception Type** | **Class** | **Location** | **User Message** | **Logging Level** |
|-------------------|-----------|--------------|------------------|-------------------|
| **Format & Content** | `QuestionGenerationProcessException::formatContentError()` | `parseLLMResponse()` | "Question generation failed due to format error" | ✅ Full LLM response + structure |
| **Data Storage** | `QuestionGenerationProcessException::dataStorageError()` | `storeDataInDb()` | "Failed to save generated question" | ✅ Database error + question data |

**Custom Exception Class:**
```php
App\Exceptions\AIQuestionGeneration\QuestionGenerationProcessException
```

### 2. Paid Service Errors (Generalizable)
**Can apply to other paid services**

| **Exception Type** | **Class** | **Location** | **User Message** | **Logging Level** |
|-------------------|-----------|--------------|------------------|-------------------|
| **Insufficient Balance** | `PaidServiceException::insufficientBalance()` | `GlobalQuestionGenerationService` | "Insufficient balance. Required: {cost}, Available: {balance}" | ✅ User ID, cost, balance |
| **Invalid Parameters** | `PaidServiceException::invalidParameters()` | `GlobalQuestionGenerationService` | "Invalid question parameters selected" | ✅ Parameters, user ID |

**Custom Exception Class:**
```php
App\Exceptions\AIQuestionGeneration\PaidServiceException
```

### 3. LLM Code Problems (LLM-Specific)
**Errors in LLM logic, parsing, configuration**

| **Exception Type** | **Class** | **Location** | **User Message** | **Logging Level** |
|-------------------|-----------|--------------|------------------|-------------------|
| **Model Not Supported** | `LLMCodeException::modelNotSupported()` | `BaseLLMService` | "Selected AI model is not available" | ✅ Model, provider |
| **Invalid Prompt** | `LLMCodeException::invalidPrompt()` | `buildPrompt()` | "Question generation configuration error" | ✅ Prompt parameters |
| **Response Processing** | `LLMCodeException::responseProcessingError()` | `processResponse()` | "AI service response format error" | ✅ Raw response |

**Custom Exception Class:**
```php
App\Exceptions\AIQuestionGeneration\LLMCodeException
```

### 4. LLM Connection Issues (Infrastructure)
**Network, API, external service problems**

| **Exception Type** | **Class** | **Location** | **User Message** | **Logging Level** |
|-------------------|-----------|--------------|------------------|-------------------|
| **API Key Missing** | `LLMConnectionException::apiKeyMissing()` | `GeminiProvider` | "AI service is temporarily unavailable" | ✅ Provider, config status |
| **Network Timeout** | `LLMConnectionException::networkTimeout()` | `makeRequest()` | "AI service is temporarily unavailable" | ✅ Endpoint, timeout value |
| **Rate Limiting** | `LLMConnectionException::rateLimiting()` | `makeRequest()` | "AI service is busy, please try again later" | ✅ Rate limit headers |
| **Server Error (5xx)** | `LLMConnectionException::serverError()` | `makeRequest()` | "AI service is experiencing issues" | ✅ Status code, response body |
| **Authentication Failed** | `LLMConnectionException::authenticationFailed()` | `makeRequest()` | "AI service is temporarily unavailable" | ✅ Status code, provider |
| **Connection Refused** | `LLMConnectionException::connectionRefused()` | `makeRequest()` | "AI service is temporarily unavailable" | ✅ Network error details |

**Custom Exception Class:**
```php
App\Exceptions\AIQuestionGeneration\LLMConnectionException
```

### 5. Global Errors (Universal)
**Can occur anywhere in the system**

| **Exception Type** | **Class** | **Location** | **User Message** | **Logging Level** |
|-------------------|-----------|--------------|------------------|-------------------|
| **Database Connection** | Standard Laravel Exceptions | `storeDataInDb()` | "Service temporarily unavailable" | ✅ General exception logging |
| **Queue System Failure** | Standard Laravel Exceptions | Job dispatch | "Service temporarily unavailable" | ✅ General exception logging |
| **Memory/Resource Limit** | Standard Laravel Exceptions | Job execution | "Service temporarily unavailable" | ✅ General exception logging |
| **Configuration Error** | Standard Laravel Exceptions | Service binding | "Service temporarily unavailable" | ✅ General exception logging |

## Implementation Details

### Exception Class Structure

All custom exception classes extend the base `Exception` class and include:

- **Error Type Constants**: Categorize the specific error
- **Context Array**: Store additional debugging information
- **User Message**: Pre-defined user-friendly error messages
- **Static Factory Methods**: Create exceptions with proper context

### Error Handling Flow

1. **Service Layer**: Catches `PaidServiceException` for business logic errors
2. **Job Layer**: Catches specific exception types for proper logging and user messaging
3. **Provider Layer**: Throws `LLMConnectionException` for infrastructure issues
4. **Base Service**: Throws `LLMCodeException` for LLM logic problems
5. **General Catch**: Handles any remaining exceptions as global errors

### Logging Strategy

- **Category 1 & 2**: Full context + user data + technical details
- **Category 3**: Technical details + LLM response + error context
- **Category 4**: **Full logging** (network details + API responses + error context)
- **Category 5**: **General exception logging** (standard Laravel logging)

### User Communication Strategy

- **Category 1**: Clear, specific error messages about question generation
- **Category 2**: Financial/parameter validation messages
- **Category 3**: Technical but user-friendly explanations
- **Category 4**: **Unified "AI service is temporarily unavailable" messages**
- **Category 5**: Generic "Service temporarily unavailable" messages

## Usage Examples

### Creating Exceptions

```php
// Question Generation Process Error
throw QuestionGenerationProcessException::formatContentError(
    'Failed to parse LLM response',
    ['content_preview' => substr($content, 0, 200)]
);

// Paid Service Error
throw PaidServiceException::insufficientBalance($required, $available, $userId);

// LLM Code Error
throw LLMCodeException::modelNotSupported($model, $provider);

// LLM Connection Error
throw LLMConnectionException::rateLimiting($statusCode, $headers);
```

### Catching and Handling

```php
try {
    // AI question generation logic
} catch (QuestionGenerationProcessException $e) {
    Log::error('Process failed', [
        'error_type' => $e->getErrorType(),
        'context' => $e->getContext()
    ]);
    // Handle process-specific errors
} catch (LLMConnectionException $e) {
    Log::error('Connection failed', [
        'error_type' => $e->getErrorType(),
        'user_message' => $e->getUserMessage()
    ]);
    // Use unified user message
} catch (\Exception $e) {
    Log::error('General error', ['error' => $e->getMessage()]);
    // Handle as global error
}
```

## Benefits

1. **Clear Error Categorization**: Easy to identify error types and sources
2. **Consistent User Experience**: Unified error messages for similar issues
3. **Comprehensive Logging**: Detailed logging for debugging and monitoring
4. **Maintainable Code**: Structured exception handling across the system
5. **Owner Notifications**: Prioritized alerts based on error categories
6. **Retry Logic**: Appropriate retry strategies for different error types

## Monitoring and Alerting

- **High Priority**: Categories 4 & 5 (infrastructure and global issues)
- **Medium Priority**: Category 3 (LLM logic problems)
- **Low Priority**: Categories 1 & 2 (business logic and user errors)

This system ensures that critical infrastructure issues are immediately flagged while maintaining appropriate logging levels for all error types. 