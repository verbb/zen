<?php
namespace verbb\zen;

use verbb\zen\base\PluginTrait;
use verbb\zen\base\ProcessingLogTrait;
use verbb\zen\models\Settings;
use verbb\zen\variables\ZenVariable;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Drafts;
use craft\services\Elements;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;

use yii\base\Event;

class Zen extends Plugin
{
    // Properties
    // =========================================================================

    public bool $hasCpSection = true;
    public bool $hasCpSettings = true;
    public string $schemaVersion = '1.0.0';


    // Traits
    // =========================================================================

    use PluginTrait;
    use ProcessingLogTrait;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->_setPluginComponents();
        $this->_setLogging();
        $this->_registerVariables();
        $this->_registerElementEvents();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpRoutes();
        }
    }

    public function getPluginName(): string
    {
        return Craft::t('zen', $this->getSettings()->pluginName);
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('zen/settings'));
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['zen'] = 'zen/plugin';
            $event->rules['zen/import/configure/<filename:.*>'] = 'zen/plugin';
            $event->rules['zen/import/review/<filename:.*>'] = 'zen/plugin';
            $event->rules['zen/import/review/<filename:.*>/<elements:.*>'] = 'zen/plugin';
            $event->rules['zen/import/run/<filename:.*>'] = 'zen/plugin';
            $event->rules['zen/import/run/<filename:.*>/<elements:.*>'] = 'zen/plugin';
            $event->rules['zen/settings'] = 'zen/plugin/settings';
        });
    }

    private function _registerVariables(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $event->sender->set('zen', ZenVariable::class);
        });
    }

    private function _registerElementEvents(): void
    {
        // Listen to when we delete and restore elements so that we can record that data to export.
        Event::on(Elements::class, Elements::EVENT_AFTER_DELETE_ELEMENT, [$this->getElements(), 'onDeleteElement']);
        Event::on(Elements::class, Elements::EVENT_AFTER_RESTORE_ELEMENT, [$this->getElements(), 'onRestoreElement']);

        // We also need to listen to applied draft which trigger a delete event, but we don't want to capture that.
        Event::on(Drafts::class, Drafts::EVENT_BEFORE_APPLY_DRAFT, [$this->getElements(), 'onApplyDraft']);
    }
}
