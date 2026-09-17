<?php

namespace Laravel\Sanctum\Tests\Unit;

use JMac\Testing\Double;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Mockery;
use PHPUnit\Framework\TestCase;

class CheckForAnyAbilityTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    public function test_request_is_passed_along_if_abilities_are_present_on_token()
    {
        $middleware = new CheckForAnyAbility;
        $request = Double::for(\stdClass::class);
        $request->allows('user')->returns($user = Double::for(\stdClass::class));
        $user->allows('currentAccessToken')->returns($token = Double::for(\stdClass::class));
        $user->allows('tokenCan')->with('foo')->returns(true);
        $user->allows('tokenCan')->with('bar')->returns(false);

        $response = $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');

        $this->assertSame('response', $response);
    }

    public function test_exception_is_thrown_if_token_doesnt_have_ability()
    {
        $this->expectException('Laravel\Sanctum\Exceptions\MissingAbilityException');

        $middleware = new CheckForAnyAbility;
        $request = Double::for(\stdClass::class);
        $request->allows('user')->returns($user = Double::for(\stdClass::class));
        $user->allows('currentAccessToken')->returns($token = Double::for(\stdClass::class));
        $user->allows('tokenCan')->with('foo')->returns(false);
        $user->allows('tokenCan')->with('bar')->returns(false);

        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }

    public function test_exception_is_thrown_if_no_authenticated_user()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');

        $middleware = new CheckForAnyAbility;
        $request = Double::for(\stdClass::class);
        $request->expects('user')->returns(null);

        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }

    public function test_exception_is_thrown_if_no_token()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');

        $middleware = new CheckForAnyAbility;
        $request = Double::for(\stdClass::class);
        $request->allows('user')->returns($user = Double::for(\stdClass::class));
        $user->allows('currentAccessToken')->returns(null);

        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }
}
