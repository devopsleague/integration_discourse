<?php
/**
 * Nextcloud - Discourse
 *
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2020
 */

namespace OCA\Discourse\AppInfo;

use Closure;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;

use OCA\Discourse\Dashboard\DiscourseWidget;
use OCA\Discourse\Search\DiscourseSearchTopicsProvider;
use OCA\Discourse\Search\DiscourseSearchPostsProvider;
use OCP\IConfig;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Util;

/**
 * Class Application
 *
 * @package OCA\Discourse\AppInfo
 */
class Application extends App implements IBootstrap {

	public const APP_ID = 'integration_discourse';
	/**
	 * @var mixed
	 */
	private $config;

	/**
	 * Constructor
	 *
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);

		$container = $this->getContainer();
		$this->config = $container->get(IConfig::class);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerDashboardWidget(DiscourseWidget::class);
		$context->registerSearchProvider(DiscourseSearchPostsProvider::class);
		$context->registerSearchProvider(DiscourseSearchTopicsProvider::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(Closure::fromCallable([$this, 'registerNavigation']));
		Util::addStyle(self::APP_ID, 'discourse-search');
	}

	public function registerNavigation(IUserSession $userSession): void {
		$user = $userSession->getUser();
		if ($user !== null) {
			$userId = $user->getUID();
			$container = $this->getContainer();

			if ($this->config->getUserValue($userId, self::APP_ID, 'navigation_enabled', '0') === '1') {
				$discourseUrl = $this->config->getUserValue($userId, self::APP_ID, 'url', '');
				if ($discourseUrl === '') {
					return;
				}
				$container->get(INavigationManager::class)->add(function () use ($container, $discourseUrl) {
					$urlGenerator = $container->get(IURLGenerator::class);
					$l10n = $container->get(IL10N::class);
					return [
						'id' => self::APP_ID,
						'order' => 10,
						'href' => $discourseUrl,
						'icon' => $urlGenerator->imagePath(self::APP_ID, 'app.svg'),
						'name' => $l10n->t('Discourse'),
					];
				});
			}
		}
	}
}

