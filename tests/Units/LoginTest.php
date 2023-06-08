<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginTest extends WebTestCase
{
    /**
     * @dataProvider provider
     */
    public function testLogin($username, $password, $expectedStatusCode)
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Sign in')->form([
            'name' => $username,
            'password' => $password,
        ]);

        $client->submit($form);
        $client->followRedirect();

        $response = $client->getResponse();
        $this->assertEquals($expectedStatusCode, $response->getStatusCode());

        if ($expectedStatusCode == 400) {
            $this->assertStringContainsString('Invalid credentials', $response->getContent());
        }
    }

    public static function provider(): array
    {
        return [
            ['valid_username', 'valid_password', 200], // log ok; pass ok
            ['valid_username', 'invalid_password', 400], // log ok; pass ko
            ['valid_username', null, 400], // log ok; pass null
            ['invalid_username', 'valid_password', 400], // log ko; pass ok
            ['invalid_username', 'invalid_password', 400], // log ko; pass ko
            ['invalid_username', null, 400], // log ko; pass null
            [null, 'valid_password', 400], // log null; pass ok
            [null, 'invalid_password', 400], // log null; pass ko
            [null, null, 400], // log null; pass null
        ];
    }
}