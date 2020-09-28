<?php
/**
 * Nextcloud - Discourse
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2020
 */

return [
    'routes' => [
        ['name' => 'config#oauthRedirect', 'url' => '/oauth-redirect', 'verb' => 'GET'],
        ['name' => 'config#oauthProtocolRedirect', 'url' => '/oauth-protocol-redirect', 'verb' => 'GET'],
        ['name' => 'config#setConfig', 'url' => '/config', 'verb' => 'PUT'],
        ['name' => 'discourseAPI#getDiscourseUrl', 'url' => '/url', 'verb' => 'GET'],
        ['name' => 'discourseAPI#getDiscourseUsername', 'url' => '/username', 'verb' => 'GET'],
        ['name' => 'discourseAPI#getNotifications', 'url' => '/notifications', 'verb' => 'GET'],
        ['name' => 'discourseAPI#getDiscourseAvatar', 'url' => '/avatar', 'verb' => 'GET'],
    ]
];
