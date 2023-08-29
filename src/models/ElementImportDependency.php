<?php
namespace verbb\zen\models;

use verbb\zen\Zen;

use craft\base\ElementInterface;
use craft\base\Model;

use Closure;

class ElementImportDependency extends Model
{
    // Properties
    // =========================================================================

    public ElementImportAction $elementImportAction;
    public Closure $callback;


    // Public Methods
    // =========================================================================

    public function callback(...$args)
    {
        call_user_func($this->callback, ...$args);
    }

    public function getExistingElement(): ?ElementInterface
    {
        // Find an existing element given the criteria in `$this->elementImportAction` to save importing it.
        $element = null;

        $registeredElement = $this->elementImportAction->elementType;
        $sourceData = $this->elementImportAction->data;

        $elementIdentifier = $registeredElement::elementUniqueIdentifier();
        $elementUid = $sourceData[$elementIdentifier] ?? null;

        if ($elementUid) {
            // For even more performance, use a fetch-cache
            if ($cachedExistingElement = Zen::$plugin->getElements()->getCachedExistingElement($elementUid)) {
                return $cachedExistingElement;
            }

            $element = $registeredElement::elementType()::find()
                ->$elementIdentifier($elementUid)
                ->status(null)
                ->one();

            // Cache it in case we call the same element
            if ($element) {
                Zen::$plugin->getElements()->setCachedExistingElement($elementUid, $element);
            }
        }

        return $element;
    }
}
