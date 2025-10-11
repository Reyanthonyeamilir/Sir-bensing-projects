// assets/bootstrap.js
// --- Symfony UX Stimulus Setup ---

import { startStimulusApp } from '@symfony/stimulus-bridge';

// Registers Stimulus controllers from controllers.json and the controllers/ directory
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));

// You can register any custom controllers here, for example:
// app.register('some_controller_name', SomeImportedController);
