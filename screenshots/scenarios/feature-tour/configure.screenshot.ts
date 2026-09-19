import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedZenFixture } from '../../support/fixtures';

let configureRoute = '/admin/zen';

export default defineScreenshotScenario({
    id: 'zen-feature-tour-configure',
    output: 'feature-tour/zen-configure.png',
    route: () => configureRoute,
    viewport: { width: 1440, height: 1100, deviceScaleFactor: 2 },
    async setup(context) {
        const fixture = await seedZenFixture(context);
        configureRoute = fixture.configureRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: '.zui-import-stats', state: 'visible', timeout: 30000 },
        { type: 'selector', selector: '.zui-import-table', state: 'visible' },
        { type: 'text', text: 'Changed Elements' },
    ],
    steps: [
        { type: 'wait', waitFor: { type: 'timeout', ms: 400 } },
    ],
    target: {
        type: 'selector',
        selector: '.zen-app',
        padding: 10,
    },
    caption: 'A real Zen archive analysed in Craft 5, with add, change, delete and restore states ready for review.',
    intent: 'Show the useful configuration step before an import is queued, including Zen’s change summary and element-level controls.',
});
