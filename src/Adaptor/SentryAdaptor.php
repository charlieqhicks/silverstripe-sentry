<?php

/**
 * Class: SentryAdaptor.
 *
 * @author  Russell Michell 2017-2021 <russ@theruss.com>
 * @package phptek/sentry
 */

namespace PhpTek\Sentry\Adaptor;

use Sentry\State\Hub;
use Sentry\ClientBuilder;
use Sentry\State\Scope;
use Sentry\ClientInterface;
use Sentry\Severity;
use Sentry\SentrySdk;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Environment as Env;
use PhpTek\Sentry\Adaptor\SentrySeverity;
use PhpTek\Sentry\Helper\SentryHelper;
use PhpTek\Sentry\Exception\SentryLogWriterException;

/**
 * The SentryAdaptor provides a functionality bridge between the getsentry/sentry
 * PHP SDK and {@link SentryLogger} itself.
 */
class SentryAdaptor
{
    use Configurable;

    /**
     * @var ClientInterface
     */
    protected $sentry;

    /**
     * Internal storage for context. Used only in the case of non-exception
     * data sent to Sentry.
     *
     * @var array
     */
    protected $context = [];

    /**
     * @return void
     */
    public function __construct()
    {
        $client = ClientBuilder::create($this->getOpts() ?: [])->getClient();
        SentrySdk::setCurrentHub(new Hub($client));

        $this->sentry = $client;
    }

    /**
     * @return ClientInterface
     */
    public function getSDK(): ClientInterface
    {
        return $this->sentry;
    }

    /**
     * Configures Sentry "context" to display additional information about a SilverStripe
     * application's runtime and context.
     *
     * @param  string $field
     * @param  mixed  $data
     * @return mixed null|void
     * @throws SentryLogWriterException
     */
    public function setContext(string $field, $data)
    {
        $hub = SentrySdk::getCurrentHub();
        $options = $hub->getClient()->getOptions();

        // Use Sentry's own default stacktrace. This was the default prior to v4
        $options->setAttachStacktrace((bool) !$this->config()->get('custom_stacktrace'));

        switch ($field) {
            case 'env':
                $options->setEnvironment($data);
                $this->context['env'] = $data;
                break;
            case 'tags':
                $hub->configureScope(function (Scope $scope) use ($data): void {
                    foreach ($data as $tagName => $tagData) {
                        $tagName = SentryHelper::normalise_key($tagName);
                        $scope->setTag($tagName, $tagData);
                        $this->context['tags'][$tagName] = $tagData;
                    }
                });
                break;
            case 'user':
                $hub->configureScope(function (Scope $scope) use ($data): void {
                    $scope->setUser($data, true);
                    $this->context['user'] = $data;
                });
                break;
            case 'extra':
                $hub->configureScope(function (Scope $scope) use ($data): void {
                    foreach ($data as $extraKey => $extraData) {
                        $extraKey = SentryHelper::normalise_key($extraKey);
                        $scope->setExtra($extraKey, $extraData);
                        $this->context['extra'][$extraKey] = $extraData;
                    }
                });
                break;
            case 'level':
                $hub->configureScope(function (Scope $scope) use ($data): void {
                    $scope->setLevel(new Severity(SentrySeverity::process_severity($level = $data)));
                });
                break;
            default:
                $msg = sprintf('Unknown field "%s" passed to %s().', $field, __FUNCTION__);
                throw new SentryLogWriterException($msg);
        }
    }

    /**
     * Get _locally_ set contextual data, that we should be able to get from Sentry's
     * current {@link Scope}.
     *
     * Note: This (re) sets data to a new instance of {@link Scope} for passing to
     * captureMessage(). One would expect this to be set by default, as it is for
     * $record data sent to Sentry via captureException(), but it isn't.
     *
     * @return Scope
     */
    public function getContext(): Scope
    {
        $scope = new Scope();

        $scope->setUser($this->context['user']);

        if (!empty($this->context['tags'])) {
            foreach ($this->context['tags'] as $tagKey => $tagData) {
                $tagKey = SentryHelper::normalise_key($tagKey);
                $scope->setTag($tagKey, $tagData);
            }
        }

        if (!empty($this->context['tags'])) {
            foreach ($this->context['extra'] as $extraKey => $extraData) {
                $extraKey = SentryHelper::normalise_key($extraKey);
                $scope->setExtra($extraKey, $extraData);
            }
        }

        return $scope;
    }

    /**
     * Get various userland options to pass to Raven. Includes detecting and setting
     * proxy options too.
     *
     * @param  string $opt
     * @return mixed  string|array Depending on whether $opts is passed.
     */
    public function getOpts(string $opt = '')
    {
        $opts = [];

        // Extract env-vars from YML config or env
        if ($dsn = Env::getEnv('SENTRY_DSN')) {
            $opts['dsn'] = $dsn;
        }

        // Env vars take precedence over YML config in array_merge()
        $opts = Injector::inst()
            ->convertServiceProperty(array_merge($this->config()->get('opts') ?? [], $opts));

        // Deal with proxy settings. Raven_Client permits host:port format but SilverStripe's
        // YML config only permits single backtick-enclosed env/consts per config
        if (!empty($opts['http_proxy'])) {
            if (!empty($opts['http_proxy']['host']) && !empty($opts['http_proxy']['port'])) {
                $opts['http_proxy'] = sprintf(
                    '%s:%s',
                    $opts['http_proxy']['host'],
                    $opts['http_proxy']['port']
                );
            }
        }

        return $opts[$opt] ?? $opts;
    }

}
