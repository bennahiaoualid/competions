<?php

namespace Tests\Unit\Jobs\Ai;

use Mockery;
use Tests\TestCase;
use App\Models\User;
use App\Enums\AISubjectEnum;
use App\Enums\AIDifficultyEnum;
use App\Models\GuestUsers\Choice;
use App\Services\LLM\LLMResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use App\Contracts\LLMHandlerInterface;
use App\Jobs\Ai\GenerateAIQuestionJob;
use App\Services\LLM\LLMHandlerFactory;
use App\Models\GuestUsers\GlobalQuestion;
use App\Events\PaidServices\AIQuestionGenerated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Events\PaidServices\AIQuestionGenerationFailed;
use App\Exceptions\AIQuestionGeneration\LLMCodeException;
use App\Exceptions\AIQuestionGeneration\LLMConnectionException;
use App\Exceptions\AIQuestionGeneration\QuestionGenerationProcessException;

class GenerateAIQuestionJobTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected array $validParams;
    protected float $cost;

    /** @var LLMHandlerFactory&\Mockery\MockInterface */
    protected $mockLLMHandlerFactory;
    /** @var LLMHandlerInterface&\Mockery\MockInterface */
    protected $mockLLMHandlerInterface;
    /** @var LLMResponse&\Mockery\MockInterface */
    protected $mockResponse;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        $this->validParams = [
            'subject' => AISubjectEnum::MATHEMATICS->value,
            'difficulty' => AIDifficultyEnum::EASY->value,
            'choices_count' => 4,
            'max_tokens' => 350,
        ];
        
        $this->cost = 10.0;

        $this->mockLLMHandlerFactory = Mockery::mock(LLMHandlerFactory::class);
        $this->mockLLMHandlerInterface = Mockery::mock(LLMHandlerInterface::class);
        $this->mockResponse = Mockery::mock(LLMResponse::class);
        
        // Fake events to prevent them from being fired during tests
        Event::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_job_constructor_sets_properties_correctly()
    {
        $job = new GenerateAIQuestionJob($this->validParams, $this->cost, $this->user->id);
        
        $this->assertEquals($this->validParams, $job->getParams());
        $this->assertEquals($this->cost, $job->getCost());
        $this->assertEquals($this->user->id, $job->getUserId());
    }

    public function test_job_has_correct_timeout_and_tries()
    {
        $job = new GenerateAIQuestionJob($this->validParams, $this->cost, $this->user->id);
        
        $this->assertEquals(120, $job->timeout);
        $this->assertEquals(3, $job->tries);
    }

    public function test_job_handles_successful_ai_question_generation()
    {
        // Arrange
        $job = new GenerateAIQuestionJob($this->validParams, $this->cost, $this->user->id);
                
        // Mock successful LLM response
        $this->mockResponse->shouldReceive('isSuccess')->once()->andReturn(true);
        $this->mockResponse->shouldReceive('getContent')->once()->andReturn($this->getValidLLMResponse());
        
        $this->mockLLMHandlerFactory->shouldReceive('getDefault')->once()->andReturn($this->mockLLMHandlerInterface);
        $this->mockLLMHandlerInterface->shouldReceive('generate')->once()->andReturn($this->mockResponse);

        // Act
        $job->handle($this->mockLLMHandlerFactory);
        
        // Assert
        $this->assertDatabaseHas('global_questions', [
            'user_id' => $this->user->id,
            'ai' => true,
            'question_text' => 'What is 2 + 2?',
            'explanation' => '2 + 2 equals 4 because when you combine two groups of two items, you get four items total.',
            'duration' => 30,
        ]);
        
        $this->assertDatabaseHas('choices', [
            'choice_text' => '3',
            'correct' => false,
        ]);
        
        $this->assertDatabaseHas('choices', [
            'choice_text' => '4',
            'correct' => true,
        ]);
        
        // Verify success event was fired
        Event::assertDispatched(AIQuestionGenerated::class, function ($event) {
            return $event->userId === $this->user->id && $event->cost === $this->cost;
        });
    }

    public function test_job_handles_llm_generation_failure()
    {
        // Arrange
        $job = new GenerateAIQuestionJob($this->validParams, $this->cost, $this->user->id);
        
        // Mock failed LLM response
        $this->mockResponse->shouldReceive('isSuccess')->once()->andReturn(false);
        $this->mockResponse->shouldReceive('getError')->twice()->andReturn('LLM service unavailable');
        
        $this->mockLLMHandlerFactory->shouldReceive('getDefault')->once()->andReturn($this->mockLLMHandlerInterface);
        $this->mockLLMHandlerInterface->shouldReceive('generate')->once()->andReturn($this->mockResponse);
        
        // Act & Assert
        $this->expectExceptionObject(LLMCodeException::responseProcessingError('LLM service unavailable', ['response' => 'LLM service unavailable']));
        
        $job->handle($this->mockLLMHandlerFactory);
        
        // Verify failure event was fired
        Event::assertDispatched(AIQuestionGenerationFailed::class, function ($event) {
            return $event->userId === $this->user->id && 
                   $event->cost === $this->cost && 
                   $event->errorType === 'llm_code';
        });
    }

    public function test_job_handles_llm_connection_exception()
    {
        // Arrange
        $job = new GenerateAIQuestionJob($this->validParams, $this->cost, $this->user->id);
        
        $this->mockLLMHandlerInterface->shouldReceive('generate')->once()->andThrow(
            LLMConnectionException::networkTimeout('https://api.example.com', 30)
        );
        $this->mockLLMHandlerFactory->shouldReceive('getDefault')->once()->andReturn($this->mockLLMHandlerInterface);
        
        // Act & Assert
        $this->expectException(LLMConnectionException::class);
        
        $job->handle($this->mockLLMHandlerFactory);
        
        // Verify failure event was fired
        Event::assertDispatched(AIQuestionGenerationFailed::class, function ($event) {
            return $event->userId === $this->user->id && 
                   $event->cost === $this->cost && 
                   $event->errorType === 'llm_connection';
        });
    }

    public function test_job_handles_question_generation_process_exception()
    {
        // Arrange
        $job = new GenerateAIQuestionJob($this->validParams, $this->cost, $this->user->id);
        
        // Mock successful LLM response but with invalid content
        $this->mockResponse->shouldReceive('isSuccess')->once()->andReturn(true);
        $this->mockResponse->shouldReceive('getContent')->once()->andReturn('Invalid JSON content');
        
        $this->mockLLMHandlerFactory->shouldReceive('getDefault')->once()->andReturn($this->mockLLMHandlerInterface);
        $this->mockLLMHandlerInterface->shouldReceive('generate')->once()->andReturn($this->mockResponse);
        
        // Act & Assert
        $this->expectException(QuestionGenerationProcessException::class);
        
        $job->handle($this->mockLLMHandlerFactory);
        
        // Verify failure event was fired
        Event::assertDispatched(AIQuestionGenerationFailed::class, function ($event) {
            return $event->userId === $this->user->id && 
                   $event->cost === $this->cost && 
                   $event->errorType === 'question_generation_process';
        });
    }

    public function test_job_handles_general_exception()
    {
        // Arrange
        $job = new GenerateAIQuestionJob($this->validParams, $this->cost, $this->user->id);
        
        $this->mockLLMHandlerInterface->shouldReceive('generate')->once()->andThrow(new \Exception('Unexpected error'));
        $this->mockLLMHandlerFactory->shouldReceive('getDefault')->once()->andReturn($this->mockLLMHandlerInterface);
        
        // Act & Assert
        $this->expectException(\Exception::class);
        
        $job->handle($this->mockLLMHandlerFactory);
        
        // Verify failure event was fired
        Event::assertDispatched(AIQuestionGenerationFailed::class, function ($event) {
            return $event->userId === $this->user->id && 
                   $event->cost === $this->cost && 
                   $event->errorType === 'general_error';
        });
    }

    public function test_job_builds_correct_prompt()
    {
        $job = new GenerateAIQuestionJob($this->validParams, $this->cost, $this->user->id);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('buildPrompt');
        $method->setAccessible(true);
        
        $result = $method->invoke($job, $this->validParams);
        
        $this->assertArrayHasKey('prompt', $result);
        $this->assertArrayHasKey('max_tokens', $result);
        $this->assertArrayHasKey('temperature', $result);
        $this->assertArrayHasKey('top_p', $result);
        
        $this->assertEquals(350, $result['max_tokens']);
        $this->assertEquals(0.7, $result['temperature']);
        $this->assertEquals(0.9, $result['top_p']);
        
        // Verify prompt contains expected content
        $prompt = $result['prompt'];
        $this->assertStringContainsString(AISubjectEnum::MATHEMATICS->value, $prompt);
        $this->assertStringContainsString(AIDifficultyEnum::EASY->value, $prompt);
        $this->assertStringContainsString('4', $prompt); // choices_count
        $this->assertStringContainsString('JSON', $prompt);
    }

    public function test_job_parses_valid_llm_response()
    {
        $job = new GenerateAIQuestionJob($this->validParams, $this->cost, $this->user->id);
        
        $validResponse = $this->getValidLLMResponse();
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('parseLLMResponse');
        $method->setAccessible(true);
        
        $result = $method->invoke($job, $validResponse);
        
        $this->assertArrayHasKey('question', $result);
        $this->assertArrayHasKey('choices', $result);
        $this->assertArrayHasKey('correct_answer', $result);
        $this->assertArrayHasKey('explanation', $result);
        $this->assertArrayHasKey('duration', $result);
        
        $this->assertEquals('What is 2 + 2?', $result['question']);
        $this->assertEquals(['3', '4', '5', '6'], $result['choices']);
        $this->assertEquals('4', $result['correct_answer']);
        $this->assertEquals(30, $result['duration']);
    }

    public function test_job_parses_llm_response_with_markdown_code_blocks()
    {
        $job = new GenerateAIQuestionJob($this->validParams, $this->cost, $this->user->id);
        
        $responseWithMarkdown = "```json\n" . $this->getValidLLMResponse() . "\n```";
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('parseLLMResponse');
        $method->setAccessible(true);
        
        $result = $method->invoke($job, $responseWithMarkdown);
        
        $this->assertArrayHasKey('question', $result);
        $this->assertEquals('What is 2 + 2?', $result['question']);
    }

    public function test_job_handles_invalid_llm_response()
    {
        $job = new GenerateAIQuestionJob($this->validParams, $this->cost, $this->user->id);
        
        $invalidResponse = 'This is not valid JSON';
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('parseLLMResponse');
        $method->setAccessible(true);
        
        $this->expectException(QuestionGenerationProcessException::class);
        
        $method->invoke($job, $invalidResponse);
    }

    public function test_job_validates_parsed_data_correctly()
    {
        $job = new GenerateAIQuestionJob($this->validParams, $this->cost, $this->user->id);
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('validateParsedData');
        $method->setAccessible(true);
        
        // Valid data
        $validData = [
            'question' => 'Test question?',
            'choices' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            'explanation' => 'Test explanation',
            'duration' => 30,
        ];
        
        $this->assertTrue($method->invoke($job, $validData));
        
        // Invalid data - missing field
        $invalidData = [
            'question' => 'Test question?',
            'choices' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            // missing explanation and duration
        ];
        
        $this->assertFalse($method->invoke($job, $invalidData));
        
        // Invalid data - non-numeric duration
        $invalidData2 = [
            'question' => 'Test question?',
            'choices' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            'explanation' => 'Test explanation',
            'duration' => 'invalid',
        ];
        
        $this->assertFalse($method->invoke($job, $invalidData2));
    }

    /**
     * Helper method to generate valid LLM response JSON
     */
    private function getValidLLMResponse(): string
    {
        return json_encode([
            'question' => 'What is 2 + 2?',
            'choices' => ['3', '4', '5', '6'],
            'correct_answer' => '4',
            'explanation' => '2 + 2 equals 4 because when you combine two groups of two items, you get four items total.',
            'duration' => 30,
        ]);
    }
} 