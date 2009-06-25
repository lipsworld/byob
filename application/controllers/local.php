<?php
/**
 *
 */
class Local_Controller extends Layout_Controller
{

    public function __construct()
    {
        parent::__construct();

        // Set the global profile ID for log events during this request.
        Logevent_Model::setCurrentProfileID(
            AuthProfiles::get_profile('id')
        );
    }

    /**
     * Attempt to grab a repack based on router parameters.
     */
    protected function _getRequestedRepack()
    {
        $params = Router::get_params(array(
            'uuid'        => null,
            'screen_name' => null,
            'short_name'  => null,
            'status'      => null
        ));

        $m = ORM::factory('repack');

        if (!empty($params['screen_name'])) {
            // If a screen name is supplied, try finding the associated 
            // profile.  Dump out with a 404 if profile not found.
            $profile = ORM::factory('profile', $params['screen_name']);
            if (null == $profile) {
                Event::run('system.404');
                exit();
            }
            $m->where('profile_id', $profile->id);
        }

        // Handle status, if provided.
        if ('released' == $params['status'] || null === $params['status']) {
            $m->whereReleased(TRUE);
        } elseif ('unreleased' == $params['status']) {
            $m->whereReleased(FALSE);
        } else {
            $m->where('state', $params['status']);
        }

        if ($params['uuid']) {
            // Add the criteria for UUID.
            $m->where('uuid', $params['uuid']);
        } else if ($params['short_name']) {
            // Add the criteria for short name.
            $m->where('short_name', $params['short_name']);
        } else {
            // UUID or short name is required.
            Event::run('system.404');
            exit();
        }
         
        // Finally, look for the repack.
        $rp = $m->find();

        if (null === $rp) {
            // Bail out if not found.
            Event::run('system.404');
            exit();
        }

        return $rp;
    }


}