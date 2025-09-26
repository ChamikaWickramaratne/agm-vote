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
                    ->click('.sm\\:flex button') 
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




    #[\PHPUnit\Framework\Attributes\Test]
    public function CreateMember(){
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
                    // Navigate to Members page
                    ->clickLink('Members') 
                    ->waitForLocation('/system/members')
                    ->assertPathIs('/system/members')
                    ->waitForText('Members')
                    // Fill the form
                    ->select('title', 'Mr.')
                    ->type('first_name', 'John')
                    ->type('last_name', 'Doe')
                    ->type('email', 'john.doe@example.com')
                    ->type('bio', 'This is a test member.')
                    ->attach('photoUpload', __DIR__.'/test-photo.jpg') 
                    ->press('✅ Create')
                    ->pause(5000)
                    ->assertSee('John')
                    ->assertSee('Doe')
                    ->assertSee('john.doe@example.com')
                    //Closing test - Logging out
                    ->click('.sm\\:flex button') 
                    ->clickLink('Log Out')
                    ->waitForLocation('/login')
                    ->assertPathIs('/login');

        });
    }

    
    #[\PHPUnit\Framework\Attributes\Test]
    public function CraeteVotingManager(){
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
                     // Navigate to Members page
                    ->clickLink('Voting Manager')
                    ->waitForLocation('/system/voting-managers')
                    ->assertPathIs('/system/voting-managers')
                    ->waitForText('Voting Managers')
                    // Fill in Voting Manager form
                    ->type('name', 'TestManager')
                    ->type('email', 'TestManager@TestManager.com')
                    ->type('password', 'TestManager123')
                    ->press('Create Voting Manager')
                    ->pause(5000)
                    ->assertSee('created.')
                    // Validate the new manager shows in table
                    ->assertSee('TestManager')
                    ->assertSee('TestManager@TestManager.com')
                    //Logging out
                    ->click('.sm\\:flex button') 
                    ->clickLink('Log Out')
                    ->waitForLocation('/login')
                    ->assertPathIs('/login')
                    //re login with new account
                    ->type('email', 'TestManager@TestManager.com')
                    ->type('password', 'TestManager123')
                    ->press('Log in')
                    // wait for redirect
                    ->waitForLocation('/')
                    ->assertPathIs('/')
                    ->waitForText('Admin Dashboard')
                    ->assertSee('Admin Dashboard')
                    ->assertSee('Hello, Admin');
        });
    }
    






}
