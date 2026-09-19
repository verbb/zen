import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import type { ScreenshotSetupContext } from '@verbb/craft-screenshots/types';

type ZenFixture = {
    configureRoute: string;
    startRoute: string;
};

const supportDir = dirname(fileURLToPath(import.meta.url));
const seedScript = readFileSync(join(supportDir, 'seed', 'seed-zen.php'), 'utf8');

/** Seed representative entries and a real Zen archive for the feature tour. */
export async function seedZenFixture(context: ScreenshotSetupContext): Promise<ZenFixture> {
    const output = await context.runCraftScript(seedScript, { label: 'seed-zen-feature-tour' });
    const fixture = JSON.parse(output.trim()) as ZenFixture;

    if (!fixture.startRoute || !fixture.configureRoute) {
        throw new Error(`Invalid Zen fixture payload: ${output}`);
    }

    return fixture;
}
