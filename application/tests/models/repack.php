<?php
/**
 * Test class for repack model
 * 
 * @package    byob
 * @subpackage tests
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 * @group      byob
 * @group      models
 * @group      models.byob
 * @group      models.byob.repack
 */
class Repack_Test extends PHPUnit_Framework_TestCase 
{
    /**
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        LMO_Utils_EnvConfig::apply('testing');

        ORM::factory('logevent')->delete_all();
        ORM::factory('repack')->delete_all();
        ORM::factory('profile')->delete_all();
        ORM::factory('login')->delete_all();

        $this->login_1 = ORM::factory('login')->set(array(
            'login_name' => 'tester1',
            'email'      => 'tester1@example.com',
        ))->save();

        $this->profile_1 = ORM::factory('profile')->set(array(
            'screen_name' => 'tester1',
            'full_name'   => 'Tess T. Err',
            'org_name'    => 'Test Organization',
        ))->save();

        $this->profile_1->add($this->login_1);
        $this->profile_1->save();

        Logevent_Model::setCurrentProfileID($this->profile_1->id);

        $this->test_data_1 = array(
            'short_name'  => 'test-repack',
            'title'       => 'Test repack',
            'description' => 'This is my testing repack',

            'locales' => array('en-US','de'),

            'bookmarks_menu' => array(
                array(
                    'type'        => 'normal',
                    'title'       => 'foobar',
                    'description' => 'this is a foobar bookmark',
                    'location'    => 'http://example.com/foobar'
                ),
                array(
                    'type'        => 'normal',
                    'title'       => 'bazquux',
                    'description' => 'this is a bazquux bookmark',
                    'location'    => 'http://example.com/bazquux'
                ),
                array(
                    'type'        => 'live',
                    'title'       => 'xyzzy',
                    'location'    => 'http://example.com/xyzzy',
                    'feed'        => 'http://example.com/xyzzy/feed'
                ),
                array(
                    'type'        => 'live',
                    'title'       => 'hello',
                    'location'    => 'http://example.com/hello',
                    'feed'        => 'http://example.com/hello/feed'
                )
            ),

            'bookmarks_toolbar' => array(
                array(
                    'type'        => 'normal',
                    'title'       => 'foobar toolbar',
                    'description' => 'this is a foobar toolbar bookmark',
                    'location'    => 'http://example.com/foobar/toolbar'
                ),
                array(
                    'type'        => 'normal',
                    'title'       => 'bazquux toolbar',
                    'description' => 'this is a bazquux toolbar bookmark',
                    'location'    => 'http://example.com/bazquux/toolbar'
                ),
                array(
                    'type'        => 'live',
                    'title'       => 'xyzzy toolbar',
                    'location'    => 'http://example.com/xyzzy/toolbar',
                    'feed'        => 'http://example.com/xyzzy/toolbar/feed'
                ),
                array(
                    'type'        => 'live',
                    'title'       => 'hello toolbar',
                    'location'    => 'http://example.com/hello/toolbar',
                    'feed'        => 'http://example.com/hello/toolbar/feed'
                )
            ),
            
        );

    }

    /**
     * Simple warm-up test, ensures two new repacks have different UUIDs
     */
    public function testConstructorAssignsUuidIfNotGiven()
    {
        $r1 = ORM::factory('repack')->save();
        $r2 = ORM::factory('repack')->save();

        $this->assertNotNull($r1->uuid);
        $this->assertNotNull($r2->uuid);
        $this->assertTrue($r1->uuid != $r2->uuid);
    }

    /**
     * Exercise repack INI generation
     */
    public function testRepackCanProduceConfigIniRepresentation()
    {
        $r1 = ORM::factory('repack')
            ->set($this->test_data_1);
        $r1->profile_id = $this->profile_1->id;
        $r1->save();

        $ini_txt = $r1->buildConfigIni();
        $ini_fn = tempnam("tmp","test-");
        file_put_contents($ini_fn, $ini_txt);
        $conf = parse_ini_file($ini_fn, true);
        unlink($ini_fn);

        // TODO: Need to inspect the data and make some assertions.  This only 
        // asserts that it was parseable.
        $this->assertNotNull($conf);
    }

    /**
     * Exercise repack generation, up to the point of actually performing the 
     * repack.
     */
    public function testRepackCanGenerateBrowserRepack()
    {
        $r1 = ORM::factory('repack')->set($this->test_data_1);
        $r1->profile_id = $this->profile_1->id;
        $r1->save();
        
        $r1->processRepack(FALSE);

        $storage = Kohana::config('repacks.storage');
        $repack_dir = "$storage/{$r1->uuid}/{$r1->version}";

        $this->assertTrue(is_dir($storage));
        $this->assertTrue(is_dir($repack_dir));

        $ini_fn = "$repack_dir/xpi-config.ini";
        $this->assertFileExists($ini_fn);
        $conf = parse_ini_file($ini_fn, true);
        $this->assertNotNull($conf);

        $ini_fn = "$repack_dir/distribution.ini";
        $this->assertFileExists($ini_fn);
        $conf = parse_ini_file($ini_fn, true);
        $this->assertNotNull($conf);
    }

    /**
     * Exercise form validation and editing
     */
    public function testFormDataCanBeValidatedAndUsedToSetProperties()
    {
        $r1 = ORM::factory('repack')->set($this->test_data_1);
        $r1->profile = $this->profile_1;
        $r1->save();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $bad_data_1 = array();
        $is_valid_1 = $r1->validateRepack($bad_data_1);
        $this->assertTrue(!$is_valid_1);
        
        $bad_data_2 = array(
            'short_name' => 'x',
            'title' => ''
        );
        $is_valid_2 = $r1->validateRepack($bad_data_2);
        $this->assertTrue(!$is_valid_2);

        $good_data_1 = $good_data_2 = array(
            'short_name' => 'longenough',
            'title' => 'Good enough for a title',
            'description' => 'Not too long for a description'
        );

        $is_valid_3 = $r1->validateRepack($good_data_1, false);
        $this->assertTrue($is_valid_3);
        $this->assertTrue(
            $r1->short_name != $good_data_2['short_name'],
            "{$r1->short_name} shouldn't equal {$good_data_2['short_name']}"
        );

        $is_valid_4 = $r1->validateRepack($good_data_2);
        $this->assertTrue($is_valid_4);
        $this->assertEquals($r1->short_name, $good_data_2['short_name']);

        $this->test_data_1['created'] = gmdate('c');

        // Start creating form data by copying some test data fields straight 
        // over.
        $form_data = array(
            'short_name' => 'another-name'
        );
        $copy_fields = array(
            'title','description'
        );
        foreach($copy_fields as $field) {
            $form_data[$field] = $this->test_data_1[$field];
        }

        // Convert test data bookmarks into form data.
        foreach(array('menu','toolbar') as $kind) {
            $bookmarks = $this->test_data_1["bookmarks_{$kind}"];
            foreach(array('type','title','description','location','feed') as $field) {
                $data = array();
                foreach ($bookmarks as $bookmark) {
                    if (isset($bookmark[$field])) {
                        $data[] = $bookmark[$field];
                    }
                }
                $form_data["bookmarks_{$kind}_{$field}"] = $data;
            }
        }

        $r1 = ORM::factory('repack')->set($this->test_data_1)->save();

        $r2 = ORM::factory('repack')->set()->save();
        $is_valid = $r2->validateRepack($form_data);
        $this->assertTrue($is_valid, 'Form should be valid. Errors: ' . 
            var_export($form_data->errors(), true));
        $r2->save();

        $r1_meta = arr::extract($r1->as_array(), 'title', 'description');
        $r2_meta = arr::extract($r2->as_array(), 'title', 'description');
        $this->assertEquals(
            $r1_meta, $r2_meta,
            "Test data and form data repacks should have the same metadata"
        );

        $this->assertEquals(
            $r1->bookmarks_menu, $r2->bookmarks_menu,
            "Bookmarks menu contents should match"
        );

        $this->assertEquals(
            $r1->bookmarks_toolbar, $r2->bookmarks_toolbar,
            "Bookmarks toolbar contents should match"
        );

    }

    /**
     * Work through a series of repack workflow steps, verify restrictions and 
     * log events
     */
    public function testWorkflowAndLogEvents()
    {
        $expected_log_events = 0;

        // First try creating and editing a repack.

        $r1 = ORM::factory('repack')->set($this->test_data_1)->save();

        $this->assertRepackState($r1, 'new');
        $this->assertLatestLog(++$expected_log_events, $r1->uuid, 'created');

        $r1->title = "edited title";
        $r1->save();

        $this->assertRepackState($r1, 'edited');
        $this->assertLatestLog(++$expected_log_events, $r1->uuid, 'modified');

        // Next, try requesting a release and then try cancelling it before the 
        // build process starts.

        $r1->requestRelease('PLEASE APPROVE ME~!');

        $this->assertTrue($r1->isLockedForChanges(), 
            "Repack pending approval should be locked for changes");
        $this->assertRepackState($r1, 'requested');
        $this->assertLatestLog(++$expected_log_events, $r1->uuid, 
            'requestRelease', 'PLEASE APPROVE ME~!');

        $this->assertException(array($r1, 'requestRelease'),
            'Requesting a release twice should fail');

        $r1->cancelRelease("Oops, sorry.");

        $this->assertTrue(!$r1->isLockedForChanges(),
            "Repack after release request cancelled should be writable again");
        $this->assertRepackState($r1, 'cancelled');
        $this->assertLatestLog(++$expected_log_events, $r1->uuid, 
            'cancelRelease', 'Oops, sorry.');

        // Incidentally, a few other actions should fail on a repack not 
        // pending review...

        $this->assertException(array($r1, 'cancelRelease'),
            'cancelRelease should fail');
        $this->assertException(array($r1, 'approveRelease'),
            'approveRelease should fail');
        $this->assertException(array($r1, 'rejectRelease'),
            'rejectRelease should fail');

        // Now, try requesting a release and let the build process start, 
        // then try cancelling.  That should fail.

        $r1->requestRelease("Try this one!");
        $this->assertRepackState($r1, 'requested');
        $this->assertLatestLog(++$expected_log_events, $r1->uuid, 
            'requestRelease', 'Try this one!');

        $r1->beginRelease("Starting release process");
        $this->assertRepackState($r1, 'started');
        $this->assertLatestLog(++$expected_log_events, $r1->uuid, 
            'beginRelease', 'Starting release process');

        $this->assertTrue($r1->isLockedForChanges(),
            "Repacks in the build process should be locked");

        $this->assertException(array($r1, 'cancelRelease'),
            'cancelRelease should fail');

        // Finish the process, then cancel. Should succeed.
        $r1->finishRelease("Build process completed");
        $r1->cancelRelease("Whoops, too early.");
        $expected_log_events += 2;

        // Next, restart the build process and reject it.
        $r1->requestRelease("Try this one!");
        $r1->beginRelease("Starting release process");
        $expected_log_events += 2;

        $r1->finishRelease("Build process completed");
        $this->assertRepackState($r1, 'pending');
        $this->assertLatestLog(++$expected_log_events, $r1->uuid,
            'finishRelease', 'Build process completed');

        $this->assertTrue($r1->isLockedForChanges(),
            "Repacks pending approval should be locked");

        $r1->rejectRelease("Is this a joke?");
        $this->assertRepackState($r1, 'rejected');
        $this->assertLatestLog(++$expected_log_events, $r1->uuid, 
            'rejectRelease', "Is this a joke?");

        $this->assertTrue(!$r1->isLockedForChanges(),
            "Repack after rejection should be writable again");

        // Try running through the build process, but fail the build.
        
        $r1->requestRelease("Seriously, check it out");
        $r1->beginRelease("Starting release process");
        $expected_log_events += 2; // Skip some log events.

        $r1->failRelease("Solar flares prevented build completion");
        $this->assertRepackState($r1, 'failed');
        $this->assertLatestLog(++$expected_log_events, $r1->uuid, 
            'failRelease', 'Solar flares prevented build completion');

        $this->assertTrue(!$r1->isLockedForChanges(),
            "Repack after build failure should be writable again");

        // Okay, try requesting release again and approve it this time.

        $r1->requestRelease("Seriously, check it out");
        $r1->beginRelease("Starting release process");
        $r1->finishRelease("Build process completed");
        $expected_log_events += 3; // Skip some log events.

        $r1->approveRelease("Okay fine, you win.");
        $this->assertRepackState($r1, 'released');
        $this->assertLatestLog(++$expected_log_events, $r1->uuid, 
            'approveRelease', 'Okay fine, you win.');

        $this->assertTrue($r1->isLockedForChanges(),
            "Releases should be locked for changes");

        // Revert the release.

        $rr = $r1->revertRelease('This browser is awful, on second thought');
        $this->assertRepackState($r1, 'reverted');
        $this->assertLatestLog(++$expected_log_events, $r1->uuid, 
            'revertRelease', 'This browser is awful, on second thought');

        $this->assertEquals($rr->id, $r1->id,
            'Release revert with no changes should result in original release'
        );

        // Run through another release, start an edit, then revert the release.
        // This should result in the edit surviving, and the release deleted.

        $r1->requestRelease("Try this for a change.");
        $r1->beginRelease("Starting release process");
        $r1->finishRelease("Build process completed");
        $r1->approveRelease("Okay, that's better.");

        $r2 = $r1->findEditable();
        $r2->title = "New title";
        $r2->save();
        
        $expected_log_events += 5; // Skip this run of log messages

        $old_id = $r1->id;
        $rr = $r1->revertRelease('Wait, nope, still crap');
        $this->assertRepackState($rr, 'edited');
        $this->assertLatestLog(++$expected_log_events, $rr->uuid, 
            'revertRelease', 'Wait, nope, still crap');

        $this->assertEquals($rr->id, $r2->id,
            'Revert with pending changes should result in pending changes surviving'
        );

        $rq = ORM::factory('repack', $old_id);
        $this->assertTrue(
            !$rq->loaded,
            'Revert with pending changes should result in pending changes surviving'
        );

    }


    public function assertException($callback, $message, $params=null, $message_expected=null)
    {
        try {
            if ($params) {
                call_user_func_array($callback, $params);
            } else {
                call_user_func($callback);
            }
            $failed = false;
        } catch (Exception $e) {
            $failed = true;
        }
        $this->assertTrue($failed, $message);
    }

    public function assertRepackState($repack, $state_name, $message=null)
    {
        $state = Repack_Model::$states[$state_name];
        if (null===$message) {
            $message = 
                "Expected state {$state_name} ($state) state - " .
                "was {$repack->getStateName()} ({$repack->state})";
        }
        $this->assertEquals($state, $repack->state, $message);
    }

    public function assertLatestLog($count, $uuid, $action, $details=null)
    {
        $events = ORM::factory('logevent')->findByUUID($uuid);
        $this->assertEquals($count, $events->count(), 
            "Expect {$count} log event(s).");
        $this->assertEquals($action, $events[0]->action);
        if (null !== $details) {
            $this->assertEquals($details, $events[0]->details);
        }
    }

}
