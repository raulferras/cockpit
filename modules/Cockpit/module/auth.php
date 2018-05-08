<?php

// Auth Api
$this->module("cockpit")->extend([

    "authenticate" => function($data) use($app) {

        $data = array_merge([
            "user"     => "",
            "email"    => "",
            "group"    => "",
            "password" => ""
        ], $data);

        if (!$data["password"]) return false;

        $user = $app->storage->findOne("cockpit/accounts", [
            "user"   => $data["user"],
            "active" => true
        ]);

        if (count($user) && password_verify($data["password"], $user["password"])) {

            $user = array_merge($data, (array)$user);

            unset($user["password"]);

            return $user;
        }

        return false;
    },

    "setUser" => function($user, $permanent = true) use($app) {

        if ($permanent) {
            $app("session")->write('cockpit.app.auth', $user);
        }

        $app['cockpit.auth.user'] = $user;
    },

    "getUser" => function($prop = null, $default = null) use($app) {

        $user = $app->retrieve('cockpit.auth.user');

        if (is_null($user)) {
            $user = $app("session")->read('cockpit.app.auth', null);
        }

        if (!is_null($prop)) {
            return $user && isset($user[$prop]) ? $user[$prop] : $default;
        }

        return $user;
    },

    "logout" => function() use($app) {
        $app("session")->delete('cockpit.app.auth');
    },

    "hasaccess" => function($resource, $action, $group = null) use($app) {

        if (!$group) {
            $user = $this->getUser();
            $group = isset($user["group"]) ? $user["group"] : null;
        }

        if ($group) {
            if ($app("acl")->hasaccess($group, $resource, $action)) return true;
        }

        return false;
    },

    "getGroup" => function() use($app) {

        $user = $this->getUser();

        if (isset($user["group"])) {
            return $user["group"];
        }

        return false;
    },

    "getGroupRights" => function($resource, $group = null) use($app) {

        if ($group) {
            return $app("acl")->getGroupRights($group, $resource);
        }

        $user = $this->getUser();

        if (isset($user["group"])) {
            return $app("acl")->getGroupRights($user["group"], $resource);
        }

        return false;
    },

    "isSuperAdmin" => function($group = null) use($app) {

        if (!$group) {

            $user = $this->getUser();

            if (isset($user["group"])) {
                $group = $user["group"];
            }
        }

        return $group ? $app("acl")->isSuperAdmin($group) : false;
    },

    "getGroups" => function() use($app) {

        $groups = array_merge(['admin'], array_keys($app->retrieve("config/groups", [])));

        return array_unique($groups);
    },

    "getGroupVar" => function($setting, $default = null) use($app) {

        if ($user = $this->getUser()) {

            if (isset($user['group']) && $user['group']) {

                return $app('acl')->getVar($user['group'], $setting, $default);
            }
        }

        return $default;
    },

    "userInGroup" => function($groups) use($app) {

        $user = $this->getUser();

        return (isset($user["group"]) && in_array($user["group"], (array)$groups));
    },

    "updateUserOption" => function($key, $value) use($app) {

        if ($user = $this->getUser()) {

            $data = isset($user['data']) && is_array($user['data']) ? $user['data'] : [];

            $data[$key] = $value;

            $app->storage->update('cockpit/accounts', ['_id' => $user['_id']], ['data' => $data]);

            return $value;
        }

        return false;
    }
]);

// ACL
$app('acl')->addResource('cockpit', [
    'backend', 'finder',
]);


// init acl groups + permissions + settings

$app('acl')->addGroup('admin', true);

/*
groups:
    author:
        $admin: false
        $vars:
            finder.path: /upload
        cockpit:
            backend: true
            finder: true

*/

$aclsettings = $app->retrieve('config/groups', []);

foreach ($aclsettings as $group => $settings) {

    $isSuperAdmin = $settings === true || (isset($settings['$admin']) && $settings['$admin']);
    $vars         = isset($settings['$vars']) ? $settings['$vars'] : [];

    $app('acl')->addGroup($group, $isSuperAdmin, $vars);

    if (!$isSuperAdmin && is_array($settings)) {

        foreach ($settings as $resource => $actions) {

            if ($resource == '$vars' || $resource == '$admin') continue;

            foreach ((array)$actions as $action => $allow) {
                if ($allow) {
                    $app('acl')->allow($group, $resource, $action);
                }
            }
        }
    }
}
