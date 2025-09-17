<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminLogin extends DuskTestCase
{


    #[\PHPUnit\Framework\Attributes\Test]
    public function SuccesfulAdminLogin(){
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->waitFor('input[name=email]')
                    ->type('email', 'admin@example.com')
                    ->type('password', 'ChangeMe!123')
                    ->press('Log in')
                    // wait for redirect
                    ->waitForLocation('/')
                    ->assertPathIs('/')
                    // wait for content to appear
                    ->waitForText('Admin Dashboard')
                    ->assertSee('Admin Dashboard')
                    ->assertSee('Hello, Admin')
                    //Closing test - Logging out
                    ->click('.sm\\:flex button') // escape special characters in Tailwind classes
                    ->clickLink('Log Out')
                    ->waitForLocation('/login')
                    ->assertPathIs('/login');
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function IncorrectAdminLogin(){
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('email', 'wrong@example.com')
                    ->type('password', 'wrongpassword')
                    ->press('Log in')
                    ->waitForText('These credentials do not match our records.')
                    ->assertSee('These credentials do not match our records.');
        });
    }






}
