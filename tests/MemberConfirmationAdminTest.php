<?php

namespace Symbiote\MemberProfiles\Tests;

use SilverStripe\Security\Member;
use SilverStripe\ORM\DataObject;
use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Security\Group;
use SilverStripe\Forms\Form;
use SilverStripe\Control\Controller;
use SilverStripe\Control\RequestProcessor;
use SilverStripe\Control\Email\Email;
use SilverStripe\Dev\FunctionalTest;

/**
 * Tests manually confirming users in the admin panel.
 *
 * @package    silverstripe-memberprofiles
 * @subpackage tests
 */
class MemberConfirmationAdminTest extends FunctionalTest
{
    protected static $fixture_file = 'MemberConfirmationAdminTest.yml';
    
    protected function setUp(): void
    {
        parent::setUp();

        Email::config()->set('admin_email', 'no-reply@example.com');
    }

    public function testManualConfirmation()
    {
        $member = $this->objFromFixture(Member::class, 'unconfirmed');
        $this->assertEquals(true, (bool) $member->NeedsValidation);

        $this->getSecurityAdmin();
        $this->submitForm(
            'Form_ItemEditForm',
            'action_doSave',
            [
                'ManualEmailValidation' => 'confirm',
            ]
        );

        $member = DataObject::get_by_id(Member::class, $member->ID);
        $this->assertEquals(false, (bool) $member->NeedsValidation);
    }

    public function testResendConfirmationEmail()
    {
        $member = $this->objFromFixture(Member::class, 'unconfirmed');
        $this->assertEquals(true, (bool) $member->NeedsValidation);

        $this->getSecurityAdmin();
        $this->submitForm(
            'Form_ItemEditForm',
            'action_doSave',
            [
                'ManualEmailValidation' => 'resend',
            ]
        );

        $member = DataObject::get_by_id(Member::class, $member->ID);
        $this->assertEquals(true, (bool) $member->NeedsValidation);

        $this->assertEmailSent($member->Email);
    }

    private function getSecurityAdmin()
    {
        $member = $this->objFromFixture(Member::class, 'unconfirmed');
        $admin  = new SecurityAdmin();
        $group  = $this->objFromFixture(Group::class, 'group');

        // Form::disable_all_security_tokens(); // NOTE(Jake): Not in SS3 / shouldn't be testing with this anyway?
        $this->logInWithPermission('ADMIN');

        if (!class_exists(RequestProcessor::class)) {
            $gLink = Controller::join_links($admin->Link(), 'groups/EditForm/field/groups/item', $group->ID, 'edit');
            $mLink = Controller::join_links($admin->Link(), 'users/EditForm/field/users/item', $member->ID, 'edit');
        } else {
            $gLink = Controller::join_links($admin->Link(), 'show', $group->ID);
            $mLink = Controller::join_links($admin->Link(), 'EditForm/field/Members/item', $member->ID, 'edit');
        }

        $this->get($gLink);
        $this->get($mLink);
    }
}
