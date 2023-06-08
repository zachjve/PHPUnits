<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginRedirectTest extends WebTestCase
{
    /**
     * @dataProvider provider
     */
    public function testLogin($username, $password, $expectedStatusCode, $expectedRedirectUrl = null)
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            'name' => $username,
            'password' => $password,
        ]);

        $client->submit($form);

        $response = $client->getResponse();
        $this->assertEquals($expectedStatusCode, $response->getStatusCode());

        if ($expectedRedirectUrl) {
            $this->assertTrue($response->isRedirect($expectedRedirectUrl));
            
            // Follow the redirection and check that we land on the expected page
            $crawler = $client->followRedirect();
            $this->assertStringContainsString($expectedRedirectUrl, $crawler->getUri());
        }              
    }

    public static function provider(): array
    {
        return [
            ['valid_username', 'valid_password', 302, '/home'], // log ok; pass ok
            ['valid_username', 'invalid_password', 302, '/login'], // log ok; pass ko
            ['valid_username', null, 302, '/login'], // log ok; pass null
            ['invalid_username', 'valid_password', 302, '/login'], // log ko; pass ok
            ['invalid_username', 'invalid_password', 302, '/login'], // log ko; pass ko
            ['invalid_username', null, 302, '/login'], // log ko; pass null
            [null, 'valid_password', 302, '/login'], // log null; pass ok
            [null, 'invalid_password', 302, '/login'], // log null; pass ko
            [null, null, 302, '/login'], // log null; pass null
        ];
    }
}

