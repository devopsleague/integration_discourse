<?php
/**
 * Nextcloud - discourse
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2020
 */

namespace OCA\Discourse\Controller;

use OCP\App\IAppManager;
use OCP\Files\IAppData;
use OCP\AppFramework\Http\DataDisplayResponse;

use OCP\IURLGenerator;
use OCP\IConfig;
use OCP\IServerContainer;
use OCP\IL10N;
use OCP\ILogger;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\RedirectResponse;

use OCP\AppFramework\Http\ContentSecurityPolicy;

use OCP\IRequest;
use OCP\IDBConnection;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\Http\Client\IClientService;

require_once __DIR__ . '/../../vendor/autoload.php';
use phpseclib\Crypt\RSA;

class ConfigController extends Controller {


    private $userId;
    private $config;
    private $dbconnection;
    private $dbtype;

    public function __construct($AppName,
                                IRequest $request,
                                IServerContainer $serverContainer,
                                IConfig $config,
                                IAppManager $appManager,
                                IAppData $appData,
                                IDBConnection $dbconnection,
                                IURLGenerator $urlGenerator,
                                IL10N $l,
                                ILogger $logger,
                                IClientService $clientService,
                                $userId) {
        parent::__construct($AppName, $request);
        $this->l = $l;
        $this->userId = $userId;
        $this->appData = $appData;
        $this->serverContainer = $serverContainer;
        $this->config = $config;
        $this->dbconnection = $dbconnection;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
        $this->clientService = $clientService;
    }

    /**
     * set config values
     * @NoAdminRequired
     */
    public function setConfig($values) {
        foreach ($values as $key => $value) {
            $this->config->setUserValue($this->userId, 'discourse', $key, $value);
        }
        $response = new DataResponse(1);
        return $response;
    }

    /**
     * set admin config values
     */
    public function setAdminConfig($values) {
        foreach ($values as $key => $value) {
            $this->config->setAppValue('discourse', $key, $value);
        }
        $response = new DataResponse(1);
        return $response;
    }

    /**
     * receive oauth code and get oauth access token
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function oauthRedirect($payload, $nonce) {
        $configNonce = $this->config->getUserValue($this->userId, 'discourse', 'nonce', '');
        error_log('PAYLOAD '.$payload);
        error_log('CONFIGNONCE '.$configNonce);
        error_log('NONCE '.$nonce);
        // decrypt payload
        $privKey = $this->config->getAppValue('discourse', 'private_key', '');
        $rsa = new RSA();
        $rsa->loadKey($privKey);
        $rsadec = $rsa->decrypt($rsacipher);
        error_log('decrypt result : '.$rsadec);
        return '3333';
        $clientID = $this->config->getAppValue('discourse', 'client_id', '');
        $clientSecret = $this->config->getAppValue('discourse', 'client_secret', '');

        // anyway, reset nonce
        $this->config->setUserValue($this->userId, 'discourse', 'nonce', '');

        if ($clientID and $clientSecret and $configState !== '' and $configState === $state) {
            $redirect_uri = $this->urlGenerator->linkToRouteAbsolute('discourse.config.oauthRedirect');
            $discourseUrl = $this->config->getUserValue($this->userId, 'discourse', 'url', '');
            $result = $this->requestOAuthAccessToken($discourseUrl, [
                'client_id' => $clientID,
                'client_secret' => $clientSecret,
                'code' => $code,
                'redirect_uri' => $redirect_uri,
                'grant_type' => 'authorization_code'
            ], 'POST');
            if (is_array($result) and isset($result['access_token'])) {
                $accessToken = $result['access_token'];
                $this->config->setUserValue($this->userId, 'discourse', 'token', $accessToken);
                return new RedirectResponse(
                    $this->urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section' => 'linked-accounts']) .
                    '?discourseToken=success'
                );
            }
            $result = $this->l->t('Error getting OAuth access token');
        } else {
            $result = $this->l->t('Error during OAuth exchanges');
        }
        return new RedirectResponse(
            $this->urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section' => 'linked-accounts']) .
            '?discourseToken=error&message=' . urlencode($result)
        );
    }

    private function requestOAuthAccessToken($url, $params = [], $method = 'GET') {
        $client = $this->clientService->newClient();
        try {
            $url = $url . '/oauth/token';
            $options = [
                'headers' => [
                    'User-Agent'  => 'Nextcloud Discourse integration',
                ]
            ];

            if (count($params) > 0) {
                if ($method === 'GET') {
                    $paramsContent = http_build_query($params);
                    $url .= '?' . $paramsContent;
                } else {
                    $options['body'] = $params;
                }
            }

            if ($method === 'GET') {
                $response = $client->get($url, $options);
            } else if ($method === 'POST') {
                $response = $client->post($url, $options);
            } else if ($method === 'PUT') {
                $response = $client->put($url, $options);
            } else if ($method === 'DELETE') {
                $response = $client->delete($url, $options);
            }
            $body = $response->getBody();
            $respCode = $response->getStatusCode();

            if ($respCode >= 400) {
                return $this->l->t('OAuth access token refused');
            } else {
                return json_decode($body, true);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Discourse OAuth error : '.$e, array('app' => $this->appName));
            return $e;
        }
    }

}
