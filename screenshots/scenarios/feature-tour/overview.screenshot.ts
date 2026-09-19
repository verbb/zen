import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedZenFixture } from '../../support/fixtures';

let startRoute = '/admin/zen';

export default defineScreenshotScenario({
    id: 'zen-feature-tour-import-export',
    output: 'feature-tour/zen-import-export.png',
    route: () => startRoute,
    viewport: { width: 1440, height: 1000, deviceScaleFactor: 2 },
    async setup(context) {
        const fixture = await seedZenFixture(context);
        startRoute = fixture.startRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: '.zui-start', state: 'visible', timeout: 30000 },
        { type: 'text', text: 'Import Content' },
        { type: 'text', text: 'Export Content' },
    ],
    preSteps: [
        { type: 'wait', waitFor: { type: 'timeout', ms: 500 } },
    ],
    target: {
        type: 'selector',
        selector: '.zui-start',
        padding: 12,
    },
    caption: 'Zen’s genuine Craft 5 import and export landing workflow.',
    intent: 'Show the two clear starting points for moving selected Craft content between environments.',
});
