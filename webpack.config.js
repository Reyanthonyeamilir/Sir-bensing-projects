const Encore = require('@symfony/webpack-encore');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on this file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or subdirectory deploy
    // .setManifestKeyPrefix('build/')

    // 👇 Entry points (you can have both JS and CSS)
.addEntry('app', './assets/app.js')



    // When enabled, Webpack "splits" files into smaller pieces for optimization
    .splitEntryChunks()

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    .enableStimulusBridge('./assets/controllers.json')

    // enable a single runtime chunk
    .enableSingleRuntimeChunk()

    // cleans build folder before building
    .cleanupOutputBeforeBuild()

    // enable notifications (optional)
    .enableBuildNotifications()

    // enable source maps (for development)
    .enableSourceMaps(!Encore.isProduction())

    // enables hashed filenames (for cache busting)
    .enableVersioning(Encore.isProduction())

    // enable PostCSS loader
    .enablePostCssLoader()

    // enable Babel preset-env
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.38';
    })


    // Uncomment if you use Sass/SCSS
    // .enableSassLoader()

    // Uncomment if you use React
    // .enableReactPreset()
;

module.exports = Encore.getWebpackConfig();
