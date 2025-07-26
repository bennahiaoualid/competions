<?php

namespace Tests\Unit\Services\User;

use Mockery;
use Exception;
use Tests\TestCase;
use App\Models\User;
use App\Services\User\UserService;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Auth;
use App\Helpers\UserSafeDelete;

class UserServiceTest extends TestCase
{
    protected $flasher;
    protected $userService;
    protected static  $userModelMock;
    protected $userMock;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // Create the alias mock once for the entire test class
        static::$userModelMock = Mockery::mock('alias:' . User::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->flasher = Mockery::mock(FlasherInterface::class);
        
        $this->userService = new UserService(
            $this->flasher
        );

        $this->userMock = Mockery::mock('alias:' . User::class);

    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }


    public function test_creates_user_successfully()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'birth_day' => '1990-01-01',
            'gender' => 'male',
        ];

        // Mock Auth::id()
        Auth::shouldReceive('id')->once()->andReturn(1);

        $mergedData = array_merge($userData, ['admin_id' => 1]);

        // Mock User::create()
        
        static::$userModelMock->shouldReceive('create')
            ->once()
            ->with($mergedData)
            ->andReturn(new User());

        $this->flasher->shouldReceive('crudSuccess')
            ->once()
            ->with('saved');

        $result = $this->userService->create($userData);

        $this->assertTrue($result);
    }


    public function test_handles_user_creation_failure()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'birth_day' => '1990-01-01',
            'gender' => 'male',
        ];

        Auth::shouldReceive('id')->once()->andReturn(1);

        // Mock User::create() to throw exception
        static::$userModelMock->shouldReceive('create')
            ->once()
            ->andThrow(new Exception('Database error'));

        $this->flasher->shouldReceive('crudFailure')
            ->once()
            ->with('saved');

        $result = $this->userService->create($userData);

        $this->assertFalse($result);
    }

    public function test_updates_user_successfully()
    {
        $user = $this->userMock;
        $userData = [
            'name' => 'Jane Doe',
            'email' => 'jane_updated@example.com'
        ];

        $user->shouldReceive('save')
            ->once()
            ->andReturn(true);

        $this->flasher->shouldReceive('crudSuccess')
            ->once()
            ->with('updated');

        $result = $this->userService->update($user, $userData);

        $this->assertTrue($result);
    }

    public function test_handles_user_update_failure()
    {
        $user = $this->userMock;
        $userData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com'
        ];

        $user->shouldReceive('save')
            ->once()
            ->andThrow(new Exception('Update failed'));

        $this->flasher->shouldReceive('crudFailure')
            ->once()
            ->with('updated');

        $result = $this->userService->update($user, $userData);

        $this->assertFalse($result);
    }

    public function test_deletes_user_successfully()
    {
        $user = $this->userMock;
        $user->id = 1;

        // Mock UserSafeDelete helper
        $mockUserSafeDelete = Mockery::mock('alias:' . UserSafeDelete::class);
        $mockUserSafeDelete->shouldReceive('deleteUser')
            ->once()
            ->with(1);

        $user->shouldReceive('delete')
            ->once()
            ->andReturn(true);

        $this->flasher->shouldReceive('crudSuccess')
            ->once()
            ->with('deleted');

        $result = $this->userService->delete($user);

        $this->assertTrue($result);
    }

    public function test_handles_user_deletion_failure()
    {
        $user = $this->userMock;
        $user->id = 1;

        $mockUserSafeDelete = Mockery::mock('alias:' . UserSafeDelete::class);
        $mockUserSafeDelete->shouldReceive('deleteUser')
            ->once()
            ->with(1)
            ->andThrow(new Exception('Safe delete failed'));

        $this->flasher->shouldReceive('crudFailure')
            ->once()
            ->with('deleted');

        $result = $this->userService->delete($user);

        $this->assertFalse($result);
    }

}
