<?php

namespace Laravel\Sanctum\Tests\Unit;

use Illuminate\Http\Request;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use Laravel\Sanctum\Contracts\HasApiTokens;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use PHPUnit\Framework\TestCase;

class CheckForAnyAbilityTest extends TestCase
{
    use VerifiesDoubles;

    public function test_request_is_passed_along_if_abilities_are_present_on_token()
    {
        $middleware = new CheckForAnyAbility;
        $request = new Request;
        $user = Double::for(HasApiTokens::class);
        $request->setUserResolver(fn () => $user);
        $user->expects('currentAccessToken')->returns($token = Double::for(\stdClass::class));
        $user->expects('tokenCan')->with('foo')->returns(true);
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
        $request = new Request;
        $user = Double::for(HasApiTokens::class);
        $request->setUserResolver(fn () => $user);
        $user->expects('currentAccessToken')->returns($token = Double::for(\stdClass::class));
        $user->expects('tokenCan')->with('foo')->returns(false);
        $user->expects('tokenCan')->with('bar')->returns(false);

        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }

    public function test_exception_is_thrown_if_no_authenticated_user()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');

        $middleware = new CheckForAnyAbility;
        $request = new Request;
        $request->setUserResolver(fn () => null);

        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }

    public function test_exception_is_thrown_if_no_token()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');

        $middleware = new CheckForAnyAbility;
        $request = new Request;
        $user = Double::for(HasApiTokens::class);
        $request->setUserResolver(fn () => $user);
        $user->expects('currentAccessToken')->returns(null);

        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }
}
