import { registerPluginBootstrap } from '@verbb/craft-screenshots/api';

export default registerPluginBootstrap({
    id: 'zen',
    async setup(context) {
        await context.runCraft(['migrate/up', '--plugin=zen'], { allowFailure: true });
    },
});
