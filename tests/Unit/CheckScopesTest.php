<?php

namespace Laravel\Sanctum\Tests\Unit;

use Illuminate\Http\Request;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use Laravel\Sanctum\Contracts\HasApiTokens;
use Laravel\Sanctum\Http\Middleware\CheckScopes;
use PHPUnit\Framework\TestCase;

class CheckScopesTest extends TestCase
{
    use VerifiesDoubles;

    public function test_request_is_passed_along_if_scopes_are_present_on_token()
    {
        $middleware = new CheckScopes;
        $request = new Request;
        $user = Double::for(HasApiTokens::class);
        $request->setUserResolver(fn () => $user);
        $user->allows('currentAccessToken')->returns($token = Double::for(\stdClass::class));
        $user->allows('tokenCan')->with('foo')->returns(true);
        $user->allows('tokenCan')->with('bar')->returns(true);

        $response = $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');

        $this->assertSame('response', $response);
    }

    public function test_exception_is_thrown_if_token_doesnt_have_scope()
    {
        $this->expectException('Laravel\Sanctum\Exceptions\MissingScopeException');

        $middleware = new CheckScopes;
        $request = new Request;
        $user = Double::for(HasApiTokens::class);
        $request->setUserResolver(fn () => $user);
        $user->allows('currentAccessToken')->returns($token = Double::for(\stdClass::class));
        $user->allows('tokenCan')->with('foo')->returns(false);

        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }

    public function test_exception_is_thrown_if_no_authenticated_user()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');

        $middleware = new CheckScopes;
        $request = new Request;
        $request->setUserResolver(fn () => null);

        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }

    public function test_exception_is_thrown_if_no_token()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');

        $middleware = new CheckScopes;
        $request = new Request;
        $user = Double::for(HasApiTokens::class);
        $request->setUserResolver(fn () => $user);
        $user->allows('currentAccessToken')->returns(null);

        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }
}
