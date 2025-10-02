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
                    ->click('@user-menu-button') 
                    ->clickLink('Log Out')
                    ->waitForLocation('/login')
                    ->assertPathIs('/login');

        });
    }

#[\PHPUnit\Framework\Attributes\Test]
public function EditMember()
{
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->waitFor('input[name=email]')
            ->type('email', 'admin@example.com')
            ->type('password', 'ChangeMe!123')
            ->press('Log in')
            ->waitForLocation('/')
            ->waitForText('Admin Dashboard')
            ->clickLink('Members')
            ->waitForLocation('/system/members')
            ->assertSee('John')
            ->assertSee('Doe')
            // Click Edit button for John Doe
            ->click('@edit-member')
            // Wait for modal to show
            ->pause(2000)
            ->type('@edit-first-name', 'Johnny') // if no dusk attr, fallback to input[name=editFirstName]
            ->type('@edit-bio', 'Updated bio for Johnny Doe.')
            ->press('Save')
            ->pause(3000)
            ->assertSee('Johnny')
            ->assertSee('Updated bio for Johnny Doe.')
            // logout
            ->click('@user-menu-button')
            ->clickLink('Log Out')
            ->waitForLocation('/login')
            ->assertPathIs('/login');
    });
}

#[\PHPUnit\Framework\Attributes\Test]
public function DeleteMember()
{
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->waitFor('input[name=email]')
            ->type('email', 'admin@example.com')
            ->type('password', 'ChangeMe!123')
            ->press('Log in')
            ->waitForLocation('/')
            ->waitForText('Admin Dashboard')
            ->clickLink('Members')
            ->waitForLocation('/system/members')
            ->assertSee('Johnny')
            // Click Delete button for Johnny Doe
            ->click('@delete-member')
            ->driver->switchTo()->alert()->accept(); // confirm JS dialog
 
        $browser->pause(3000)
            ->assertDontSee('Johnny')
            ->assertDontSee('john.doe@example.com')
            // logout
            ->click('@user-menu-button')
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
                    ->type('name', 'test')
                    ->type('email', 'test@test.com')
                    ->type('password', 'testtest123')
                    ->press('Create Voting Manager')
                    ->pause(5000)
                    ->assertSee('Voting Manager test@test.com created.')
                    // Validate the new manager shows in table
                    ->assertSee('test')
                    ->assertSee('test@test.com')
                    //Logging out
                    ->click('.sm\\:flex button') 
                    ->clickLink('Log Out')
                    ->waitForLocation('/login')
                    ->assertPathIs('/login')
                    //re login with new account
                    ->type('email', 'test@test.com')
                    ->type('password', 'testtest123')
                    ->press('Log in')
                    // wait for redirect
                    ->waitForLocation('/')
                    ->assertPathIs('/')
                    ->waitForText('Admin Dashboard')
                    ->assertSee('Admin Dashboard')
                    ->assertSee('Hello, test');
        });
    }


//     #[\PHPUnit\Framework\Attributes\Test]
// public function EditVotingManager()
// {
//     $this->browse(function (Browser $browser) {
//         $browser->visit('/login')
//             ->waitFor('input[name=email]')
//             ->type('email', 'admin@example.com')
//             ->type('password', 'ChangeMe!123')
//             ->press('Log in')
//             ->waitForLocation('/')
//             ->assertPathIs('/')
//             ->waitForText('Admin Dashboard')
//             ->clickLink('Voting Manager')
//             ->waitForLocation('/system/voting-managers')
//             ->assertPathIs('/system/voting-managers')
//             ->waitForText('Voting Managers')
//             // Click Edit button for TestManager
//             ->click('@edit-vm-1') // add dusk="edit-vm-1" to the edit button for this manager
//             ->pause(1000)
//             ->type('@edit-vm-name', 'UpdatedManager')
//             ->type('@edit-vm-email', 'updatedmanager@test.com')
//             ->click('@save-vm') // add dusk="save-vm" to "Save Changes" button
//             ->pause(2000)
//             ->assertSee('UpdatedManager')
//             ->assertSee('updatedmanager@test.com');
//     });
// }

// #[\PHPUnit\Framework\Attributes\Test]
// public function DeleteVotingManager()
// {
//     $this->browse(function (Browser $browser) {
//         $browser->visit('/login')
//             ->waitFor('input[name=email]')
//             ->type('email', 'admin@example.com')
//             ->type('password', 'ChangeMe!123')
//             ->press('Log in')
//             ->waitForLocation('/')
//             ->assertPathIs('/')
//             ->waitForText('Admin Dashboard')
//             ->clickLink('Voting Manager')
//             ->waitForLocation('/system/voting-managers')
//             ->assertPathIs('/system/voting-managers')
//             ->waitForText('Voting Managers')
//             // Click Delete button for UpdatedManager
//             ->click('@delete-vm-1') // add dusk="delete-vm-1" to the delete button
//             ->pause(500)
//             ->click('@confirm-delete-vm') // add dusk="confirm-delete-vm" to "Delete" button in modal
//             ->pause(2000)
//             ->assertDontSee('UpdatedManager')
//             ->assertDontSee('updatedmanager@test.com');
//     });
// }

    






}
