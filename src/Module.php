<?php

namespace Yiisoft\Yii\Debug;

/**
 * @deprecated This class will be removed after that all features from this will be implemented in `yiisoft/yii-debug-viewer`
 */
class Module
{
    const DEFAULT_IDE_TRACELINE = '<a href="ide://open?url=file://{file}&line={line}">{text}</a>';

    /**
     * @var array the list of IPs that are allowed to access this module.
     * Each array element represents a single IP filter which can be either an IP address
     * or an address with wildcard (e.g. 192.168.0.*) to represent a network segment.
     * The default value is `['127.0.0.1', '::1']`, which means the module can only be accessed
     * by localhost.
     */
    public $allowedIPs = ['127.0.0.1', '::1'];
    /**
     * @var array the list of hosts that are allowed to access this module.
     * Each array element is a hostname that will be resolved to an IP address that is compared
     * with the IP address of the user. A use case is to use a dynamic DNS (DDNS) to allow access.
     * The default value is `[]`.
     */
    public $allowedHosts = [];
    /**
     * @var callable A valid PHP callback that returns true if user is allowed to use web shell and false otherwise
     *
     * The signature is the following:
     *
     * function (Action|null $action) The action can be null when called from a non action context (like set debug header)
     */
    public $checkAccessCallback;
    /**
     * @var array|Panel[] list of debug panels. The array keys are the panel IDs, and values are the corresponding
     * panel class names or configuration arrays. This will be merged with [[corePanels()]].
     * You may reconfigure a core panel via this property by using the same panel ID.
     * You may also disable a core panel by setting it to be false in this property.
     */
    public $panels = [];
    /**
     * @var string the name of the panel that should be visible when opening the debug panel.
     * The default value is 'log'.
     */
    public $defaultPanel = 'log';
    /** @var int the debug bar default height, as a percentage of the total screen height */
    public $defaultHeight = 50;

    public function bootstrap($app)
    {
        $logger = $app->getLogger();
        if ($logger instanceof \Yiisoft\Log\Logger) {
            $this->logTarget = new LogTarget($this);
            $logger->addTarget($this->logTarget, 'debug');
        }
        // @todo handle Monolog
        // @todo handle not supported logger

        $profiler = $app->getProfiler();
        if ($profiler instanceof \yii\profile\Profiler) {
            $this->profileTarget = new ProfileTarget();
            $profiler->addTarget($this->profileTarget, 'debug');
        }
        // @todo handle not supported profiler

        // delay attaching event handler to the view component after it is fully configured
        $app->on(RequestEvent::BEFORE, function () use ($app) {
            $app->getResponse()->on(Response::EVENT_AFTER_PREPARE, [$this, 'setDebugHeaders']);
        });
        $app->on(ActionEvent::BEFORE, function () use ($app) {
            $app->getView()->on(BodyEvent::END, [$this, 'renderToolbar']);
        });
        $app->getUrlManager()->addRules([
            [
                '__class' => $this->urlRuleClass,
                'route' => $this->id,
                'pattern' => $this->id,
                'suffix' => false,
            ],
            [
                '__class' => $this->urlRuleClass,
                'route' => $this->id . '/<controller>/<action>',
                'pattern' => $this->id . '/<controller:[\w\-]+>/<action:[\w\-]+>',
                'suffix' => false,
            ],
        ], false);
    }
    public function beforeAction(Action $action)
    {
        if (!$this->enableDebugLogs) {
            $logger = $this->app->getLogger();
            if ($logger instanceof \Yiisoft\Log\Logger) {
                foreach ($logger->getTargets() as $target) {
                    $target->enabled = false;
                }
            }
        }

        // do not display debug toolbar when in debug view mode
        $this->app->getView()->off(BodyEvent::END, [$this, 'renderToolbar']);
        $this->app->getResponse()->off(Response::EVENT_AFTER_PREPARE, [$this, 'setDebugHeaders']);

        if ($this->checkAccess($action)) {
            $this->resetGlobalSettings();
            return true;
        }

        if ($action->id === 'toolbar') {
            // Accessing toolbar remotely is normal. Do not throw exception.
            return false;
        }

        throw new ForbiddenHttpException('You are not allowed to access this page.');
    }

    /**
     * Renders mini-toolbar at the end of page body.
     *
     * @param \yii\base\Event $event
     */
    public function renderToolbar($event)
    {
        if (!$this->checkAccess() || $this->app->getRequest()->getIsAjax()) {
            return;
        }

        /** @var View $view */
        $view = $event->getTarget();
        echo $view->renderDynamic('return $this->app->getModule("' . $this->id . '")->getToolbarHtml();');

        // echo is used in order to support cases where asset manager is not available
        echo '<style>' . $view->renderPhpFile(__DIR__ . '/assets/css/toolbar.css') . '</style>';
        echo '<script>' . $view->renderPhpFile(__DIR__ . '/assets/js/toolbar.js') . '</script>';
    }

    /**
     * Checks if current user is allowed to access the module
     * @param \yii\base\Action|null $action the action to be executed. May be `null` when called from
     * a non action context
     * @return bool if access is granted
     */
    protected function checkAccess($action = null)
    {
        $allowed = false;
        $ip = $this->app->getRequest()->getUserIP();
        foreach ($this->allowedIPs as $filter) {
            if ($filter === '*' || $filter === $ip || (($pos = strpos($filter, '*')) !== false && !strncmp($ip, $filter, $pos))) {
                $allowed = true;
                break;
            }
        }
        if ($allowed === false) {
            foreach ($this->allowedHosts as $hostname) {
                $filter = gethostbyname($hostname);
                if ($filter === $ip) {
                    $allowed = true;
                    break;
                }
            }
        }
        if ($allowed === false) {
            if (!$this->disableIpRestrictionWarning) {
                Yii::warning('Access to debugger is denied due to IP address restriction. The requesting IP address is ' . $ip, __METHOD__);
            }

            return false;
        }

        if ($this->checkAccessCallback !== null && call_user_func($this->checkAccessCallback, $action) !== true) {
            if (!$this->disableCallbackRestrictionWarning) {
                Yii::warning('Access to debugger is denied due to checkAccessCallback.', __METHOD__);
            }

            return false;
        }

        return true;
    }
}
