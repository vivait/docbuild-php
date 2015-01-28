<?php

namespace spec\Vivait\DocBuild\Auth;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Vivait\DocBuild\Exception\BadCredentialsException;
use Vivait\DocBuild\Exception\UnauthorizedException;
use Vivait\DocBuild\Http\HttpAdapter;

class AuthSpec extends ObjectBehavior
{
    function let(HttpAdapter $httpAdapter)
    {
        $httpAdapter->setUrl('http://doc.build/api/');
        $this->beConstructedWith($httpAdapter);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Vivait\DocBuild\Auth\Auth');
    }

    function it_errors_with_invalid_credentials(HttpAdapter $httpAdapter)
    {
        $response = ["error" => "invalid_client", "error_description" =>"The client credentials are invalid"];
        $httpAdapter->get('oauth/token', [
            'client_id' => 'myid',
            'client_secret' => 'anincorrectsecret',
            'grant_type' => 'client_credentials'
        ])->willThrow(new UnauthorizedException(json_encode($response)));

        $httpAdapter->getResponseCode()->willReturn(401);

        $this->shouldThrow(new UnauthorizedException(json_encode($response), 401))->duringAuthorize('myid', 'anincorrectsecret');
    }

    function it_can_authorize_the_client(HttpAdapter $httpAdapter)
    {
        $token = 'myapitoken1';
        $response = ['access_token' => $token, 'expires_in' => 3600, 'token_type' => 'bearer', 'scope' => ''];
        $httpAdapter->get('oauth/token', [
            'client_id' => 'myid',
            'client_secret' => 'somesecret',
            'grant_type' => 'client_credentials'
        ])->willReturn($response);

        $httpAdapter->getResponseCode()->willReturn(200);

        $this->authorize('myid', 'somesecret');
        $this->getAccessToken()->shouldEqual($token);
    }

    function it_throws_an_exception_if_no_credentials_are_set()
    {
        $this->shouldThrow(new BadCredentialsException())->duringAuthorize(null, null);
    }

}
